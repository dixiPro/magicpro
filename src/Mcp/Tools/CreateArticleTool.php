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

#[Name('create-article')]
#[Description('Creates an empty MagicPro article as a child of an existing one. The parentId argument is the future parent, not the article being created. The new article gets a generated name such as art_1784214877114, an empty body, the default controller, and the next free npp under that parent; the parent is marked as a directory. Give it a real name, title and body with a follow-up save-article call using the id returned here. Calls the articles API command createNew.')]
class CreateArticleTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'parentId' => $schema->integer()
                ->description('Id of the article the new one is created under. Pass 1 to create at the top level of the site.')
                ->required(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $result = API_ArticlesPostController::run([
            'command' => 'createNew',
            'id'      => $request->get('parentId'),
        ]);

        if (! $result['status']) {
            return Response::error($result['errorMsg']);
        }

        return Response::structured($result['data']);
    }
}
