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

#[Name('make-dir')]
#[Description('Creates a folder through the MagicPro file manager api, command mkdir. The folder has to be inside the upload directory of the file manager — the setting PUBLIC_UPLOAD_DIR, /design by default; find the place with list-dir first. Missing parent folders are created along the way. A folder that already exists is an error, nothing is overwritten. Errors from the api are returned as tool errors.')]
class MakeDirTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'folderName' => $schema->string()
                ->description('New folder relative to the public directory, for example design/img/banners.')
                ->required(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $folderName = trim((string) $request->get('folderName'));

        $result = (new API_FileManagerPostController())
            ->handle(new HttpRequest([
                'command'    => 'mkdir',
                'folderName' => $folderName,
            ]))
            ->getData(true);

        if (! $result['status']) {
            return Response::error($result['errorMsg'] ?? 'mkdir failed');
        }

        // the api answers with the absolute server path; the agent works with
        // paths from the public directory, so it gets back the one it sent
        return Response::structured(['created' => $folderName]);
    }
}
