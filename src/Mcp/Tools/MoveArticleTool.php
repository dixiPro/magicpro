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

#[Name('move-article')]
#[Description('Moves a MagicPro article to another parent, or reorders it under its current one. This is the only correct way to change parentId or npp: it renumbers the siblings on both sides and maintains the directory flag of the old and the new parent, all in one transaction. Setting those fields through save-article instead leaves duplicate npp values and stale directory flags behind. Calls the articles API command move.')]
class MoveArticleTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('Article being moved.')
                ->required(),

            'newParentId' => $schema->integer()
                ->description('Parent the article should end up under. Pass its current parentId to reorder it in place without changing parents.')
                ->required(),

            'idBrotherUp' => $schema->integer()
                ->description('Article to place it directly after. Must be a child of newParentId. Pass 0 to make it the first child.')
                ->required(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $result = API_ArticlesPostController::run([
            'command'     => 'move',
            'id'          => $request->get('id'),
            'newParentId' => $request->get('newParentId'),
            'idBrotherUp' => $request->get('idBrotherUp'),
        ]);

        if (! $result['status']) {
            return Response::error($result['errorMsg']);
        }

        return Response::structured($result['data']);
    }
}
