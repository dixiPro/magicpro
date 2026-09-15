<?php

namespace MagicProSrc\Archive;

use Illuminate\Support\Facades\Auth;
use MagicProDatabaseModels\Article;
use MagicProDatabaseModels\ArticleVersion;
use MagicProDatabaseModels\MagicProUser;
use MagicProSrc\Config\MagicGlobals;

/**
 * The archive of article versions.
 *
 * Every save through the articles api leaves a copy of the saved article here,
 * so a save that wiped a blade can be taken back. The one who saves may be a
 * person in the browser or an agent through МСП: both go through `saveById`,
 * and both land here.
 *
 * What the archive is not: the import knows nothing about it and it knows
 * nothing about the import. The import saves hundreds of articles at once, and
 * a copy of the whole site in the archive after every import would say nothing
 * about anybody's work.
 */
class ArticleArchive
{
    /** How many days to keep, when the setting says nothing. */
    private const DAYS = 30;

    /**
     * Puts the saved state of an article into the archive.
     *
     * Called after the article is written, with the article as it now is: the
     * archive holds saved states, so its newest version and the article itself
     * are the same thing.
     *
     * A save that changed nothing writes nothing: three saves in a row without
     * an edit are one state, not three.
     */
    public static function add(array $article, ?int $userId = null): void
    {
        $articleId = (int) ($article['id'] ?? 0);

        if ($articleId === 0) {
            return;
        }

        $data = self::pack($article);

        if ($data === self::lastData($articleId)) {
            return;
        }

        ArticleVersion::create([
            'userId'    => $userId ?? self::currentUserId(),
            'articleId' => $articleId,
            'data'      => $data,
        ]);

        self::cleanup($articleId);
    }

    /**
     * The versions of one article, newest first, without the articles
     * themselves: the list shows dates and authors, and a whole blade in every
     * row would travel for nothing.
     */
    public static function versions(int $articleId): array
    {
        $rows = ArticleVersion::where('articleId', $articleId)
            ->orderByDesc('id')
            ->get(['id', 'userId', 'created_at']);

        // the names of the authors in one query: the list shows people, and an
        // id says nothing to the one who is looking for his own save
        $names = MagicProUser::whereIn('id', $rows->pluck('userId')->unique())
            ->pluck('name', 'id');

        return $rows->map(fn($row) => [
            'id'         => (int) $row->id,
            'userId'     => (int) $row->userId,
            'user'       => (string) ($names[$row->userId] ?? ''),
            'created_at' => optional($row->created_at)->format('Y-m-d H:i:s'),
        ])->all();
    }

    /** One version unpacked, as the article was at that moment. */
    public static function get(int $versionId): array
    {
        $row = ArticleVersion::find($versionId);

        if (! $row) {
            throw new \Exception('version not found: ' . $versionId);
        }

        return [
            'id'         => (int) $row->id,
            'userId'     => (int) $row->userId,
            'articleId'  => (int) $row->articleId,
            'created_at' => optional($row->created_at)->format('Y-m-d H:i:s'),
            'article'    => self::unpack((string) $row->data),
        ];
    }

    /**
     * How many days of history are kept. Zero means forever.
     */
    public static function days(): int
    {
        return max(0, (int) (MagicGlobals::$INI['ARCHIVE_DAYS'] ?? self::DAYS));
    }

    /**
     * Throws away what is older than the setting allows.
     *
     * Kept by time and not by count: a day of work on one article is worth
     * keeping whole, and an article nobody touches costs nothing anyway.
     *
     * The newest version of an article is never swept, however old it is. It is
     * the state the article is in, and an archive that loses it turns an old
     * untouched article into one with no history at all.
     */
    public static function cleanup(?int $articleId = null): int
    {
        $days = self::days();

        if ($days === 0) {
            return 0;
        }

        $query = ArticleVersion::where('created_at', '<', now()->subDays($days));

        if ($articleId !== null) {
            $query->where('articleId', $articleId);
        }

        $newest = ArticleVersion::selectRaw('max(id) as id')
            ->groupBy('articleId')
            ->pluck('id')
            ->all();

        if ($newest) {
            $query->whereNotIn('id', $newest);
        }

        return (int) $query->delete();
    }

    /**
     * Versions of articles that are gone.
     *
     * They are kept on purpose — a deleted article is exactly the case when
     * history is needed — so they leave only when somebody presses the button
     * on the setup page.
     */
    public static function deletedCount(): int
    {
        return (int) ArticleVersion::whereNotIn('articleId', Article::query()->select('id'))->count();
    }

    public static function purgeDeleted(): int
    {
        return (int) ArticleVersion::whereNotIn('articleId', Article::query()->select('id'))->delete();
    }

    /**
     * The article as it goes into the base: plain json, not compressed.
     *
     * Compression was measured and dropped. The median blade of a site is half
     * a kilobyte, so gzip would save tens of megabytes in the worst case — and
     * take away reading a version by eye and looking through the archive with a
     * plain `like`. A binary column would cost more than that: on postgres a
     * bytea needs its own writing and its own reading, past Eloquent, while text
     * travels the same way in every driver.
     */
    private static function pack(array $article): string
    {
        $json = json_encode($article, JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new \Exception('article cannot be encoded to json');
        }

        return $json;
    }

    private static function unpack(string $data): array
    {
        $article = json_decode($data, true);

        if (! is_array($article)) {
            throw new \Exception('the version is damaged: it is not json');
        }

        return $article;
    }

    /** The packed body of the newest version, to compare a save against. */
    private static function lastData(int $articleId): ?string
    {
        $row = ArticleVersion::where('articleId', $articleId)
            ->orderByDesc('id')
            ->first(['data']);

        return $row ? (string) $row->data : null;
    }

    /**
     * Who is saving.
     *
     * Zero when nobody is logged in: a save from the console has no author, and
     * an invented one would be worse than none.
     */
    private static function currentUserId(): int
    {
        $user = Auth::guard('magic')->user();

        return $user ? (int) $user->id : 0;
    }
}
