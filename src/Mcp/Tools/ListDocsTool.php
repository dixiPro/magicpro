<?php

namespace MagicProSrc\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use MagicProSrc\Docs\DocsTree;

#[Name('list-docs')]
#[Description('The table of contents of the MagicPro documentation. Call it before the first change in a module you have not touched here yet: feeds, images, mail, cron, components, articles, helpers. Every row is a section, the name of a page in it, a line on what the page is about and the path to take with get-doc: open what the task needs, not everything. An agent working over http has no files of the package, and this is the only way to read its documentation.')]
class ListDocsTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $lang  = DocsTree::langWithDocs();
        $pages = DocsTree::pages($lang);

        if ($pages === []) {
            return Response::error(
                'there is no documentation index for the language "' . $lang . '": docs/' . $lang . '/index.json is missing'
            );
        }

        $docs = [];

        foreach ($pages as $page) {
            // `agent` in the index is what decides: a page is offered only
            // when it was marked for МСП, and a page named but missing on disk
            // is nothing to read anyway
            if (! $page['agent'] || ! $page['exists']) {
                continue;
            }

            $docs[] = [
                'section' => $page['group'],
                'name'    => $page['name'],
                'about'   => $page['about'],
                'path'    => $lang . '/' . $page['link'],
            ];
        }

        return Response::structured(['docs' => $docs]);
    }
}
