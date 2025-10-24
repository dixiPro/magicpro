<?php

namespace MagicProAdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use MagicProDatabaseModels\Article;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportExportController extends Controller
{
    /**
     * Экспорт статей в JSON
     */
    public function exportArticle(Request $request)
    {
        $id = $request->input('id');
        $name = Article::where('id', $id)->first()->name;
        // Рекурсивно получаем все дочерние id
        $ids = $this->collectIds($id);

        // Получаем записи по этим id
        $rows = Article::whereIn('id', $ids)->get();

        $data = $rows->map(function ($m) {
            $item = $m->only($m->getFillable());

            // Находим имя родителя
            $parentName = null;
            if ($m->parentId > 0) {
                $parentName = Article::where('id', $m->parentId)->value('name');
            }

            $item['parentName'] = $parentName;
            unset($item['parentId']);

            return $item;
        });

        $json = $data->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return new StreamedResponse(function () use ($json) {
            echo $json;
        }, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $name . '.json"',
        ]);
    }

    /**
     * Рекурсивно собирает все id поддерева.
     */
    private function collectIds($id)
    {
        $ids = collect([$id]);

        $children = Article::where('parentId', $id)->pluck('id');

        foreach ($children as $childId) {
            $ids = $ids->merge($this->collectIds($childId));
        }

        return $ids;
    }


    private function parseArticlesXml(string $xmlText): array
    {
        // Удаляем разделители между статьями
        $xmlText = preg_replace('~<hr\s+separate_line\s*>~i', '', $xmlText);

        // Удаляем в конце мусор
        $pos = strpos($xmlText, '</article-list>');
        if ($pos !== false) {
            $xmlText = substr($xmlText, 0, $pos + strlen('</article-list>'));
        }
        // оборачиваем art_body
        $xmlText = str_replace('<art_body>', '<art_body><![CDATA[', $xmlText);
        $xmlText = str_replace('</art_body>', ']]></art_body>', $xmlText);

        try {
            $xml = simplexml_load_string($xmlText, 'SimpleXMLElement', LIBXML_NOCDATA);
        } catch (\Throwable $th) {
            // Извлекаем номер строки из сообщения об ошибке
            preg_match('/line\s+(\d+)/i', $th->getMessage(), $m);
            $errLine = isset($m[1]) ? (int)$m[1] : 0;

            // Разбиваем XML по строкам
            $lines = explode("\n", $xmlText);
            $total = count($lines);

            // Определяем диапазон ±10 строк вокруг ошибки
            $start = max(0, $errLine - 10);
            $end = min($total, $errLine + 10);

            // Собираем контекст
            $excerpt = '';
            for ($i = $start; $i < $end; $i++) {
                $mark = ($i + 1 === $errLine) ? ' <<< ПРОБЛЕМА ЗДЕСЬ' : '';
                $excerpt .= str_pad($i + 1, 4, ' ', STR_PAD_LEFT) . ': ' . $lines[$i] . $mark . "\n";
            }

            $excerpt = '<pre>' . $excerpt . '/<pre>';
            throw new \RuntimeException(
                "Error XML (string $errLine):\n\n$excerpt\Msg: {$th->getMessage()}"
            );
            throw new \RuntimeException($th->getMessage());
        }


        $result = [];
        foreach ($xml->article as $index => $a) {
            $template = trim((string)($a->art_template ?? '')) ?: 'none';
            if ($template !== 'none') {
                $start = "@extends('magic::{$template}')\n";
                $start = $start . "@section('body')\n\n";
                $end = $start . "\n@endsection";
            } else {
                $start = '';
                $end = '';
            }
            $template = $template === 'none' ?  "" : "@extends('magic::{$template}')\n";

            $result[] = [
                'npp'         => (int)($a->art_npp ?? 0),
                'name'        => (string)($a->art_name ?? ''),
                'title'       => (string)($a->art_title ?? ''),
                'controller'  => '', // в XML нет — заполняем пустым
                'body'        => $start . (string)($a->art_body ?? '') . $end,
                'directory'   => ((int)$a->art_directory ?? 0) === 1,
                'menuOn'      => ((int)$a->art_menuOn ?? 0) === 1,
                'isRoute'     => ((int)$a->art_isRoute ?? 0) === 1,
                'routeParams' => [],
                'parentName'  => (string)($a->art_parentName ?? ''),
            ];
        }

        return $result;
    }


    public function importArticle(Request $request)
    {
        $log = new class {
            public array $items = [];

            public function add(string $name, string $msg): void
            {
                $this->items[] = ['name' => $name, 'msg' => $msg];
            }

            public function all(): array
            {
                return $this->items;
            }
        };

        DB::beginTransaction();
        try {
            $file = $request->file('file');
            $writeBase = $request->boolean('writeBase', false); // по умолчанию false
            $typeFile = $request->validate([
                'typeFile' => 'required|string|in:json,xml',
            ]);


            // Проверка файла
            if (!$file || !$file->isValid()) {
                throw new \Exception('Файл не загружен или повреждён');
            }
            if ($typeFile === 'json') {
                // Проверка JSON
                $data = json_decode(file_get_contents($file->getRealPath()), true);
                if (!is_array($data)) {
                    throw new \Exception('Некорректный формат JSON');
                }
            } else {

                $data = $this->parseArticlesXml(file_get_contents($file->getRealPath()));
            }

            foreach ($data as $item) {
                $article = Article::where('name', $item['name'])->first();

                if ($article) {
                    unset($item['parentName'], $item['npp']);
                    $article->update($item);
                    $log->add($item['name'], 'updated');
                } else {
                    $parentName = $item['parentName'] ?? null;

                    if (!$parentName) {
                        $log->add($item['name'], 'parent name not specified, set to root');
                        $parentName = 'root';
                    }

                    $parent = Article::where('name', $parentName)->first();
                    if (!$parent) {
                        $log->add($item['name'], 'parent not found, set to root');
                    }

                    $parentId = $parent?->id ?? 1;

                    $item['parentId'] = $parentId;

                    $last = Article::where('parentId', $parentId)
                        ->orderByDesc('npp')
                        ->first();

                    $npp = $last ? $last->npp + 1 : 1;

                    unset($item['parentName']);
                    $oldNpp = $item['npp'];
                    $item['npp'] = $npp;

                    Article::create($item);
                    $log->add($item['name'], "added. npp old={$oldNpp}, new={$npp}");
                }
            }
            // если writeBase комит, иначе отказ
            $writeBase ? DB::commit() : DB::rollBack();
        } catch (\Throwable $e) {
            DB::rollBack();
            $log->add('', 'ERROR: ' . $e->getMessage());
        }
        return back()->with('importResult', $log->all());
    }
}
