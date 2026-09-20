<?php

namespace MagicProAdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use MagicProDatabaseModels\Article;
use MagicProSrc\Archive\ArticleArchive;

require_once __DIR__ . '/MagicProBuilder.php';

class API_ArticlesPostController extends AbstractApiHandler
{
    protected array $map = [
        'getDefaultController'         => 'getDefaultController',
        'getDefaultLivewireController' => 'getDefaultLivewireController',
        'getParents'                   => 'getParents',
        'getChildrens'                 => 'getChildrens',
        'getBrothers'                  => 'getBrothers',
        'makeHeTree'                   => 'makeHeTree',
        'getById'                      => 'getArticle',
        'createNew'                    => 'createNew',
        'deleteById'                   => 'deleteRec',
        'articleByName'                => 'getArticleByName',
        'move'                         => 'move',
        'copyRec'                      => 'copyRec',
        'saveById'                     => 'saveById',
        'regenerateAll'                => 'regenerateAll',
        'search'                       => 'search',
        'versions'                     => 'versions',
        'versionGet'                   => 'versionGet',
        'versionRestore'               => 'versionRestore',
        'archiveInfo'                  => 'archiveInfo',
        'archivePurgeDeleted'          => 'archivePurgeDeleted',
    ];



    // ==================================================================
    //                     helper methods
    // ==================================================================

    protected function search(Request $request): array
    {
        $term = $request->input('text');
        $results = Article::where(function ($q) use ($term) {
            $q->where('body', 'like', "%$term%")
                ->orWhere('controller', 'like', "%$term%");
        })->select('id', 'title')->get()->toArray();


        return $results;
    }


    protected function regenerateAll(Request $request): array
    {

        $records = Article::orderBy('name', 'asc')->get()->toArray();
        $saved = [];

        foreach ($records as $article) {
            $result = createMpro($article);
            $view = $result['view'] ?? '--';
            $controller = $result['controller'] ?? false ? '--controller' : '';
            $saved[] = "{$article['name']} {$controller}";
        }
        return $saved;
    }

    protected function saveById(Request $request): array
    {

        $article = $request->input('article', []);
        $id = $article['id'] ?? 0;

        // Position in the tree is owned by the move command: it renumbers npp among
        // the siblings and maintains the directory flag of both parents. Writing the
        // fields here would skip all of that, so they are never taken from the payload.
        //
        // `directory` is of the same kind, only it says whether the article has
        // children — and that is decided by the tree, not by whoever is saving.
        // Written by hand it made a childless article look like a folder, and a
        // folder like a leaf: the tree loads children by this flag.
        unset($article['parentId'], $article['npp'], $article['directory']);

        $record = Article::find($id);
        if (!$record) {
            throw new \InvalidArgumentException("id=$id not found");
        }

        $before = $record->toArray();

        // routeParams is taken key by key, like the article itself: a key that
        // was not sent keeps its value. The editor sends the whole set anyway;
        // an agent that sends `{"useController": true}` used to reset every
        // other key.
        if (isset($article['routeParams']) && is_array($article['routeParams'])) {
            $article['routeParams'] = array_replace((array) $record->routeParams, $article['routeParams']);
        }

        // База принимает правку всегда: текст, набранный человеком, теряться не
        // должен. Отвлекли на середине, вернулся — а там твоё, с ошибкой и
        // ошибка названа.
        DB::transaction(function () use ($record, $article) {
            $record->update($article);
        });

        $saved = $record->toArray();

        // А вот на диск битый контроллер не едет. Проверка идёт до записи, и не
        // прошла — файлы остаются прежними: страница работает на старом
        // контроллере, пока новый не поправят. Раньше было наоборот: файлы
        // сносились первыми, а `php -l` смотрел на уже записанный текст, и
        // ошибка оставляла статью вообще без рабочих файлов.
        $publish = true;

        if ($saved['routeParams']['useController'] ?? false) {
            try {
                checkControllerName($saved['name']);
                lintControllerText(dataController($saved));
            } catch (\Throwable $e) {
                $saved['warning'] = trim($e->getMessage());
                $publish          = false;
            }
        }

        if ($publish) {
            // имя могло смениться: файлы прежнего имени больше никому не нужны,
            // а createMpro() знает только новое
            if (($before['name'] ?? '') !== ($saved['name'] ?? '')) {
                deleteMpro($before);
            }

            createMpro($saved);
        }

        // the archive keeps saved states, so the copy is made of what the
        // article has become, not of what it was.
        //
        // Its trouble is not the trouble of the save: the article is written and
        // its files are in place. An error here used to travel out as the answer
        // of `saveById`, and the person saved again — a second time over what
        // was already saved.
        try {
            ArticleArchive::add($saved);
        } catch (\Throwable $e) {
            \MproHelper::addLog('api', [
                'api'     => static::class,
                'command' => 'saveById',
                'error'   => 'article version not written: ' . $e->getMessage(),
                'article' => $saved['name'] ?? '',
            ]);
        }

        return $saved;
    }

    /**
     * The state of the archive for the setup page: how long it keeps things and
     * how much of it belongs to articles that no longer exist.
     */
    protected function archiveInfo(Request $request): array
    {
        return [
            'days'    => ArticleArchive::days(),
            'deleted' => ArticleArchive::deletedCount(),
        ];
    }

    /**
     * Throws away the versions of deleted articles.
     *
     * They are kept on purpose, so nothing sweeps them by itself: only this
     * command, and only when somebody pressed the button.
     */
    protected function archivePurgeDeleted(Request $request): array
    {
        return ['gone' => ArticleArchive::purgeDeleted()];
    }

    /** Versions of one article: date and author, without the articles. */
    protected function versions(Request $request): array
    {
        return ['versions' => ArticleArchive::versions((int) $request->input('id'))];
    }

    /** One version as the article was at that moment. */
    protected function versionGet(Request $request): array
    {
        return ArticleArchive::get((int) $request->input('versionId'));
    }

    /**
     * Puts a version back.
     *
     * Restoring is an ordinary save: the files are regenerated, and the state
     * that has just been restored goes into the archive with today's date, so
     * the history shows that somebody rolled back.
     */
    protected function versionRestore(Request $request): array
    {
        $version = ArticleArchive::get((int) $request->input('versionId'));

        $article = $version['article'];
        $article['id'] = $version['articleId'];

        return $this->saveById(new Request(['article' => $article]));
    }

    protected function getArticleByName(Request $request): array
    {
        $name = $request->input('name');
        $article = Article::where('name', $name)->first();
        if (!$article) {
            throw new \InvalidArgumentException("Article with name='{$name}' was not found");
        }
        return $article->toArray();
    }

    protected function getDefaultController(): array
    {
        return ['controller' => readDefaultController()];
    }

    /** The controller and the blade of a new Livewire component: they only work together. */
    protected function getDefaultLivewireController(): array
    {
        return [
            'controller' => readDefaultLivewireController(),
            'body'       => readDefaultLivewireView(),
        ];
    }

    protected function copyRec(Request $request): array
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

    protected function move(Request $request): array
    {
        $id = $request->integer('id');
        $newParentId = $request->integer('newParentId');
        $idBrotherUp = $request->integer('idBrotherUp');

        self::checkMove($id, $newParentId, $idBrotherUp);

        $result = DB::transaction(function () use ($id, $newParentId, $idBrotherUp) {
            $a = Article::lockForUpdate()->findOrFail($id);
            return ($a->parentId === $newParentId)
                ? self::moveWithinParent($a, $idBrotherUp)
                : self::moveToAnotherParent($a, $newParentId, $idBrotherUp);
        });

        return $result->toArray();
    }

    /**
     * Перенос, который не должен состояться.
     *
     * Модель запрещает только ссылку на саму себя, а дерево ломается шире:
     * родителя можно увести под собственного потомка и получить кольцо, из
     * которого не выбраться ни обходом вверх, ни обходом вниз. Корень можно
     * увести из корня. Статью можно поставить после самой себя.
     *
     * Всё это дешевле запретить на входе, чем разбирать потом «Проверкой
     * статей».
     */
    private static function checkMove(int $id, int $newParentId, int $idBrotherUp): void
    {
        if ($id === 1) {
            throw new \InvalidArgumentException('Root cannot be moved');
        }

        if ($newParentId === $id) {
            throw new \InvalidArgumentException('Article cannot be its own parent');
        }

        if ($idBrotherUp === $id) {
            throw new \InvalidArgumentException('Article cannot be placed after itself');
        }

        if ($newParentId < 1) {
            throw new \InvalidArgumentException('Only the root has parentId 0');
        }

        if (! Article::where('id', $newParentId)->exists()) {
            throw new \InvalidArgumentException("New parent id={$newParentId} not found");
        }

        // спуск от переносимой статьи вниз: встретили нового родителя — значит
        // он её потомок, и перенос свернул бы ветку в кольцо
        $level = [$id];
        $seen  = [$id => true];

        while ($level) {
            $children = Article::whereIn('parentId', $level)
                ->whereNotIn('id', array_keys($seen))
                ->pluck('id')
                ->all();

            if (in_array($newParentId, $children, true)) {
                throw new \InvalidArgumentException('Article cannot be moved under its own child');
            }

            foreach ($children as $child) {
                $seen[$child] = true;
            }

            $level = $children;
        }
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
                    throw new \InvalidArgumentException('idBrotherUp is not from the same parentId');
                }
                return (int) $bro->npp + 1;
            })();

        // Сосед считается на неподвинутом дереве. Когда статья едет вниз, она
        // сперва уходит со своего места, и все, кто был ниже, поднимаются на
        // единицу — включая того, после кого её просят встать. Без этой
        // поправки «поставь A после C» в ряду A B C D давало B C D A вместо
        // B C A D.
        if ($pos > $oldNpp) {
            $pos--;
        }

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
                throw new \InvalidArgumentException('newParentId is equal to the current parentId');
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
                    throw new \InvalidArgumentException('idBrotherUp belongs to a different parentId');
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

    protected function getArticle(Request $request): array
    {
        $id = $request->integer('id');
        $article = Article::find($id);
        if ($article) {
            return $article->toArray();
        }
        throw new \InvalidArgumentException("id=$id not found");
    }

    protected function createNew(Request $request): array
    {
        $id = $request->integer('id');
        return DB::transaction(function () use ($id) {
            $parent = $this->getArticle(new Request(['id' => $id]));
            $maxNpp = DB::table('articles')
                ->where('parentId', $id)
                // ->lockForUpdate()
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

    protected function deleteRec(Request $request): array
    {
        $id = $request->integer('id');
        if ($id === 1) {
            throw new \InvalidArgumentException('Root cannot be deleted');
        }

        return DB::transaction(function () use ($id) {
            return $this->deleteRecNoTx($id);
        });
    }

    protected function deleteRecNoTx(int $id): array
    {
        $article = Article::find($id);
        if (!$article) {
            throw new \InvalidArgumentException("Delete failed: id={$id} not found");
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

    protected function makeHeTree(Request $request): array
    {
        $id = $request->integer('id');
        $tree = [];
        $curId = $id;
        $maxDepth = Article::count();
        $i = 0;

        while (true) {
            if (++$i > $maxDepth) {
                throw new \InvalidArgumentException("Hierarchy is too deep or cyclic (id={$id})");
            }

            $article = Article::where('id', $curId)
                ->first(['id', 'title as text', 'npp', 'parentId', 'menuOn', 'isRoute', 'directory']);

            if (!$article) {
                throw new \InvalidArgumentException("Article id={$curId} not found");
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

    protected function getParents(Request $request): array
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

    protected function getChildrens(Request $request): array
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

    protected function getBrothers(Request $request): array
    {
        $id = $request->integer('id');
        $article = Article::find($id);
        if (!$article) {
            throw new \InvalidArgumentException("id={$id} not found");
        }

        return Article::where('parentId', $article->parentId)
            ->orderBy('npp')
            ->orderBy('id')
            ->get(['id', 'name', 'title', 'npp'])
            ->toArray();
    }
}
