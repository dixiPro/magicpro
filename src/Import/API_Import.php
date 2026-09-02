<?php

namespace MagicProSrc\Import;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MagicProSrc\Api\AbstractApi;

/**
 * Import of articles and the saved states: POST /a_dmin/api/import.
 *
 * The file itself never lands on the server: it lives in the browser and comes
 * in the body of every call, so the two passes need nothing kept between them.
 * A saved state is another matter — that one is a file of the site, and it is
 * written on purpose.
 *
 * Every command works up to the first error and throws; the shape of the answer
 * is built by AbstractApi::run().
 */
class API_Import extends AbstractApi
{
    protected const ERRORS = [
        'text_empty' => 'the file is empty',
        'mode_bad'   => 'unknown import mode',
    ];

    protected array $map = [
        'check'     => 'checkFile',
        'run'       => 'runImport',
        'snapshots' => 'snapshotList',
        'save'      => 'snapshotSave',
        'delete'    => 'snapshotDelete',
        'restore'   => 'snapshotRestore',
    ];

    /**
     * The answer of AbstractApi carries back what it was asked, and here that
     * is the whole file. Megabytes travelling back for nothing are worth these
     * few lines.
     */
    public function handle(Request $request): JsonResponse
    {
        $answer = parent::handle($request);

        $data = $answer->getData(true);

        if (isset($data['request']['text'])) {
            $data['request']['text'] = '...';
        }

        return response()->json($data);
    }

    /** First pass: says what will happen and changes nothing. */
    protected function checkFile(array $params): array
    {
        return ImportTree::check(self::text($params), self::mode($params));
    }

    /** Second pass: the same checks, then the work. */
    protected function runImport(array $params): array
    {
        return ImportTree::run(
            self::text($params),
            self::mode($params),
            (bool) ($params['snapshot'] ?? true),
        );
    }

    protected function snapshotList(array $params): array
    {
        return ['snapshots' => Snapshots::all()];
    }

    protected function snapshotSave(array $params): array
    {
        return [
            'file'      => Snapshots::save((string) ($params['name'] ?? '')),
            'snapshots' => Snapshots::all(),
        ];
    }

    protected function snapshotDelete(array $params): array
    {
        Snapshots::delete((string) ($params['file'] ?? ''));

        return ['snapshots' => Snapshots::all()];
    }

    /**
     * A state is put back the same way a file is imported, whole: the site is
     * wiped and raised from the snapshot.
     *
     * No state is saved before that. Putting one back is what a saved state is
     * for, and a snapshot of the tree somebody has just decided to throw away
     * is a file nobody will ever open.
     */
    protected function snapshotRestore(array $params): array
    {
        $text = Snapshots::read((string) ($params['file'] ?? ''));

        $result = ImportTree::run($text, ImportTree::FULL, false);

        $result['snapshots'] = Snapshots::all();

        return $result;
    }

    private static function text(array $params): string
    {
        $text = (string) ($params['text'] ?? '');

        if (trim($text) === '') {
            throw new \Exception(self::ERRORS['text_empty']);
        }

        return $text;
    }

    private static function mode(array $params): string
    {
        $mode = (string) ($params['mode'] ?? '');

        if (! in_array($mode, [ImportTree::PARTIAL, ImportTree::FULL], true)) {
            throw new \Exception(self::ERRORS['mode_bad']);
        }

        return $mode;
    }
}
