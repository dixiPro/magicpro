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
    public function exportArticle()
    {
        $rows = Article::all();

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
            'Content-Disposition' => 'attachment; filename="articles.json"',
        ]);
    }


    public function importArticle(Request $request)
    {
        $file = $request->file('file');
        $writeBase = $request->boolean('writeBase', false); // по умолчанию false

        if (!$file || !$file->isValid()) {
            return response()->json([
                'error'        => 'Файл не загружен или повреждён',
                'method'       => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'has_file'     => $request->hasFile('file'),
                'all_input'    => $request->all(),
                'files'        => $_FILES,
                'file_object'  => $file ? [
                    'isValid' => $file->isValid(),
                    'originalName' => $file->getClientOriginalName(),
                    'mimeType'     => $file->getMimeType(),
                    'size'         => $file->getSize(),
                    'tmpPath'      => $file->getRealPath(),
                ] : null,
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            return response('Файл не загружен или повреждён', 400);
        }

        $data = json_decode(file_get_contents($file->getRealPath()), true);
        if (!is_array($data)) {
            return response('Некорректный формат JSON', 400);
        }

        $log = [];

        DB::transaction(function () use ($data, $writeBase, &$log) {
            $map = [];
            $temp = [];

            // 1. Создаём или обновляем записи
            foreach ($data as $item) {
                $parentName = $item['parentName'] ?? null;
                unset($item['parentName']);

                $article = Article::where('name', $item['name'])->first();

                if ($article) {
                    $log[] = [
                        'name' => $item['name'],
                        'msg'  => "Обновлена запись",
                    ];
                    if ($writeBase) {
                        $article->update($item);
                    }
                } else {
                    $log[] = [
                        'name' => $item['name'],
                        'msg'  => "Создана запись",
                    ];
                    if ($writeBase) {
                        $article = Article::create($item);
                    } else {
                        // фиктивный объект для тестового режима
                        $article = new Article($item);
                        $article->id = rand(10000, 99999);
                    }
                }

                $map[$article->name] = $article->id;
                $temp[$article->id] = $parentName;
            }

            $log[] = [
                'name' => '',
                'msg'  => "Правка родителей",
            ];

            // 2. Проставляем parentId по parentName
            foreach ($temp as $id => $parentName) {
                if ($parentName && isset($map[$parentName])) {
                    $log[] = [
                        'name' => $parentName,
                        'msg'  => "Установлен как родитель для ID {$id}",
                    ];
                    if ($writeBase) {
                        Article::where('id', $id)->update(['parentId' => $map[$parentName]]);
                    }
                }
            }

            if (!$writeBase) {
                DB::rollBack();
            }
        });

        return back()->with('importResult', $log);
    }
}
