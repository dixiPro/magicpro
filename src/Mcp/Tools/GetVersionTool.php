<?php

namespace MagicProSrc\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('get-version')]
#[Description('Returns the version of MagicPro installed on this site, the same number the admin panel shows in its title. Use it to tell which release a site runs before relying on a tool or a page of the documentation that may be newer than the site.')]
class GetVersionTool extends Tool
{
    public function handle(Request $request): Response
    {
        // the constant the admin panel prints: one source, so the two never differ
        return Response::text(defined('MAGIC_VERSION') ? MAGIC_VERSION : 'unknown');
    }
}
