<?php

namespace MagicProSrc\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('get-project-name')]
#[Description('Returns the Laravel project name.')]
class GetProjectNameTool extends Tool
{
    public function handle(Request $request): Response
    {
        return Response::text((string) config('app.name'));
    }
}
