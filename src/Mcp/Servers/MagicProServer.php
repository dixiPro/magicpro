<?php

namespace MagicProSrc\Mcp\Servers;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Contracts\Transport;
use MagicProSrc\Mcp\Tools\CreateArticleTool;
use MagicProSrc\Mcp\Tools\DeleteArticleTool;
use MagicProSrc\Mcp\Tools\FeedApiTool;
use MagicProSrc\Mcp\Tools\FeedApiWriteTool;
use MagicProSrc\Mcp\Tools\GetArticleByNameTool;
use MagicProSrc\Mcp\Tools\GetArticleChildrenTool;
use MagicProSrc\Mcp\Tools\GetArticleParentsTool;
use MagicProSrc\Mcp\Tools\GetArticleSiblingsTool;
use MagicProSrc\Mcp\Tools\GetArticleTool;
use MagicProSrc\Mcp\Tools\GetArticleTreeTool;
use MagicProSrc\Mcp\Tools\GetDocTool;
use MagicProSrc\Mcp\Tools\GetProjectNameTool;
use MagicProSrc\Mcp\Tools\ListDocsTool;
use MagicProSrc\Mcp\Tools\MoveArticleTool;
use MagicProSrc\Mcp\Tools\ReadFileTool;
use MagicProSrc\Mcp\Tools\SaveArticleTool;
use MagicProSrc\Mcp\Tools\SaveFileTool;
use MagicProSrc\Mcp\Tools\SearchArticlesTool;

class MagicProServer extends Server
{
    protected array $tools = [
        GetProjectNameTool::class,

        // документация пакета: агент по http файлов не видит
        ListDocsTool::class,
        GetDocTool::class,

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
        DeleteArticleTool::class,

        // ленты: чтение и запись, по инструменту на каждое
        FeedApiTool::class,
        FeedApiWriteTool::class,

        // файлы
        ReadFileTool::class,
        SaveFileTool::class,
    ];

    /**
     * The server sends no instructions of its own.
     *
     * The rules of the agent are the page `docs/ru/mcp/agent.md`. `AGENTS.md`
     * from `install.zip`, which the client reads at every start, sends the
     * agent to take it with get-doc. Server instructions would be one more copy
     * of the same, put by the host into every request and paid for each time. The empty string
     * is on purpose: without it the library sends its own sentence.
     */
    public function __construct(Transport $transport)
    {
        parent::__construct($transport);

        // без этого initialize отвечал «Laravel MCP Server 0.0.1» — именем и
        // версией библиотеки: не отличить от чужого Laravel MCP и не понять,
        // какой релиз пакета стоит на сайте
        $this->name    = 'MagicPro';
        $this->version = defined('MAGIC_VERSION') ? MAGIC_VERSION : $this->version;

        $this->instructions = '';
    }

    /**
     * The tools as the admin page lists them: the name and the first sentence
     * of the description.
     *
     * Read from the list above and from the attributes of each class — the
     * very things the agent is told — so the page cannot part with them. The
     * server itself is not built for it: its constructor wants a transport.
     *
     * @return array<int, array{name: string, summary: string}>
     */
    public static function toolList(): array
    {
        $classes = (new \ReflectionClass(static::class))->getDefaultProperties()['tools'] ?? [];

        $list = [];

        foreach ($classes as $class) {
            $ref = new \ReflectionClass($class);

            $name = ($ref->getAttributes(Name::class)[0] ?? null)?->newInstance()->value ?? class_basename($class);
            $text = ($ref->getAttributes(Description::class)[0] ?? null)?->newInstance()->value ?? '';
            $text = trim((string) preg_replace('/\s+/u', ' ', $text));

            $list[] = [
                'name'    => $name,
                'summary' => preg_match('/^.*?[.!?](?=\s|$)/u', $text, $m) ? $m[0] : $text,
            ];
        }

        return $list;
    }
}
