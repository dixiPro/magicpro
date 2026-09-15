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

#[Name('feed-api-write')]
#[Description(<<<'TXT'
Creates, edits and deletes records of MagicPro feeds. Reading is the feed-api
tool, and it comes first: field codes live in the feed schema, so call feedGet
before writing anything. A code that is not in the schema is refused.

Commands, passed in `command` with their arguments in `params`:

- itemCreate {feedId} — a new record at the end of the feed. It takes no values:
  the record appears hidden, with the defaults of the schema, and the values go
  in with itemSave.
- itemSave {id, fields, visible?, slug?} — changes the fields that are sent.
  `fields` is an object of logical field code to value; a field that is not in
  it keeps its value, so send only what changes. `visible` and `slug` are system
  columns and travel apart from the fields; visible is written exactly as asked,
  and a feed that derives its slug from a field recomputes the slug anyway.
- itemDelete {id, confirm?} — one record per call, no lists. Without confirm
  nothing is deleted: the answer describes what would go, including the records
  that link to it. Repeat with confirm true to actually delete. The files of the
  record go with it and nothing brings them back.

Image fields cannot be written here. An image reaches a feed through the upload
and the cropper of the admin panel, and MCP has no such channel; sending an image
field is an error, not a silently skipped value.
TXT)]
class FeedApiWriteTool extends Tool
{
    /** Commands this tool may run. Writing only: reading is feed-api. */
    public const COMMANDS = ['itemCreate', 'itemSave', 'itemDelete'];

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

        if ($command === 'itemDelete') {
            return $this->delete($params);
        }

        if ($command === 'itemSave') {
            $refused = $this->imagesRefused($params);

            if ($refused !== null) {
                return $refused;
            }
        }

        $result = API_Feeds::run($command, $params);

        if (! $result['status']) {
            return Response::error($result['errorMsg']);
        }

        return Response::structured(FeedApiTool::wrap($result['data']));
    }

    /**
     * Удаление в два шага: первый вызов только показывает, что уйдёт.
     *
     * Клиент показывает человеку каждый вызов инструмента, и во втором из них
     * видно заголовок записи, а не голый номер. Держателей ссылок добираем сами:
     * API отвечает на такое удаление голым false, и агенту оно ничего не говорит.
     */
    protected function delete(array $params): Response|ResponseFactory
    {
        $id = (int) ($params['id'] ?? 0);

        $item = API_Feeds::run('itemGet', ['id' => $id]);

        if (! $item['status']) {
            return Response::error($item['errorMsg']);
        }

        $holders = API_Feeds::run('itemLinks', ['id' => $id]);

        $preview = [
            'id'      => $id,
            'feedId'  => $item['data']['feedId'] ?? 0,
            'title'   => $this->firstValue($item['data']),
            'holders' => $holders['status'] ? $holders['data'] : [],
        ];

        if (! ($params['confirm'] ?? false)) {
            return Response::structured([
                'confirmRequired' => true,
                'willDelete'      => $preview,
                'note'            => 'Nothing deleted yet. Repeat the call with confirm true. The files of the record go with it.',
            ]);
        }

        $result = API_Feeds::run('itemDelete', ['id' => $id]);

        if (! $result['status']) {
            return Response::error($result['errorMsg']);
        }

        // false здесь не ошибка API, а отказ: на запись ссылаются
        if ($result['data'] !== true) {
            return Response::error(
                'Record ' . $id . ' is linked from ' . count($preview['holders'])
                . ' other record(s) and was not deleted: '
                . json_encode($preview['holders'], JSON_UNESCAPED_UNICODE)
            );
        }

        return Response::structured(['deleted' => true, 'id' => $id, 'was' => $preview]);
    }

    /**
     * Картинки этим путём не пишутся. Какие поля картиночные, знает только схема
     * ленты, поэтому её приходится спросить: запись → лента → схема.
     */
    protected function imagesRefused(array $params): Response|ResponseFactory|null
    {
        $fields = (array) ($params['fields'] ?? []);

        if ($fields === []) {
            return null;
        }

        $item = API_Feeds::run('itemGet', ['id' => (int) ($params['id'] ?? 0)]);

        if (! $item['status']) {
            return Response::error($item['errorMsg']);
        }

        $feed = API_Feeds::run('feedGet', ['id' => $item['data']['feedId'] ?? 0]);

        if (! $feed['status']) {
            return Response::error($feed['errorMsg']);
        }

        $images = array_intersect(array_keys($fields), $this->imageCodes($feed['data']['schema'] ?? []));

        if ($images === []) {
            return null;
        }

        return Response::error(
            'Image fields cannot be written through MCP: ' . implode(', ', $images)
            . '. An image gets into a feed through the upload and the cropper of the admin panel.'
        );
    }

    /** Коды полей-картинок схемы, вместе с теми, что лежат внутри __data. */
    protected function imageCodes(array $schema): array
    {
        $codes = [];

        foreach ($schema['fields'] ?? [] as $field) {
            $nested = ($field['column'] ?? null) === '__data' ? ($field['data'] ?? []) : [$field];

            foreach ($nested as $one) {
                if (($one['type'] ?? '') === 'image' && ($one['code'] ?? '') !== '') {
                    $codes[] = (string) $one['code'];
                }
            }
        }

        return $codes;
    }

    /**
     * Чем запись зовётся на экране — первым непустым значением, как в админке.
     *
     * Имя не title: так зовётся метод самого Tool, заголовок инструмента.
     */
    protected function firstValue(array $item): string
    {
        foreach ($item['fields'] ?? [] as $value) {
            if (is_scalar($value) && (string) $value !== '') {
                return (string) $value;
            }
        }

        return '';
    }
}
