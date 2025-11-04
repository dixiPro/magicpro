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
            ->select('id', 'title', 'name', 'menuOn')
            ->where('parentId', $artId)
            ->get()
            ->toArray();
    }

    //  Получить всех потомков записи.
    public static function getChildrenByName(string $name): array
    {
        $id = Article::select('id')->where('name', $name)->value('id');

        $result = Article::query()
            ->select('id', 'title', 'name', 'menuOn')
            ->where('parentId', $id)
            ->get()
            ->toArray();

        return $result;
    }

    // 
    public static function getPathToRoot(int $id): array
    {
        // TODO: идти вверх по parentId до root
        return ['rootPath' => 'путь на верх'];
    }
}
