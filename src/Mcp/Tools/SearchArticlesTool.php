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

#[Name('search-articles')]
#[Description('Finds MagicPro articles containing the given text in their blade source or in their controller source. Matching is a plain substring, case handling follows the database collation, and the title is not searched. Returns id and title only, so follow up with get-article for the full record. Calls the articles API command search.')]
class SearchArticlesTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'text' => $schema->string()
                ->description('Substring to look for in the body and controller columns.')
                ->required(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $result = API_ArticlesPostController::run([
            'command' => 'search',
            'text'    => $request->get('text'),
        ]);

        if (! $result['status']) {
            return Response::error($result['errorMsg']);
        }

        return Response::structured(['results' => $result['data']]);
    }
}
