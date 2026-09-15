<?php

namespace MagicProSrc\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use MagicProAdminControllers\API_ArticlesPostController;

#[Name('delete-article')]
#[Description('Deletes a MagicPro article together with everything under it: the whole subtree goes, its generated blades and controllers are removed and the addresses of those pages stop working. Two steps. Without confirm nothing is deleted: the answer shows the article and every article that would go with it, so read it and tell the human what is about to disappear. Only a second call with confirm true deletes. The root, id 1, cannot be deleted. A copy of the tree is an export of root made by a person in the admin panel, and a delete is the moment when the absence of one is felt, so ask before you do this. Calls the articles API command deleteById.')]
class DeleteArticleTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('Id of the article. Everything under it goes too.')
                ->required(),

            'confirm' => $schema->boolean()
                ->description('False or absent shows what would be deleted and deletes nothing. True deletes.'),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $id = (int) $request->get('id');

        if ($id === 1) {
            return Response::error('the root article cannot be deleted');
        }

        $article = $this->call('getById', ['id' => $id]);

        if (is_string($article)) {
            return Response::error($article);
        }

        // the first call only shows: an agent that deletes silently is the
        // reason this tool did not exist for a long time
        if (! $request->get('confirm')) {
            $goes = $this->subtree($id);

            return Response::structured([
                'deleted'   => false,
                'article'   => ['id' => $id, 'name' => $article['name'] ?? '', 'title' => $article['title'] ?? ''],
                'goes_with' => $goes,
                'total'     => count($goes) + 1,
                'next'      => 'call again with confirm true to delete all of it',
            ]);
        }

        $parent = $this->call('deleteById', ['id' => $id]);

        if (is_string($parent)) {
            return Response::error($parent);
        }

        return Response::structured(['deleted' => true, 'parent' => $parent]);
    }

    /**
     * Everything under the article, level by level.
     *
     * The api answers with the direct children, so the walk is the tool's own —
     * the only piece of logic here, and it exists because "delete" without the
     * list of what goes tells the agent nothing.
     *
     * @return array<int, array{id: int, title: string}>
     */
    private function subtree(int $id): array
    {
        $found = [];
        $queue = [$id];

        while ($queue !== []) {
            $current  = array_shift($queue);
            $children = $this->call('getChildrens', ['id' => $current]);

            if (is_string($children)) {
                continue;
            }

            foreach ($children as $child) {
                $found[] = ['id' => (int) $child['id'], 'title' => (string) ($child['text'] ?? '')];
                $queue[] = (int) $child['id'];
            }
        }

        return $found;
    }

    /** The answer of the api, or its error message as a string. */
    private function call(string $command, array $params): array|string
    {
        $result = API_ArticlesPostController::run(['command' => $command] + $params);

        return $result['status'] ? $result['data'] : (string) $result['errorMsg'];
    }
}
