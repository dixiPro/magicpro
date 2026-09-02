<?php

namespace MagicProAdminControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use MagicProDatabaseModels\Article;

class ImportExportController extends Controller
{
    /**
     * Exports an article with its whole subtree as json.
     *
     * The parent goes into the file by name, not by id: the file is read by a
     * site where the same articles have different ids, and a name is the only
     * thing both sides agree on.
     */
    public function exportArticle(Request $request)
    {
        $id = (int) $request->input('id');

        $article = Article::find($id);

        if (! $article) {
            abort(404, 'article id=' . $id . ' not found');
        }

        $rows = Article::whereIn('id', $this->collectIds($id))->get();

        // Names of the subtree, to spare a query per row. Only the topmost
        // article has a parent outside it, and only it is asked for.
        $names = $rows->pluck('name', 'id');

        $data = $rows->map(function ($m) use ($names) {
            $item = $m->only($m->getFillable());

            $item['parentName'] = $m->parentId > 0
                ? ($names[$m->parentId] ?? Article::where('id', $m->parentId)->value('name'))
                : null;

            unset($item['parentId']);

            return $item;
        });

        // The name of an article is latin letters, digits, dash and underscore
        // and nothing else, but the header is built from data, and data is not
        // the place to trust.
        $file = preg_replace('/[^A-Za-z0-9_-]/', '', $article->name) ?: 'articles';

        return response(
            $data->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            200,
            [
                'Content-Type'        => 'application/json',
                'Content-Disposition' => 'attachment; filename="' . $file . '.json"',
            ]
        );
    }

    /**
     * Ids of the subtree, the article itself included.
     *
     * Level by level: one query per level instead of one per article, and an id
     * already taken is never walked again. A ring in `parentId` is possible —
     * the model forbids only a link to itself — and without that check the walk
     * would not end.
     *
     * @return array<int, int>
     */
    private function collectIds(int $id): array
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
}
