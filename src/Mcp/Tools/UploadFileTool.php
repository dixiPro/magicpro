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

#[Name('upload-file')]
#[Description('Uploads a file through the MagicPro file manager api, command upload: the contents travel as base64, so this is the way to put a picture, a pdf or an archive on the site. Everything the admin panel accepts is accepted here — images (jpg, png, gif, webp, svg, ico), documents, audio, video, archives and the text formats. The folder has to be inside the upload directory of the file manager — the setting PUBLIC_UPLOAD_DIR, /design by default; find it with list-dir, create it with make-dir. A file of the same name is overwritten without asking, exactly as in the admin panel. Base64 makes the contents a third bigger, so a large photo is better uploaded by a person. Text files are easier written with create-file: there the contents go as they are. Errors from the api are returned as tool errors.')]
class UploadFileTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'path' => $schema->string()
                ->description('Folder for the file, relative to the public directory, for example design/img.')
                ->required(),

            'fileName' => $schema->string()
                ->description('Name of the file with its extension, for example logo.png.')
                ->required(),

            'fileData' => $schema->string()
                ->description('Contents of the file in base64, without the data: prefix.')
                ->required(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $result = (new API_FileManagerPostController())
            ->handle(new HttpRequest([
                'command'  => 'upload',
                'path'     => trim((string) $request->get('path')),
                'filename' => trim((string) $request->get('fileName')),
                'file'     => (string) $request->get('fileData'),
            ]))
            ->getData(true);

        if (! $result['status']) {
            return Response::error($result['errorMsg'] ?? 'upload failed');
        }

        // the api answers with the file as the file manager sees it: name, mime,
        // size, and for a picture its width and height
        return Response::structured($result['data']);
    }
}
