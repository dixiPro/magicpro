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

#[Name('save-file')]
#[Description('Writes one file through the MagicPro file manager api, command saveFile. The whole file is replaced by the given contents, so read it with read-file first and send it back complete. The file has to exist already, this command does not create files. Only the extensions the file manager allows for editing can be written: txt, rtf, csv, css, js, json, xml, sql and md. Article blades and controllers are not files here, write those with save-article. Errors from the api are returned as tool errors.')]
class SaveFileTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'fileName' => $schema->string()
                ->description('Path to the file relative to the public directory, for example design/script/form.js.')
                ->required(),

            'fileData' => $schema->string()
                ->description('New contents of the file, complete. Anything omitted here is lost.')
                ->required(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $result = (new API_FileManagerPostController())
            ->handle(new HttpRequest([
                'command'  => 'saveFile',
                'fileName' => $request->get('fileName'),
                'fileData' => $request->get('fileData'),
            ]))
            ->getData(true);

        if (! $result['status']) {
            return Response::error($result['errorMsg'] ?? 'saveFile failed');
        }

        return Response::structured($result['data']);
    }
}
