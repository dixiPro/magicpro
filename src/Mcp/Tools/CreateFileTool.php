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

#[Name('create-file')]
#[Description('Creates a new file through the MagicPro file manager api, commands mkfile and then saveFile, and fills it with the given contents in the same call. The file has to be inside the upload directory of the file manager — the setting PUBLIC_UPLOAD_DIR, /design by default; find the place with list-dir, make the folder with make-dir. Only the extensions the file manager allows for editing: txt, rtf, csv, css, js, json, xml, sql and md. A file that already exists is an error and stays untouched — change it with save-file. Article blades and controllers are not files here, write those with save-article. Errors from the api are returned as tool errors.')]
class CreateFileTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'fileName' => $schema->string()
                ->description('Path to the new file relative to the public directory, for example design/css/article.css.')
                ->required(),

            'fileData' => $schema->string()
                ->description('Contents of the new file. Empty or omitted — the file is created empty.'),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $fileName = trim((string) $request->get('fileName'));
        $fileData = (string) $request->get('fileData', '');

        $created = $this->call(['command' => 'mkfile', 'fileName' => $fileName]);

        if (! $created['status']) {
            return Response::error($created['errorMsg'] ?? 'mkfile failed');
        }

        if ($fileData !== '') {
            $saved = $this->call(['command' => 'saveFile', 'fileName' => $fileName, 'fileData' => $fileData]);

            // the file is already there, empty: say so, or the agent tries to
            // create it once more and gets «already exists»
            if (! $saved['status']) {
                return Response::error(
                    'The file is created but empty: ' . ($saved['errorMsg'] ?? 'saveFile failed')
                    . '. Write its contents with save-file.'
                );
            }
        }

        // the api answers with the absolute server path; the agent works with
        // paths from the public directory, so it gets back the one it sent
        return Response::structured([
            'created' => $fileName,
            'size'    => strlen($fileData),
        ]);
    }

    private function call(array $input): array
    {
        return (new API_FileManagerPostController())
            ->handle(new HttpRequest($input))
            ->getData(true);
    }
}
