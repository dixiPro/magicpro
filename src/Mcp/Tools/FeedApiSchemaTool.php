<?php

namespace MagicProSrc\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use MagicProDatabaseModels\FeedItem;
use MagicProSrc\Lenta\API_Feeds;

#[Name('feed-api-schema')]
#[Description(<<<'TXT'
Creates MagicPro feeds and sets up their structure: groups, code, title and
schema. Records are written by feed-api-write, read by feed-api.

A feed is open to this tool only while it has no records. Once the first
record exists, feedSave and schemaSave refuse: code, title, group and schema
of a filled feed are changed by a person in the admin panel. So finish the
structure before the first itemCreate.

Commands, passed in `command` with their arguments in `params`:
- groupsList {} — the groups of feeds.
- groupCreate {title} — a new group at the end of the list.
- feedCreate {groupId} — a new empty feed in the group, with a generated code;
  give it a real code and title with feedSave.
- feedSave {id, code?, title?, groupId?} — code, title, group; only the keys that
  are sent change. The code is unique, the site finds the feed by it.
- schemaGet {feedId} — the schema as it is stored.
- schemaSave {feedId, schema} — the schema as a whole, never field by field:
  take schemaGet, change it, send the whole object back. Every check of the
  admin panel runs: slots, codes, slugFrom, links.

Read ru/feed/use.md before the first schema: it lists the slots and the types.
Nothing here deletes feeds or groups, clears or moves them.
TXT)]
class FeedApiSchemaTool extends Tool
{
    /** Commands this tool may run. */
    public const COMMANDS = ['groupsList', 'groupCreate', 'feedCreate', 'feedSave', 'schemaGet', 'schemaSave'];

    /** Commands closed once the feed has a record, and where its id travels. */
    private const LOCKED = ['feedSave' => 'id', 'schemaSave' => 'feedId'];

    public function schema(JsonSchema $schema): array
    {
        return [
            'command' => $schema->string()
                ->enum(self::COMMANDS)
                ->description('Feeds api command to run.')
                ->required(),
            'params' => $schema->object()
                ->description('Arguments of the command, as described for each command above.')
                ->required(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $command = (string) $request->get('command');
        $params  = (array) ($request->get('params') ?? []);

        if (! in_array($command, self::COMMANDS, true)) {
            return Response::error('Unknown command: ' . $command . '. This tool runs only ' . implode(', ', self::COMMANDS) . '.');
        }

        // the rule belongs to МСП, not to the api: in the admin panel a filled
        // feed stays open. The id is required here: the api would also find a
        // feed by its code, and that way would pass by this check
        if (isset(self::LOCKED[$command])) {
            $key    = self::LOCKED[$command];
            $feedId = (int) ($params[$key] ?? 0);

            if ($feedId <= 0) {
                return Response::error($command . ' needs ' . $key . '.');
            }

            if (FeedItem::query()->where('feed_id', $feedId)->exists()) {
                return Response::error('The feed ' . $feedId . ' has records: its structure is changed only in the admin panel.');
            }
        }

        if ($command === 'groupCreate') {
            return $this->groupCreate($params);
        }

        return $this->answer(API_Feeds::run($command, $params));
    }

    /**
     * The api makes a group with a default title and renames it separately,
     * the way the admin panel does. Here it is one step: a group without its
     * title would only confuse the person who sees it later.
     */
    protected function groupCreate(array $params): Response|ResponseFactory
    {
        $title = trim((string) ($params['title'] ?? ''));

        if ($title === '') {
            return Response::error('groupCreate needs a title.');
        }

        $created = API_Feeds::run('groupCreate', []);

        if (! $created['status']) {
            return Response::error($created['errorMsg']);
        }

        return $this->answer(API_Feeds::run('groupSave', [
            'id'    => $created['data']['id'],
            'title' => $title,
        ]));
    }

    protected function answer(array $result): Response|ResponseFactory
    {
        if (! $result['status']) {
            return Response::error($result['errorMsg']);
        }

        return Response::structured(FeedApiTool::wrap($result['data']));
    }
}
