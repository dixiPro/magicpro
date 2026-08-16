<?php

namespace MagicProSrc\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use MagicProSrc\Lenta\API_Feeds;

#[Name('feed-api')]
#[Description(<<<'TXT'
Reads MagicPro feeds through the same api the admin panel uses.

A feed is a table the operator defines himself: products, news, trainings. All
records of all feeds live in one table with generic columns (__string_1,
__date_2, __data), and the feed schema says which logical name sits in which
column. Field names therefore cannot be guessed: read the schema first.

The usual path is feedsList (the operator names a feed with words, this turns it
into a feedId) then feedGet (field codes and types), and only then the records.

Commands, passed in `command` with their arguments in `params`:

- feedsList {groupId?} — feeds with id, code, title, itemsCount.
- feedGet {id} or {code} — one feed together with its schema.
- itemsList {feedId, page?, perPage?, orderBy?, direction?, filter?} — records
  by pages, with total and lastPage in the answer. filter is a list of
  {field, op, value}, op being like, =, <>, >, >=, < or <=. Only slot fields can
  be filtered and sorted: fields stored in __data have no column of their own.
  perPage above 250 is cut down to 250, so walk a long feed page by page.
- itemGet {id} — one record with all its fields.

Writing is a separate tool, feed-api-write. Command details are in
docs/ru/feed/use.md.
TXT)]
class FeedApiTool extends Tool
{
    /** Commands this tool may run. Reading only. */
    public const COMMANDS = ['feedsList', 'feedGet', 'itemsList', 'itemGet'];

    /** Ceiling for itemsList: a whole feed in one answer eats the agent's context. */
    public const PER_PAGE = 250;

    public function schema(JsonSchema $schema): array
    {
        return [
            'command' => $schema->string()
                ->enum(self::COMMANDS)
                ->description('Feeds api command to run.')
                ->required(),

            'params' => $schema->object()
                ->description('Arguments of the command, as described for each command above. An empty object for a command that takes none.'),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $command = (string) $request->get('command');
        $params  = (array) ($request->get('params') ?? []);

        if (! in_array($command, self::COMMANDS, true)) {
            return Response::error('Unknown command: ' . $command . '. This tool runs only ' . implode(', ', self::COMMANDS) . '.');
        }

        // потолок стоит здесь, а не в API: админке большие страницы не нужны,
        // а агенту важно, чтобы ответ помещался в контекст
        if ($command === 'itemsList' && (int) ($params['perPage'] ?? 0) > self::PER_PAGE) {
            $params['perPage'] = self::PER_PAGE;
        }

        $result = API_Feeds::run($command, $params);

        if (! $result['status']) {
            return Response::error($result['errorMsg']);
        }

        return Response::structured(self::wrap($result['data']));
    }

    /**
     * Ответ инструмента — всегда объект: список и пустота структурой не годятся,
     * пустой массив Response::structured() вовсе не принимает.
     */
    public static function wrap(mixed $data): array
    {
        if (is_array($data) && $data !== [] && ! array_is_list($data)) {
            return $data;
        }

        return ['results' => $data];
    }
}
