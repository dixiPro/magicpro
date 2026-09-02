<?php

namespace MagicProSrc\Cleanup;

use Illuminate\Support\Facades\DB;
use MagicProDatabaseModels\Article;
use MagicProSrc\Import\Snapshots;

require_once __DIR__ . '/../../admin/controller/MagicProBuilder.php';

/**
 * Looks the article tree over and repairs it. One button, no questions.
 *
 * Everything found is fixed on the spot and in silence: the talk with the human
 * happens afterwards, over the report — an html file that can be opened a month
 * later. What the repair did roughly, a person corrects by hand.
 *
 * The base is written straight through the query builder, not through the
 * model. That is the one place in MagicPro where it is right: the model refuses
 * to save an article whose name is broken or whose parent is missing, and those
 * are exactly the articles that have to be saved here. What the model would
 * have kept in step — the generated blade and controller — this run puts in
 * order itself, in points 9 to 11.
 *
 * The order of the run is not the order of the points. First the tree is made a
 * tree (parents, rings), then the names, then the fields, and only then the
 * files: a renamed article needs its old file gone and a new one written, and
 * that can only be told once the names are final.
 */
class ArticleCheck
{
    /** The root: its id, its name and its place are nailed down. */
    private const ROOT = 1;

    /** Points of the report, in the order they are printed. */
    public const POINTS = [
        1  => 'cleanup_p1',
        2  => 'cleanup_p2',
        3  => 'cleanup_p3',
        4  => 'cleanup_p4',
        5  => 'cleanup_p5',
        6  => 'cleanup_p6',
        7  => 'cleanup_p7',
        8  => 'cleanup_p8',
        9  => 'cleanup_p9',
        10 => 'cleanup_p10',
        11 => 'cleanup_p11',
    ];

    /** Points whose repair changes the address of an article. */
    public const LOUD = [6, 7];

    /** What was repaired, by point. */
    private array $rows = [];

    /**
     * The whole run. Answers with the name of the report and of the snapshot.
     *
     * @return array{report: string, snapshot: string, found: int}
     */
    public static function run(): array
    {
        $check = new self();

        // Before the repair, always: it is done in silence, and there would be
        // nothing to go back to otherwise.
        $snapshot = Snapshots::save('cleanup_' . date('Y-m-d_H-i'));

        $check->orphans();      // 2
        $check->rings();        // 3
        $check->names();        // 6
        $check->doubles();      // 7
        $check->root();         // 8
        $check->routeParams();  // 1
        $check->npp();          // 4
        $check->directory();    // 5

        $check->filesWithoutArticle(); // 9
        $check->articlesWithoutView(); // 10
        $check->extraControllers();    // 11

        return [
            'report'   => CleanupReport::write($check->rows, $snapshot),
            'snapshot' => $snapshot,
            'found'    => $check->found(),
        ];
    }

    // ==================================================================
    //                          the tree
    // ==================================================================

    /**
     * 2. A parent that is not there.
     *
     * `parentId = 0` counts too: only the root is allowed to stand on nothing,
     * and an article outside the tree is invisible in the editor — it shows in
     * the list and nowhere else.
     */
    private function orphans(): void
    {
        $ids = DB::table('articles')->pluck('id')->all();
        $ids = array_flip($ids);

        $rows = DB::table('articles')
            ->where('id', '!=', self::ROOT)
            ->get(['id', 'name', 'parentId']);

        foreach ($rows as $row) {
            if ($row->parentId > 0 && isset($ids[$row->parentId])) {
                continue;
            }

            $this->toRoot(2, $row, (string) $row->parentId);
        }
    }

    /**
     * 3. A ring: `a` waits for `b`, `b` waits for `a`.
     *
     * The model forbids only a link to itself, so a ring gets in through a
     * direct write. Everything the root cannot be reached from is either in a
     * ring or hangs behind one; the ring is broken at its oldest article — the
     * smallest id — and that one goes to the root.
     */
    private function rings(): void
    {
        // There can be more than one ring, and every one takes its own pass.
        for ($pass = 0; $pass < 100; $pass++) {
            $parent = DB::table('articles')->pluck('parentId', 'id')->all();

            $lost = $this->unreachable($parent);

            if (! $lost) {
                return;
            }

            $ring = $this->ring($lost, $parent);

            if (! $ring) {
                return;
            }

            $id  = min($ring);
            $row = DB::table('articles')->where('id', $id)->first(['id', 'name', 'parentId']);

            $this->toRoot(3, $row, (string) $row->parentId);
        }
    }

    /** @return array<int, int> ids the root cannot be reached from */
    private function unreachable(array $parent): array
    {
        $lost = [];

        foreach ($parent as $id => $up) {
            $seen = [];
            $at   = (int) $id;

            while ($at && $at !== self::ROOT && ! isset($seen[$at])) {
                $seen[$at] = true;
                $at        = (int) ($parent[$at] ?? 0);
            }

            if ($at !== self::ROOT) {
                $lost[] = (int) $id;
            }
        }

        return $lost;
    }

    /** @return array<int, int> ids of one ring, walking up from a lost article */
    private function ring(array $lost, array $parent): array
    {
        $seen = [];
        $at   = (int) $lost[0];

        while ($at && ! isset($seen[$at])) {
            $seen[$at] = true;
            $at        = (int) ($parent[$at] ?? 0);
        }

        if (! $at) {
            return [];
        }

        // We came where we already were: the ring starts here.
        $ring  = [];
        $start = $at;

        do {
            $ring[] = $at;
            $at     = (int) ($parent[$at] ?? 0);
        } while ($at && $at !== $start);

        return $ring;
    }

    private function toRoot(int $point, $row, string $was): void
    {
        DB::table('articles')->where('id', $row->id)->update([
            'parentId' => self::ROOT,
            'npp'      => $this->lastNpp(self::ROOT),
        ]);

        $this->note($point, $row->id, $row->name, 'parentId ' . $was, 'parentId ' . self::ROOT);
    }

    private function lastNpp(int $parentId): int
    {
        return (int) DB::table('articles')->where('parentId', $parentId)->max('npp') + 1;
    }

    // ==================================================================
    //                          the names
    // ==================================================================

    /** 6. Letters that have no business in a name. */
    private function names(): void
    {
        $rows = DB::table('articles')->get(['id', 'name']);

        foreach ($rows as $row) {
            if (preg_match('/^[A-Za-z0-9_-]+$/', (string) $row->name)) {
                continue;
            }

            $this->rename(6, $row);
        }
    }

    /** 7. Two articles under one name: the model forbids it, a direct write does not. */
    private function doubles(): void
    {
        $seen = [];

        foreach (DB::table('articles')->orderBy('id')->get(['id', 'name']) as $row) {
            $name = (string) $row->name;

            if (! isset($seen[$name])) {
                $seen[$name] = true;

                continue;
            }

            $this->rename(7, $row);
        }
    }

    /**
     * A repaired name always gets the time appended.
     *
     * So it lands on nothing, and the list of articles says at a glance that
     * the name was repaired rather than chosen.
     */
    private function rename(int $point, $row): void
    {
        $clean = (string) preg_replace('/[^A-Za-z0-9_-]/', '', (string) $row->name);
        $clean = $clean !== '' ? $clean : 'art';

        $name = $clean . '_' . $this->stamp();

        DB::table('articles')->where('id', $row->id)->update(['name' => $name]);

        $this->note($point, $row->id, $name, (string) $row->name, $name);
    }

    /** Milliseconds, and never the same twice in one run. */
    private function stamp(): string
    {
        static $last = 0;

        $now = (int) round(microtime(true) * 1000);

        $last = $now > $last ? $now : $last + 1;

        return (string) $last;
    }

    // ==================================================================
    //                          the fields
    // ==================================================================

    /** 8. The root stands where it stands and is called what it is called. */
    private function root(): void
    {
        $row = DB::table('articles')->where('id', self::ROOT)->first(['id', 'name', 'parentId', 'npp']);

        if (! $row) {
            return;
        }

        if ($row->name === 'root' && (int) $row->parentId === 0 && (int) $row->npp === 0) {
            return;
        }

        DB::table('articles')->where('id', self::ROOT)->update([
            'name'     => 'root',
            'parentId' => 0,
            'npp'      => 0,
        ]);

        $this->note(
            8,
            self::ROOT,
            'root',
            $row->name . ', parentId ' . $row->parentId . ', npp ' . $row->npp,
            'root, parentId 0, npp 0',
        );
    }

    /**
     * 1. `routeParams` without `useController`.
     *
     * Nothing is broken by it — the builder reads the key as `?? false` — but
     * the list of articles paints a red mark, and a real trouble stops being
     * visible in that mark. That is how everything is born that was made by the
     * «create» button and never saved from the editor.
     */
    private function routeParams(): void
    {
        foreach (DB::table('articles')->get(['id', 'name', 'routeParams']) as $row) {
            $params = json_decode((string) $row->routeParams, true);

            if (is_array($params) && array_key_exists('useController', $params)) {
                continue;
            }

            $was = is_array($params) ? $params : [];

            $was['useController'] = false;

            DB::table('articles')->where('id', $row->id)->update(['routeParams' => json_encode($was)]);

            $this->note(1, $row->id, $row->name, (string) ($row->routeParams ?? 'null'), json_encode($was));
        }
    }

    /** 4. Numbers of the brothers: no doubles and no holes, order kept. */
    private function npp(): void
    {
        $parents = DB::table('articles')
            ->where('id', '!=', self::ROOT)
            ->distinct()
            ->pluck('parentId');

        foreach ($parents as $parentId) {
            $rows = DB::table('articles')
                ->where('parentId', $parentId)
                ->orderBy('npp')
                ->orderBy('id')
                ->get(['id', 'name', 'npp']);

            $npp = 0;

            foreach ($rows as $row) {
                $npp++;

                if ((int) $row->npp === $npp) {
                    continue;
                }

                DB::table('articles')->where('id', $row->id)->update(['npp' => $npp]);

                $this->note(4, $row->id, $row->name, 'npp ' . $row->npp, 'npp ' . $npp);
            }
        }
    }

    /** 5. A leaf with children, a folder without them. */
    private function directory(): void
    {
        $children = DB::table('articles')
            ->select('parentId', DB::raw('count(*) as total'))
            ->groupBy('parentId')
            ->pluck('total', 'parentId')
            ->all();

        foreach (DB::table('articles')->get(['id', 'name', 'directory']) as $row) {
            $has = ! empty($children[$row->id]);

            if ((bool) $row->directory === $has) {
                continue;
            }

            DB::table('articles')->where('id', $row->id)->update(['directory' => $has]);

            $this->note(5, $row->id, $row->name, $row->directory ? 'directory' : 'leaf', $has ? 'directory' : 'leaf');
        }
    }

    // ==================================================================
    //                          the files
    // ==================================================================

    /** 9. A blade or a controller no article answers for. */
    private function filesWithoutArticle(): void
    {
        $names = array_flip(DB::table('articles')->pluck('name')->all());

        foreach ($this->files(MAGIC_VIEW_DIR, '.blade.php') as $name => $path) {
            if (isset($names[$name])) {
                continue;
            }

            unlink($path);

            $this->note(9, null, $name, basename($path), '');
        }

        foreach ($this->files(MAGIC_CONTROLLER_DIR, '.php') as $name => $path) {
            if (isset($names[$name])) {
                continue;
            }

            unlink($path);

            $this->note(9, null, $name, basename($path), '');
        }
    }

    /** 10. An article whose blade is not there. Only what is missing. */
    private function articlesWithoutView(): void
    {
        foreach (Article::orderBy('id')->get() as $article) {
            if (is_file(MAGIC_VIEW_DIR . '/' . $article->name . '.blade.php')) {
                continue;
            }

            \MagicProAdminControllers\createMpro($article->toArray());

            $this->note(10, $article->id, $article->name, '', $article->name . '.blade.php');
        }
    }

    /** 11. A controller of an article that does not use one. */
    private function extraControllers(): void
    {
        foreach (Article::orderBy('id')->get() as $article) {
            $path = MAGIC_CONTROLLER_DIR . '/' . $article->name . '.php';

            if (($article->routeParams['useController'] ?? false) || ! is_file($path)) {
                continue;
            }

            unlink($path);

            $this->note(11, $article->id, $article->name, basename($path), '');
        }
    }

    /**
     * Files of a directory by the name of the article they belong to.
     *
     * @return array<string, string> name => path
     */
    private function files(string $dir, string $suffix): array
    {
        if (! is_dir($dir)) {
            return [];
        }

        $files = [];

        foreach (scandir($dir) ?: [] as $file) {
            if (! str_ends_with($file, $suffix)) {
                continue;
            }

            $files[substr($file, 0, -strlen($suffix))] = $dir . '/' . $file;
        }

        return $files;
    }

    // ==================================================================
    //                          the report
    // ==================================================================

    private function note(int $point, ?int $id, ?string $name, string $was, string $now): void
    {
        $this->rows[$point][] = [
            'id'   => $id,
            'name' => (string) $name,
            'was'  => $was,
            'now'  => $now,
        ];
    }

    private function found(): int
    {
        return array_sum(array_map('count', $this->rows));
    }
}
