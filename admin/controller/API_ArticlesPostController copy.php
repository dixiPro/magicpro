<?php

namespace MagicProAdminControllers;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\TryCatch;
use Illuminate\Support\Str;

use MagicProDatabaseModels\Article;


require_once __DIR__ . '/MagicProBuilder.php';

class API_ArticlesPostController extends Controller
{
    public function handle(Request $request): JsonResponse
    // command = getById / createNew / deleteById / saveById / getParents    //    
    {
        try {

            $data = $this->handleApi($request);
            return response()->json([
                'status' => true,
                'data' => $data,
                'request' => $request->all(),
            ]);
        } catch (\Throwable $th) {
            $msg = $th->getMessage();
            if ($th->getFile()) {
                $msg .= ' in ' . $th->getFile() . "\n";
            }
            if ($th->getLine()) {
                $msg .= ' on line ' . $th->getLine() . "\n";
            }
            if ($request->all()) {
                $msg .= ' | request: ' . json_encode($request->all(), JSON_UNESCAPED_UNICODE) . "\n";
            }

            return response()->json([
                'status' => false,
                'errorMsg' => $msg,
                'request' => $request->all()
            ]);
        }
    }

    public function handleApi(Request $request): array
    // command = getById / createNew / deleteById / saveById / getParents
    //    
    {
        $command = $request->string('command')->toString();

        if ($command == 'getDefaultController') {
            return $this->getDefaultController();
        }

        if ($command == 'getDefaultLiveWareController') {
            return $this->getDefaultLiveWareController();
        }

        if ($command == 'getParents') {
            return $this->getParents($request->integer('id'));
        }

        if ($command == 'getChildrens') {
            return $this->getChildrens($request->integer('id'));
        }

        if ($command == 'getBrothers') {
            return $this->getBrothers($request->integer('id'));
        }

        if ($command == 'makeHeTree') {
            return $this->makeHeTree($request->integer('id'));
        }

        // запрос статьи 
        if ($command == 'getById') {
            return $this->getArticle($request->integer('id'));
        }

        // создать  новую
        if ($command == 'createNew') {
            return $this->createNew($request->integer('id'));
        }

        // удалить
        if ($command == 'deleteById') {
            return $this->deleteRec($request->integer('id'));
        }

        if ($command == 'articleByName') {
            return $this->getArticleByName($request);
        }

        // переместить
        if ($command == 'move') {
            return $this->move(
                $request->integer('id'),
                $request->integer('newParentId'),
                $request->integer('idBrotherUp')
            )->toArray();
        }

        // копировать
        if ($command == 'copyRec') {
            return $this->copyRec($request->integer('id'));
        }

        // сохранить заменить
        if ($command == 'saveById') {

            $article = $request->input('article', []);
            $id = $article['id'];
            // root
            if ($id == 1) {
                $article['parentId'] = 0;
            }

            $record = Article::find($id);
            if (!$record) {
                throw new \InvalidArgumentException("id=$id not found");
            }

            // delete old controller
            deleteMpro($record->toArray());

            $record->update($article);

            createMpro($record->toArray());

            return $record->toArray();
        }


        throw new \InvalidArgumentException("Unkonown command " . $command);
    }

    // ================================

    private function getArticleByName(Request $request): array
    {
        $name = $request->input('name'); // строка, не integer
        $article = Article::where('name', $name)->first();

        if (!$article) {
            throw new \InvalidArgumentException("Статья с name='{$name}' не найдена");
        }
        return $article->toArray();
    }

    private function getDefaultController(): array
    {
        $article = ['controller' => readDefaultController()];
        return $article;
    }

    private function getDefaultLiveWareController(): array
    {
        $article = ['controller' => readDefaultLiveWareController()];
        return $article;
    }

    private function copyRec(int $id): array
    {
        return DB::transaction(function () use ($id) {
            /** @var Article $src */
            $src = Article::lockForUpdate()->findOrFail($id);

            // Сдвигаем справа: все, у кого npp > текущего — +1
            Article::where('parentId', $src->parentId)
                ->where('npp', '>', $src->npp)
                ->lockForUpdate()
                ->increment('npp');

            $ts = (string) round(microtime(true) * 1000);

            // Копируем все поля
            /** @var Article $copy */
            $copy = $src->replicate();
            $copy->name  = $src->name  . '_' . $ts;
            $copy->title = $src->title . ' ' . $ts;
            $copy->npp   = $src->npp + 1;
            $copy->directory  = false;
            $copy->save();

            createMpro($copy->toArray());

            $copy->children   = [];

            return $copy->toArray();
        });
    }

    private  function move(int $id, int $newParentId, int $idBrotherUp): Article
    {
        return DB::transaction(function () use ($id, $newParentId, $idBrotherUp) {
            /** @var Article $a */
            $a = Article::lockForUpdate()->findOrFail($id);
            return ($a->parentId === $newParentId)
                ? self::moveWithinParent($a, $idBrotherUp)
                : self::moveToAnotherParent($a, $newParentId, $idBrotherUp);
        });
    }

    private static function moveWithinParent(Article $a, int $idBrotherUp): Article
    {
        $parentId = (int)$a->parentId;
        $oldNpp   = (int)$a->npp;

        // целевая позиция
        $pos = ($idBrotherUp === 0)
            ? 1
            : (function () use ($idBrotherUp, $parentId) {
                $bro = Article::lockForUpdate()->findOrFail($idBrotherUp);
                if ((int)$bro->parentId !== $parentId) {
                    throw new \InvalidArgumentException('idBrotherUp не из того же parentId');
                }
                return (int)$bro->npp + 1;
            })();

        if ($pos === $oldNpp) {
            return $a; // уже на месте
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
                throw new \InvalidArgumentException('newParentId равен текущему parentId; используйте функцию перестановки внутри родителя');
            }

            $oldNpp = (int) $a->npp;

            // 1) закрыть "дыру" у старого родителя
            Article::where('parentId', $oldParentId)
                ->where('npp', '>', $oldNpp)
                ->lockForUpdate()
                ->decrement('npp');

            // 2) вычислить позицию у нового родителя
            $pos = 1;
            if ($idBrotherUp !== 0) {
                $bro = Article::lockForUpdate()->findOrFail($idBrotherUp);
                if ((int) $bro->parentId !== $newParentId) {
                    throw new \InvalidArgumentException('idBrotherUp принадлежит другому parentId');
                }
                $pos = (int) $bro->npp + 1;
            }

            // 3) освободить место у нового родителя
            Article::where('parentId', $newParentId)
                ->where('npp', '>=', $pos)
                ->lockForUpdate()
                ->increment('npp');

            // 4) переместить узел
            $a->parentId = $newParentId;
            $a->npp      = $pos;
            $a->save();

            // 5) старый родитель: если детей не осталось — снять флаг directory
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

            // 6) новый родитель — точно директория
            if ($parent = Article::lockForUpdate()->find($newParentId)) {
                if (!$parent->directory) {
                    $parent->directory = true;
                    $parent->save();
                }
            }

            return $a;
        });
    }


    private function getArticle(int $id): array
    {
        $article = Article::find($id);
        if ($article) {
            return $article->toArray();
        }
        throw new \InvalidArgumentException("id=$id not found");
    }

    // добавить запись. Определяет макс. номер по порядку и добовляет в конец.
    private function createNew(int $id): array
    {
        return DB::transaction(function () use ($id) {
            // Родитель обязателен
            $parent = $this->getArticle($id); // бросает, если не найден

            // Блокируем ряд для согласованной нумерации (борьба с гонками)
            $maxNpp = DB::table('articles')
                ->where('parentId', $id)
                ->lockForUpdate()
                ->max('npp') ?? 0;

            // Создаём запись в конце
            $article = new Article();
            $article->name     = 'art_' . (int) round(microtime(true) * 1000);
            $article->title    = $article->name;
            $article->parentId = $id;
            $article->npp      = $maxNpp + 1;
            $article->directory      = false;
            $article->controller = readDefaultController();

            if (!$article->save()) {
                throw new \InvalidArgumentException("Error: creation failed");
            }

            // Обновляем флаг директории у родителя
            if (empty($parent['directory'])) {
                Article::where('id', $id)->update(['directory' => true]);
            }

            return $article->toArray();
        });
    }


    // Удаляет. Удаляет детей. У оставшихся братьев пересчитывает номер по порядку. 
    // если у родителя не осталось детей parent-directory = 0
    public function deleteRec(int $id): array
    {
        if ($id === 1) {
            throw new \InvalidArgumentException('Удалять рут нельзя ' . $id .   "\n");
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

        // Родитель до удаления (может быть null)
        $parent = Article::find($article->parentId);

        // Рекурсивно удаляем детей
        $childIds = Article::where('parentId', $id)->pluck('id');
        foreach ($childIds as $childId) {
            $this->deleteRecNoTx((int)$childId);
        }

        // Сдвигаем npp у братьев после текущего
        Article::where('parentId', $article->parentId)
            ->where('npp', '>', $article->npp)
            ->decrement('npp');

        // Удаляем текущую запись
        deleteMpro($article->toArray());
        $article->delete();

        // Если у родителя не осталось детей — снимаем флаг directory
        if ($parent && Article::where('parentId', $parent->id)->doesntExist()) {
            $parent->directory = false; // cast boolean в модели
            $parent->save();
        }

        return $parent ? $parent->toArray() : [];
    }

    private function makeHeTree(int $id): array
    {
        $tree  = [];
        $curId = $id;

        // максимально допустимая глубина = количество статей
        $maxDepth = Article::count();
        $i        = 0;

        while (true) {
            if (++$i > $maxDepth) {
                throw new \InvalidArgumentException("Слишком глубокая или зацикленная иерархия (id={$id})");
            }

            // текущий узел
            $article = Article::where('id', $curId)
                ->first(['id', 'title as text', 'npp', 'parentId', 'menuOn', 'isRoute', 'directory']);

            if (!$article) {
                throw new \InvalidArgumentException("Статья id={$curId} не найдена");
            }

            $node = $article->toArray();

            // дети
            $node['children'] = Article::where('parentId', $curId)
                ->orderBy('npp')
                ->orderBy('id')
                ->get(['id', 'title as text', 'npp', 'parentId', 'menuOn', 'isRoute', 'directory'])
                ->toArray();

            if (empty($tree)) {
                $tree = $node;
            } else {
                foreach ($node['children'] as $key => $val) {
                    if ((int)$val['id'] === (int)$tree['id']) {
                        $node['children'][$key] = $tree;
                        break;
                    }
                }
                $tree = $node;
            }

            $curId = (int)$node['parentId'];
            if ($curId === 0) {
                break; // дошли до корня
            }
        }

        return [$tree];
    }


    private function getParents(int $id): array
    {
        if ($id == 1) {
            return [];
        }

        $parents = [];

        $article = Article::find($id);

        if (!$article) {
            throw new \InvalidArgumentException("id={$id} не найден");
        }
        $repeat = true;
        while ($repeat) {
            $parent = Article::find($article->parentId);
            if (!$parent) {
                throw new \InvalidArgumentException("Родитель id={$article->parentId} не найден");
            }

            $parents[] = [
                'id'    => $parent->id,
                'name'  => $parent->name,
                'title' => $parent->title,
                'npp' => $parent->npp,
            ];
            $article = $parent;
            $repeat = $article->id != 1;
        }

        return $parents;
    }

    private function getChildrens(int $id): array
    {
        // убеждаемся, что родитель существует
        if (!Article::find($id)) {
            throw new \InvalidArgumentException("id={$id} не найден");
        }

        // берём только нужные поля и сортируем по npp 
        return Article::where('parentId', $id)
            ->orderBy('npp')
            ->get(['id', 'title as text', 'npp', 'parentId', 'menuOn', 'isRoute', 'directory'])
            ->toArray();
    }

    private function getBrothers(int $id): array
    {
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
