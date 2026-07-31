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

#[Name('get-article-parents')]
#[Description('Returns the chain of ancestors of a MagicPro article, from its direct parent up to the root, as id, name, title and npp. Useful for breadcrumbs and for understanding where an article lives. The root itself has no ancestors and returns an empty list. Calls the articles API command getParents.')]
class GetArticleParentsTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('Article whose ancestors are listed.')
                ->required(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $result = API_ArticlesPostController::run([
            'command' => 'getParents',
            'id'      => $request->get('id'),
        ]);

        if (! $result['status']) {
            return Response::error($result['errorMsg']);
        }

        return Response::structured(['parents' => $result['data']]);
    }
}
