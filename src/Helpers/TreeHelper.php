<?php
// namespace MagicProSrc;
// use App\Models\Article; // или ваша модель
use MagicProDatabaseModels\Article;

class TreeHelper
{
    // продитель
    public static function getParent(int $id): array
    {
        // TODO: запрос к БД, вернуть parent
        return ['parent' => 'родитель'];
    }


    //  Получить всех потомков записи.
    public static function getChildrenById(int $artId): array
    {
        return Article::query()
            ->select('id', 'title', 'name', 'menuOn', 'updated_at')
            ->where('parentId', $artId)
            ->where('menuOn', true)
            ->get()
            ->toArray();
    }

    //  Получить всех потомков записи.
    public static function getChildrenByName(string $name): array
    {
        $id = Article::select('id')->where('name', $name)->value('id');

        $result = Article::query()
            ->select('id', 'title', 'name', 'menuOn', 'updated_at')
            ->where('parentId', $id)
            ->where('menuOn', true)
            ->get()
            ->toArray();
        return $result;
    }

    public static function getArtByName(string $name): array
    {
        $result = Article::query()
            ->where('name', $name)
            ->select('*')
            ->get()
            ->toArray();
        return $result[0] ?? [];
    }

    // 
    public static function getPathToRootById(int $id): array
    {
        $path = [];
        $count = 0;


        try {
            while (++$count < 100) {
                $article = Article::findOrFail($id);
                array_unshift($path, $article->name);

                if ($article->parentId == 0) {
                    break;
                }
                $id = $article->parentId;
            }
        } catch (\Throwable) {
            return $path;
        }
        return $path;
    }
}
