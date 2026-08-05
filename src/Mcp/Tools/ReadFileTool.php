<?php

namespace MagicProSrc\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request as HttpRequest;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use MagicProAdminControllers\API_FileManagerPostController;

#[Name('read-file')]
#[Description('Reads one file through the MagicPro file manager api, command loadFile. Returns the contents in the fileData field. Only the extensions the file manager allows for editing can be read: txt, rtf, csv, css, js, json, xml, sql and md. Article blades and controllers are not files here, read those with get-article. Errors from the api are returned as tool errors.')]
class ReadFileTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'fileName' => $schema->string()
                ->description('Path to the file relative to the public directory, for example design/script/form.js.')
                ->required(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $result = (new API_FileManagerPostController())
            ->handle(new HttpRequest([
                'command'  => 'loadFile',
                'fileName' => $request->get('fileName'),
            ]))
            ->getData(true);

        if (! $result['status']) {
            return Response::error($result['errorMsg'] ?? 'loadFile failed');
        }

        return Response::structured($result['data']);
    }
}
