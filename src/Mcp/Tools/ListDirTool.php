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

#[Name('list-dir')]
#[Description('Lists one folder through the MagicPro file manager api, command dirList. Only folders inside the upload directory of the file manager can be listed — the setting PUBLIC_UPLOAD_DIR, /design by default. Without path the upload directory itself is listed, and the path used comes back in the answer. Folders come first, then files; each item has name, type (dir or file), mime, size and mtime, images also width and height. Use it to find a file before read-file or save-file, or a place for make-dir. Errors from the api are returned as tool errors.')]
class ListDirTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'path' => $schema->string()
                ->description('Folder relative to the public directory, for example design/img. Empty means the upload directory itself.'),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $path = trim((string) $request->get('path', ''));

        // no path: the agent does not have to know the setting, it gets the root
        if ($path === '') {
            $start = $this->call(['command' => 'start']);

            if (! $start['status']) {
                return Response::error($start['errorMsg'] ?? 'start failed');
            }

            $path = $start['data']['startDirectory'];
        }

        $result = $this->call(['command' => 'dirList', 'path' => $path]);

        if (! $result['status']) {
            return Response::error($result['errorMsg'] ?? 'dirList failed');
        }

        return Response::structured([
            'path'  => $path,
            'items' => $result['data'],
        ]);
    }

    private function call(array $input): array
    {
        return (new API_FileManagerPostController())
            ->handle(new HttpRequest($input))
            ->getData(true);
    }
}
