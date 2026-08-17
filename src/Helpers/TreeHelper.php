<?php
// namespace MagicProSrc;
// use App\Models\Article; // или ваша модель
use MagicProDatabaseModels\Article;

class TreeHelper
{

    public static function imageType(string $text): string
    {
        $text = match (strtolower(pathinfo($text, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => '',
        };

        return $text;
    }

    //
    public static function trimAndCutText(string $text, int $limit = 0): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strip_tags($text);
        $text = preg_replace('/[\p{So}\p{Cn}]/u', '', $text);
        $text = preg_replace('/\s+/u', ' ', $text); // \r \n \t и лишние пробелы
        $text = trim($text);

        if ($limit > 0 && mb_strlen($text) > $limit) {
            $text = mb_substr($text, 0, $limit + 1);
            $text = preg_replace('/\s+\S*$/u', '', $text);
        }

        return $text;
    }
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
            ->select('id', 'title', 'name', 'menuOn', 'updated_at', 'npp')
            ->where('parentId', $artId)
            ->where('menuOn', true)
            ->orderBy('npp')
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
            ->orderBy('npp')
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
