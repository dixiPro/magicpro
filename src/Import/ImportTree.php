<?php

namespace MagicProSrc\Import;

use Illuminate\Support\Facades\DB;
use MagicProDatabaseModels\Article;

require_once __DIR__ . '/../../admin/controller/MagicProBuilder.php';

/**
 * Import of an article tree from json.
 *
 * Two trees meet here. The **birch** is the file: a tree of articles that came
 * from an export, of this site or of another one. The **oak** is the site.
 *
 * The work always goes in two passes. The first one changes nothing: it reads
 * the file, checks it and tells what is worth telling — what is wrong with the
 * file, and what the site loses. The second pass repeats those checks and does
 * the work. Nothing is kept between the passes: the file lives in the browser
 * and comes with every call. A thousand articles are counted in seconds, and
 * there is nothing here worth saving.
 *
 * The report is short on purpose. «The article X will be raised from the file»
 * is a line nobody reads: that is what an import is. What has to be read is an
 * error and a collision — the place where the site loses something.
 *
 * Names are the only thing the two trees agree on. Ids of the file mean nothing
 * on this site, and the file does not carry them: a parent is written by name.
 *
 * Where the two trees have the same name, the birch wins: the article of the
 * oak is deleted with its whole subtree, and the one from the file takes its
 * place. That is why the first pass exists — a subtree is lost silently
 * otherwise, and what has to be kept is prepared by hand, before the import.
 *
 * The base is written in one transaction. Blade views and controllers are
 * generated after the commit, in one run over the whole tree that does not stop
 * at the first trouble: what could not be written is reported by name. Half-generated
 * files after a failure would be worse than none.
 */
class ImportTree
{
    /** The birch is hung onto the oak; the oak keeps everything else. */
    public const PARTIAL = 'partial';

    /** The oak is wiped and the birch takes its place. */
    public const FULL = 'full';

    /** The root of the oak: it cannot be deleted and cannot be renamed. */
    private const ROOT = 1;

    /** What an article takes from the file. Anything else in a row is ignored. */
    private const FIELDS = [
        'npp',
        'name',
        'title',
        'controller',
        'body',
        'directory',
        'menuOn',
        'isRoute',
        'routeParams',
    ];

    /** Rows of the file by name. */
    private array $rows = [];

    /** Names, parents before children. */
    private array $order = [];

    /** Names whose parent is not in the file — they are hung onto the oak. */
    private array $tops = [];

    /** Id of the oak article a top is hung onto, by name of the top. */
    private array $hook = [];

    /** Ids of the oak that the import deletes. */
    private array $drop = [];

    /** What will happen, line by line. */
    private array $report = [];

    private bool $ok = true;

    /** First pass: reads, checks, tells. Changes nothing. */
    public static function check(string $text, string $mode): array
    {
        $tree = new self();

        $tree->plan($text, $mode);

        return ['ok' => $tree->ok, 'report' => $tree->report];
    }

    /** Second pass: the same checks, then the work. */
    public static function run(string $text, string $mode): array
    {
        $tree = new self();

        $tree->plan($text, $mode);

        if (! $tree->ok) {
            return ['ok' => false, 'report' => $tree->report];
        }

        try {
            $gone = DB::transaction(fn () => $mode === self::FULL ? $tree->applyFull() : $tree->applyPartial());
        } catch (\Throwable $e) {
            // The base is rolled back and no file was touched — generation
            // happens only after a commit. The run over the tree is insurance,
            // not repair.
            self::generate([]);

            throw $e;
        }

        $failed = self::generate($gone);

        foreach ($tree->report as &$line) {
            $line['done'] = true;
        }

        unset($line);

        // База уже приняла дерево — это свершилось и не отменяется. Поэтому
        // ответ остаётся успешным, а неудавшиеся файлы идут отдельными строками
        // отчёта: повторять импорт из-за них не надо и вредно, надо чинить эти
        // статьи.
        foreach ($failed as $trouble) {
            $tree->report[] = [
                'code'   => 'file_failed',
                'params' => $trouble,
                'error'  => true,
                'done'   => false,
            ];
        }

        return ['ok' => true, 'report' => $tree->report];
    }

    // ==================================================================
    //                             the plan
    // ==================================================================

    private function plan(string $text, string $mode): void
    {
        $rows = json_decode($text, true);

        if (! is_array($rows)) {
            $this->fail('json');

            return;
        }

        if (! array_is_list($rows)) {
            $this->fail('not_list');

            return;
        }

        if (! $rows) {
            $this->fail('empty');

            return;
        }

        foreach ($rows as $i => $row) {
            $this->readRow($row, $i + 1);
        }

        if (! $this->ok) {
            return;
        }

        // The root of the oak is id = 1 and its name is nailed down. A file
        // that carries root is a file of the whole site, and hanging a whole
        // site onto a corner of another one has no meaning.
        if ($mode === self::PARTIAL && isset($this->rows['root'])) {
            $this->fail('root_partial');

            return;
        }

        $this->order();

        if (! $this->ok) {
            return;
        }

        $mode === self::FULL ? $this->planFull() : $this->planPartial();
    }

    private function readRow($row, int $line): void
    {
        if (! is_array($row)) {
            $this->fail('row', ['line' => $line]);

            return;
        }

        $name = trim((string) ($row['name'] ?? ''));

        if ($name === '') {
            $this->fail('name', ['line' => $line]);

            return;
        }

        if (! preg_match('/^[A-Za-z0-9_-]+$/', $name)) {
            $this->fail('name_bad', ['name' => $name, 'line' => $line]);

            return;
        }

        if (isset($this->rows[$name])) {
            $this->fail('dup', ['name' => $name]);

            return;
        }

        $row['name'] = $name;

        $this->rows[$name] = $row;
    }

    /**
     * Parents before children, and the tops picked out.
     *
     * A top is a row whose parent is not in the file: it is the one hung onto
     * the oak. There can be more than one — a file is not obliged to hold a
     * single subtree.
     *
     * What never gets its turn is a ring: `a` waits for `b`, `b` waits for `a`.
     * The file is refused whole, there is nothing to salvage in it.
     */
    private function order(): void
    {
        $placed = [];

        foreach ($this->rows as $name => $row) {
            $parent = (string) ($row['parentName'] ?? '');

            if ($parent === '' || ! isset($this->rows[$parent])) {
                $this->tops[]  = $name;
                $this->order[] = $name;
                $placed[$name] = true;
            }
        }

        $left = count($this->rows) - count($this->order);

        while ($left > 0) {
            $moved = 0;

            foreach ($this->rows as $name => $row) {
                if (isset($placed[$name])) {
                    continue;
                }

                if (isset($placed[(string) ($row['parentName'] ?? '')])) {
                    $this->order[] = $name;
                    $placed[$name] = true;
                    $moved++;
                    $left--;
                }
            }

            if ($moved === 0) {
                $this->fail('ring');

                return;
            }
        }
    }

    private function planPartial(): void
    {
        // Everything the oak loses: an article of the same name and all of its
        // subtree. Counted first — a hook may itself stand inside a subtree
        // that is about to go.
        $gone = [];

        foreach (array_keys($this->rows) as $name) {
            $id = (int) Article::where('name', $name)->value('id');

            if (! $id || $id === self::ROOT) {
                continue;
            }

            $this->note('replace', ['name' => $name]);

            foreach ($this->subtree($id) as $dropId) {
                $gone[$dropId] = true;
            }
        }

        $this->drop = array_keys($gone);

        // What leaves with them and does not come back from the file. The
        // parent of such an article is always inside the same set — the one
        // that is not is the article of the same name, and it does come back.
        $rows  = Article::whereIn('id', $this->drop)->get();
        $names = $rows->pluck('name', 'id');

        foreach ($rows as $article) {
            if (isset($this->rows[$article->name])) {
                continue;
            }

            $this->note('drop', [
                'name'   => $article->name,
                'parent' => (string) ($names[$article->parentId] ?? ''),
            ]);
        }

        foreach ($this->tops as $name) {
            $parentName = (string) ($this->rows[$name]['parentName'] ?? '');
            $parentId   = $parentName === ''
                ? 0
                : (int) Article::where('name', $parentName)->value('id');

            if ($parentId && ! isset($gone[$parentId])) {
                $this->hook[$name] = $parentId;

                continue;
            }

            // The file names a parent, and the parent is not there — it was
            // never on this site, or the import itself deletes it. The birch
            // lands at the root instead, and that is worth a line: it is not
            // where the file said it goes.
            $this->hook[$name] = self::ROOT;

            if ($parentName !== '') {
                $this->note('attach_root', ['name' => $name, 'parent' => $parentName]);
            }
        }
    }

    /**
     * Nothing to tell. A full import deletes the whole oak and raises the whole
     * birch — listing that article by article says the same thing as the words
     * «full import», only longer.
     */
    private function planFull(): void
    {
    }

    // ==================================================================
    //                             the work
    // ==================================================================

    /** @return array<int, string> names whose generated files have to go */
    private function applyPartial(): array
    {
        $gone = $this->wipe($this->drop);

        foreach ($this->order as $name) {
            $row        = $this->rows[$name];
            $parentName = (string) ($row['parentName'] ?? '');

            if (isset($this->rows[$parentName])) {
                $parentId = (int) Article::where('name', $parentName)->value('id');
                $npp      = (int) ($row['npp'] ?? 0);
            } else {
                // The top is hung onto the oak as the last leaf: its npp comes
                // from another tree and would sit in the middle of the numbers
                // that are already there.
                $parentId = $this->hook[$name] ?? self::ROOT;
                $npp      = (int) Article::where('parentId', $parentId)->max('npp') + 1;
            }

            $this->create($row, $parentId, $npp);
        }

        return $gone;
    }

    /** @return array<int, string> names whose generated files have to go */
    private function applyFull(): array
    {
        $gone = $this->wipe(
            Article::where('id', '!=', self::ROOT)->pluck('id')->all()
        );

        foreach ($this->order as $name) {
            $row = $this->rows[$name];

            // The root of the oak cannot be deleted, so the root of the birch
            // does not arrive — it pours into the one that is there.
            if ($name === 'root') {
                $root = Article::find(self::ROOT);
                $root->update($this->fields($row, ['name', 'npp']));

                continue;
            }

            $parentName = (string) ($row['parentName'] ?? '');

            $parentId = isset($this->rows[$parentName])
                ? (int) Article::where('name', $parentName)->value('id')
                : self::ROOT;

            // Nothing of the oak is left to collide with, so the numbers of the
            // file are taken as they are.
            $this->create($row, $parentId, (int) ($row['npp'] ?? 0));
        }

        return $gone;
    }

    private function create(array $row, int $parentId, int $npp): void
    {
        $article = new Article();

        $article->fill($this->fields($row, ['npp']));

        $article->parentId = $parentId;
        $article->npp      = $npp;

        $article->save();
    }

    /**
     * Deletes the articles and answers with their names: the files they
     * generated are removed by name, and after the delete there is nobody left
     * to ask.
     *
     * @param  array<int, int>  $ids
     * @return array<int, string>
     */
    private function wipe(array $ids): array
    {
        $ids = array_values(array_filter($ids, fn ($id) => (int) $id !== self::ROOT));

        if (! $ids) {
            return [];
        }

        $names = Article::whereIn('id', $ids)->pluck('name')->all();

        Article::whereIn('id', $ids)->delete();

        return $names;
    }

    /**
     * Ids of an article with everything under it.
     *
     * Level by level, and an id already taken is never walked again: a ring in
     * `parentId` is possible — the model forbids only a link to itself.
     *
     * @return array<int, int>
     */
    private function subtree(int $id): array
    {
        $ids   = [$id];
        $level = [$id];

        while ($level) {
            $level = Article::whereIn('parentId', $level)
                ->whereNotIn('id', $ids)
                ->pluck('id')
                ->all();

            $ids = array_merge($ids, $level);
        }

        return $ids;
    }

    /**
     * Views and controllers of the whole tree, in one run, after the commit.
     *
     * What was deleted is removed by name first: regeneration writes files for
     * the articles that exist and knows nothing about the ones that are gone.
     *
     * @param  array<int, string>  $gone
     */
    /**
     * Файлы статей после коммита.
     *
     * Обход не останавливается на первой беде. Раньше исключение с одной статьи
     * прерывало проход, и сайт оставался в смеси: база уже новая, часть файлов
     * новая, часть старая, часть удалена. Наружу при этом уходила ошибка, и
     * человек повторял импорт, который на самом деле уже применился.
     *
     * Теперь остальные статьи всё равно получают свои файлы, а список тех, кому
     * не досталось, возвращается наверх — чинить надо их, а не повторять
     * импорт.
     *
     * @return array<int, array{name: string, error: string}>
     */
    private static function generate(array $gone): array
    {
        $failed = [];

        foreach ($gone as $name) {
            try {
                \MagicProAdminControllers\deleteMpro(['name' => $name]);
            } catch (\Throwable $e) {
                $failed[] = ['name' => $name, 'error' => $e->getMessage()];
            }
        }

        foreach (Article::orderBy('id')->get() as $article) {
            try {
                \MagicProAdminControllers\createMpro($article->toArray());
            } catch (\Throwable $e) {
                $failed[] = ['name' => (string) $article->name, 'error' => $e->getMessage()];
            }
        }

        return $failed;
    }

    // ==================================================================
    //                             helpers
    // ==================================================================

    /**
     * Fields of a row an article may take.
     *
     * @param  array<int, string>  $except
     */
    private function fields(array $row, array $except = []): array
    {
        $fields = [];

        foreach (self::FIELDS as $field) {
            if (in_array($field, $except, true) || ! array_key_exists($field, $row)) {
                continue;
            }

            $fields[$field] = $row[$field];
        }

        return $fields;
    }

    /**
     * A line of the report.
     *
     * Only a code and its parts travel to the browser: the admin panel speaks
     * two languages, and the words for both are in its own dictionary.
     */
    private function note(string $code, array $params = []): void
    {
        $this->report[] = [
            'code'   => $code,
            'params' => $params,
            'error'  => false,
            'done'   => false,
        ];
    }

    private function fail(string $code, array $params = []): void
    {
        $this->ok = false;

        $this->report[] = [
            'code'   => 'e_' . $code,
            'params' => $params,
            'error'  => true,
            'done'   => false,
        ];
    }
}
