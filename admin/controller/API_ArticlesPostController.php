<?php

namespace MagicProAdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use MagicProDatabaseModels\Article;

require_once __DIR__ . '/MagicProBuilder.php';

class API_ArticlesPostController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        try {
            $methods = [
                'getDefaultController'         => ['name' => 'getDefaultController'],
                'getDefaultLiveWareController' => ['name' => 'getDefaultLiveWareController'],
                'getParents'                   => ['name' => 'getParents'],
                'getChildrens'                 => ['name' => 'getChildrens'],
                'getBrothers'                  => ['name' => 'getBrothers'],
                'makeHeTree'                   => ['name' => 'makeHeTree'],
                'getById'                      => ['name' => 'getArticle'],
                'createNew'                    => ['name' => 'createNew'],
                'deleteById'                   => ['name' => 'deleteRec'],
                'articleByName'                => ['name' => 'getArticleByName'],
                'move'                         => ['name' => 'move'],
                'copyRec'                      => ['name' => 'copyRec'],
                'saveById'                     => ['name' => 'saveById'],
                'regenerateAll'                => ['name' => 'regenerateAll'],
                'search'                       => ['name' => 'search'],
                'checkUrlByPhp'                => ['name' => 'checkUrlByPhp'],


            ];

            $command = $request->string('command')->toString();

            if (!array_key_exists($command, $methods)) {
                throw new \InvalidArgumentException("Unknown command '{$command}'");
            }

            $methodName = $methods[$command]['name'];
            if (!method_exists($this, $methodName)) {
                throw new \BadMethodCallException("Method {$methodName} not found");
            }

            $data = $this->{$methodName}($request);

            return response()->json([
                'status'  => true,
                'data'    => $data,
                'request' => $request->all(),
            ]);
        } catch (\Throwable $th) {
            $msg = $th->getMessage();
            if ($th->getFile()) $msg .=  "\n" . 'in ' . $th->getFile();
            if ($th->getLine()) $msg .= "\n" .  'on line ' . $th->getLine();

            return response()->json([
                'status'   => false,
                'errorMsg' => $msg,
                'request'  => $request->all(),
            ]);
        }
    }

    // ==================================================================
    //                     ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
    // ==================================================================

    private function saveHtmlFile(string $url, string $body): void
    {
        // Берём только path (без протокола, домена и параметров)
        $path = parse_url($url, PHP_URL_PATH) ?? '/';

        // Корневая страница
        if ($path === '/' || $path === '' || $path === null) {
            $fullPath = public_path('_html/index.html');
        } else {
            // Приводим путь к html/<path>.html
            // /test → html/test.html
            // /a/b/  → html/a/b/index.html
            // /a/b   → html/a/b.html
            $path = rtrim($path, '/');

            if ($path === '') {
                $fullPath = public_path('_html/index.html');
            } elseif (str_contains($path, '/')) {
                // Поддиректории
                $dir = public_path('html/' . dirname($path));
                @mkdir($dir, 0777, true);
                $fullPath = public_path('_html/' . $path . '.html');
            } else {
                // Обычный файл
                $fullPath = public_path('_html/' . $path . '.html');
            }
        }

        // Создать директорию если её нет
        @mkdir(dirname($fullPath), 0777, true);

        // Пишем файл
        file_put_contents($fullPath, $body);
    }

    private function checkUrlByPhp(Request $request): array
    {
        $url = $request->input('url');

        $host = parse_url($url, PHP_URL_HOST);

        $resolve = [];
        if (str_ends_with($host, '.test')) {
            $resolve[] = "$host:80:192.168.1.33";
            $resolve[] = "$host:443:192.168.1.33";
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_RESOLVE => $resolve,
        ]);

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?? '';

        curl_close($ch);

        if (
            $code === 200 &&  $body !== false && str_starts_with($contentType, 'text/html')
        ) {
            // $this->saveHtmlFile($url, $body);
        }

        return [
            'check' => ($code >= 200 && $code < 400),
            'code'   => $code,
            'body' => $body,
            'url' => $url
        ];
    }


    private function search(Request $request): array
    {
        $term = $request->input('text');
        $results = Article::where(function ($q) use ($term) {
            $q->where('body', 'like', "%$term%")
                ->orWhere('controller', 'like', "%$term%");
        })->select('id', 'title')->get()->toArray();


        return $results;
    }


    private function regenerateAll(Request $request): array
    {

        $records = Article::orderBy('name', 'asc')->get()->toArray();
        $saved = [];

        foreach ($records as $article) {
            deleteMpro($article);
            createMpro($article);
            $saved[] =  $article['name'];
        }
        return $saved;
    }

    private function saveById(Request $request): array
    {

        $article = $request->input('article', []);
        $id = $article['id'] ?? 0;

        if ($id == 1) {
            $article['parentId'] = 0;
        }

        $record = Article::find($id);
        if (!$record) {
            throw new \InvalidArgumentException("id=$id not found");
        }

        deleteMpro($record->toArray());

        $record->update($article);
        createMpro($record->toArray());

        return $record->toArray();
    }

    private function getArticleByName(Request $request): array
    {
        $name = $request->input('name');
        $article = Article::where('name', $name)->first();
        if (!$article) {
            throw new \InvalidArgumentException("Статья с name='{$name}' не найдена");
        }
        return $article->toArray();
    }

    private function getDefaultController(): array
    {
        return ['controller' => readDefaultController()];
    }

    private function getDefaultLiveWareController(): array
    {
        return ['controller' => readDefaultLiveWareController()];
    }

    private function copyRec(Request $request): array
    {
        $id = $request->integer('id');
        return DB::transaction(function () use ($id) {
            $src = Article::lockForUpdate()->findOrFail($id);

            Article::where('parentId', $src->parentId)
                ->where('npp', '>', $src->npp)
                ->lockForUpdate()
                ->increment('npp');

            $ts = (string) round(microtime(true) * 1000);

            $copy = $src->replicate();
            $copy->name  = $src->name  . '_' . $ts;
            $copy->title = $src->title . ' ' . $ts;
            $copy->npp   = $src->npp + 1;
            $copy->directory = false;
            $copy->save();

            createMpro($copy->toArray());
            $copy->children = [];

            return $copy->toArray();
        });
    }

    private function move(Request $request): array
    {
        $id = $request->integer('id');
        $newParentId = $request->integer('newParentId');
        $idBrotherUp = $request->integer('idBrotherUp');

        $result = DB::transaction(function () use ($id, $newParentId, $idBrotherUp) {
            $a = Article::lockForUpdate()->findOrFail($id);
            return ($a->parentId === $newParentId)
                ? self::moveWithinParent($a, $idBrotherUp)
                : self::moveToAnotherParent($a, $newParentId, $idBrotherUp);
        });

        return $result->toArray();
    }

    private static function moveWithinParent(Article $a, int $idBrotherUp): Article
    {
        $parentId = (int) $a->parentId;
        $oldNpp   = (int) $a->npp;

        $pos = ($idBrotherUp === 0)
            ? 1
            : (function () use ($idBrotherUp, $parentId) {
                $bro = Article::lockForUpdate()->findOrFail($idBrotherUp);
                if ((int) $bro->parentId !== $parentId) {
                    throw new \InvalidArgumentException('idBrotherUp не из того же parentId');
                }
                return (int) $bro->npp + 1;
            })();

        if ($pos === $oldNpp) {
            return $a;
        }

        if ($pos > $oldNpp) {
            Article::where('parentId', $parentId)
                ->where('npp', '>',  $oldNpp)
                ->where('npp', '<=', $pos)
                ->lockForUpdate()
                ->decrement('npp');
        } else {
            Article::where('parentId', $parentId)
                ->where('npp', '>=', $pos)
                ->where('npp', '<',  $oldNpp)
                ->lockForUpdate()
                ->increment('npp');
        }

        $a->npp = $pos;
        $a->save();

        return $a;
    }

    private static function moveToAnotherParent(Article $a, int $newParentId, int $idBrotherUp): Article
    {
        return DB::transaction(function () use ($a, $newParentId, $idBrotherUp) {
            $oldParentId = (int) $a->parentId;
            if ($newParentId === $oldParentId) {
                throw new \InvalidArgumentException('newParentId равен текущему parentId');
            }

            $oldNpp = (int) $a->npp;

            Article::where('parentId', $oldParentId)
                ->where('npp', '>', $oldNpp)
                ->lockForUpdate()
                ->decrement('npp');

            $pos = 1;
            if ($idBrotherUp !== 0) {
                $bro = Article::lockForUpdate()->findOrFail($idBrotherUp);
                if ((int) $bro->parentId !== $newParentId) {
                    throw new \InvalidArgumentException('idBrotherUp принадлежит другому parentId');
                }
                $pos = (int) $bro->npp + 1;
            }

            Article::where('parentId', $newParentId)
                ->where('npp', '>=', $pos)
                ->lockForUpdate()
                ->increment('npp');

            $a->parentId = $newParentId;
            $a->npp = $pos;
            $a->save();

            $hasChildrenOld = Article::where('parentId', $oldParentId)
                ->lockForUpdate()
                ->exists();

            if (!$hasChildrenOld) {
                if ($oldParent = Article::lockForUpdate()->find($oldParentId)) {
                    if ($oldParent->directory) {
                        $oldParent->directory = false;
                        $oldParent->save();
                    }
                }
            }

            if ($parent = Article::lockForUpdate()->find($newParentId)) {
                if (!$parent->directory) {
                    $parent->directory = true;
                    $parent->save();
                }
            }

            return $a;
        });
    }

    private function getArticle(Request $request): array
    {
        $id = $request->integer('id');
        $article = Article::find($id);
        if ($article) {
            return $article->toArray();
        }
        throw new \InvalidArgumentException("id=$id not found");
    }

    private function createNew(Request $request): array
    {
        $id = $request->integer('id');
        return DB::transaction(function () use ($id) {
            $parent = $this->getArticle(new Request(['id' => $id]));
            $maxNpp = DB::table('articles')
                ->where('parentId', $id)
                ->lockForUpdate()
                ->max('npp') ?? 0;

            $article = new Article();
            $article->name     = 'art_' . (int) round(microtime(true) * 1000);
            $article->title    = $article->name;
            $article->parentId = $id;
            $article->npp      = $maxNpp + 1;
            $article->directory = false;
            $article->controller = readDefaultController();

            if (!$article->save()) {
                throw new \InvalidArgumentException("Error: creation failed");
            }

            if (empty($parent['directory'])) {
                Article::where('id', $id)->update(['directory' => true]);
            }

            return $article->toArray();
        });
    }

    private function deleteRec(Request $request): array
    {
        $id = $request->integer('id');
        if ($id === 1) {
            throw new \InvalidArgumentException('Удалять рут нельзя');
        }

        return DB::transaction(function () use ($id) {
            return $this->deleteRecNoTx($id);
        });
    }

    private function deleteRecNoTx(int $id): array
    {
        $article = Article::find($id);
        if (!$article) {
            throw new \InvalidArgumentException("Удаление: id={$id} не найден");
        }

        $parent = Article::find($article->parentId);
        $childIds = Article::where('parentId', $id)->pluck('id');

        foreach ($childIds as $childId) {
            $this->deleteRecNoTx((int) $childId);
        }

        Article::where('parentId', $article->parentId)
            ->where('npp', '>', $article->npp)
            ->decrement('npp');

        deleteMpro($article->toArray());
        $article->delete();

        if ($parent && Article::where('parentId', $parent->id)->doesntExist()) {
            $parent->directory = false;
            $parent->save();
        }

        return $parent ? $parent->toArray() : [];
    }

    private function makeHeTree(Request $request): array
    {
        $id = $request->integer('id');
        $tree = [];
        $curId = $id;
        $maxDepth = Article::count();
        $i = 0;

        while (true) {
            if (++$i > $maxDepth) {
                throw new \InvalidArgumentException("Слишком глубокая или зацикленная иерархия (id={$id})");
            }

            $article = Article::where('id', $curId)
                ->first(['id', 'title as text', 'npp', 'parentId', 'menuOn', 'isRoute', 'directory']);

            if (!$article) {
                throw new \InvalidArgumentException("Статья id={$curId} не найдена");
            }

            $node = $article->toArray();
            $node['children'] = Article::where('parentId', $curId)
                ->orderBy('npp')
                ->orderBy('id')
                ->get(['id', 'title as text', 'npp', 'parentId', 'menuOn', 'isRoute', 'directory'])
                ->toArray();

            if (empty($tree)) {
                $tree = $node;
            } else {
                foreach ($node['children'] as $key => $val) {
                    if ((int) $val['id'] === (int) $tree['id']) {
                        $node['children'][$key] = $tree;
                        break;
                    }
                }
                $tree = $node;
            }

            $curId = (int) $node['parentId'];
            if ($curId === 0) {
                break;
            }
        }

        return [$tree];
    }

    private function getParents(Request $request): array
    {
        $id = $request->integer('id');
        if ($id == 1) {
            return [];
        }

        $parents = [];
        $article = Article::find($id);
        if (!$article) {
            throw new \InvalidArgumentException("id={$id} не найден");
        }

        while (true) {
            $parent = Article::find($article->parentId);
            if (!$parent) {
                throw new \InvalidArgumentException("Родитель id={$article->parentId} не найден");
            }

            $parents[] = [
                'id'    => $parent->id,
                'name'  => $parent->name,
                'title' => $parent->title,
                'npp'   => $parent->npp,
            ];
            $article = $parent;
            if ($article->id == 1) break;
        }

        return $parents;
    }

    private function getChildrens(Request $request): array
    {
        $id = $request->integer('id');
        if (!Article::find($id)) {
            throw new \InvalidArgumentException("id={$id} не найден");
        }

        return Article::where('parentId', $id)
            ->orderBy('npp')
            ->get(['id', 'title as text', 'npp', 'parentId', 'menuOn', 'isRoute', 'directory'])
            ->toArray();
    }

    private function getBrothers(Request $request): array
    {
        $id = $request->integer('id');
        $article = Article::find($id);
        if (!$article) {
            throw new \InvalidArgumentException("id={$id} не найден");
        }

        return Article::where('parentId', $article->parentId)
            ->orderBy('npp')
            ->orderBy('id')
            ->get(['id', 'name', 'title', 'npp'])
            ->toArray();
    }
}
