<?php

namespace MagicProSrc\Mcp\Servers;

use Laravel\Mcp\Server;
use MagicProSrc\Mcp\Tools\CreateArticleTool;
use MagicProSrc\Mcp\Tools\GetArticleByNameTool;
use MagicProSrc\Mcp\Tools\GetArticleChildrenTool;
use MagicProSrc\Mcp\Tools\GetArticleParentsTool;
use MagicProSrc\Mcp\Tools\GetArticleSiblingsTool;
use MagicProSrc\Mcp\Tools\GetArticleTool;
use MagicProSrc\Mcp\Tools\GetArticleTreeTool;
use MagicProSrc\Mcp\Tools\GetProjectNameTool;
use MagicProSrc\Mcp\Tools\MoveArticleTool;
use MagicProSrc\Mcp\Tools\ReadFileTool;
use MagicProSrc\Mcp\Tools\SaveArticleTool;
use MagicProSrc\Mcp\Tools\SaveFileTool;
use MagicProSrc\Mcp\Tools\SearchArticlesTool;

class MagicProServer extends Server
{
    protected array $tools = [
        GetProjectNameTool::class,

        // статьи: чтение
        GetArticleTool::class,
        GetArticleByNameTool::class,
        SearchArticlesTool::class,

        // статьи: структура
        GetArticleTreeTool::class,
        GetArticleChildrenTool::class,
        GetArticleParentsTool::class,
        GetArticleSiblingsTool::class,

        // статьи: запись
        CreateArticleTool::class,
        SaveArticleTool::class,
        MoveArticleTool::class,

        // файлы
        ReadFileTool::class,
        SaveFileTool::class,
    ];
}
