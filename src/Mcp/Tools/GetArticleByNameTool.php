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

#[Name('get-article-by-name')]
#[Description('Reads one MagicPro article by its name, which is also its route: the name "about" belongs to the article served at /about. The match is exact, not a search. Returns the full record, same as get-article. Calls the articles API command articleByName.')]
class GetArticleByNameTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('Exact article name. Only latin letters, digits, hyphen and underscore occur in names.')
                ->pattern('^[A-Za-z0-9_-]+$')
                ->required(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $result = API_ArticlesPostController::run([
            'command' => 'articleByName',
            'name'    => $request->get('name'),
        ]);

        if (! $result['status']) {
            return Response::error($result['errorMsg']);
        }

        return Response::structured($result['data']);
    }
}
