<?php

namespace MagicProSrc\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use MagicProSrc\Docs\DocsTree;

#[Name('get-doc')]
#[Description('One page of the MagicPro documentation, as markdown, exactly as it is written. The path comes from list-docs and looks like ru/mainUse/use.md — the language first, then the folder of the module. Read only: the documentation is edited in the repository of the package, and what lies on a site is the copy that came with the release.')]
class GetDocTool extends Tool
{
    /**
     * `<lang>/<path>.md`, and nothing else gets through.
     *
     * Everything is spelled out: the letters of a language, the segments of a
     * path, the extension. A dot cannot get in, so `..` cannot be built either,
     * and `get-doc` does not turn into a second read-file.
     */
    private const PATH = '#^([A-Za-z-]{2,10})/([A-Za-z0-9_-]+(?:/[A-Za-z0-9_-]+)*)\.md$#';

    public function schema(JsonSchema $schema): array
    {
        return [
            'path' => $schema->string()
                ->description('The path of the page inside docs/, as list-docs gives it: ru/mainUse/use.md, ru/image/use.md, ru/feed/use.md.')
                ->required(),
        ];
    }

    /** Is this page marked `agent` in the index of its language. */
    private static function offered(string $lang, string $link): bool
    {
        foreach (DocsTree::pages($lang) as $page) {
            if ($page['link'] === $link) {
                return $page['agent'] && $page['exists'];
            }
        }

        return false;
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $path = trim((string) $request->get('path'));

        if (! preg_match(self::PATH, $path, $found)) {
            return Response::error(
                'the path must look like ru/mainUse/use.md: a language, a path inside it and the .md extension. Take it from list-docs.'
            );
        }

        [, $lang, $name] = $found;

        // the same list `list-docs` is built from: a page is handed over only
        // when the index marked it for МСП. Without this the whitelist held
        // only the table of contents — the path of any other page still worked
        if (! self::offered($lang, $name . '.md')) {
            return Response::error('this page is not offered over МСП: ' . $path . '. Call list-docs for what is.');
        }

        // getDoc() checks the name and the language once more and builds the
        // path itself, so nothing arrives at the file system from here
        $text = \MproHelper::getDoc($name, $lang, false);

        if ($text === '') {
            return Response::error('there is no such page: ' . $path . '. Call list-docs and take the path from there.');
        }

        return Response::text($text);
    }
}
