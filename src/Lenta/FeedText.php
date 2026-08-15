<?php

namespace MagicProSrc\Lenta;

use Illuminate\Support\Facades\Blade;
use MagicProDatabaseModels\FeedItem;

/**
 * Text of a feed record, ready for the page.
 *
 * Two things happen to it. Marks like #img1# are replaced with values of the
 * record, and tags of magic components are rendered:
 *
 *     <x-magic::resize_abs_img :file="#img1#" width="400" />
 *
 * Only the tag itself goes through Blade. The text around it is never compiled,
 * so {{ }} and @php written by an operator stay letters — the difference
 * between "a blade" and "a text somebody typed in the admin panel" holds.
 *
 * In the visual editor the tag is stored escaped, &lt;x-magic::… /&gt;: for the
 * editor that is ordinary text, and it neither eats it nor breaks the markup
 * around. Both spellings are found here.
 */
class FeedText
{
    /** A component tag, plain or escaped. Attributes are taken as one piece. */
    private const TAG = '~(?:<|&lt;)x-magic::(?<name>[A-Za-z0-9_.-]+)(?<attrs>[^<>]*?)(?:/>|/&gt;)~su';

    /** #code# and #code.key# — a field of the record, maybe a key inside it. */
    private const MARK_BODY = '#(?<path>[A-Za-z0-9_-]+(?:\.[A-Za-z0-9_-]+)*)#';

    private const MARK = '~' . self::MARK_BODY . '~u';

    /** The same mark, but alone: the whole attribute is one field and no more. */
    private const MARK_ONLY = '~^\s*' . self::MARK_BODY . '\s*$~u';

    /** One attribute of a tag; the leading colon means "pass the value as is". */
    private const ATTR = '~(?<bound>:?)(?<name>[A-Za-z0-9_:.@-]+)\s*=\s*"(?<value>[^"]*)"~su';

    /** Blade syntax has no business inside a tag written by a webmaster. */
    private const BLADE = '~\{\{|\{!!|@[A-Za-z]~';

    /** Said instead of a tag that was not rendered. */
    private const UNSUPPORTED = 'unsupported';

    /**
     * The value of a field with everything expanded.
     *
     * Field names are logical, the same as everywhere else: $item->body is
     * read as render($item, 'body').
     */
    public static function render(FeedItem $item, string $code): string
    {
        $text = $item->$code;

        if (! is_string($text) || $text === '') {
            return '';
        }

        $values = $item->fields();

        // Tags go first, and their attributes are filled by type: an array has
        // to reach the component as an array. What is left in the text is
        // filled afterwards, where only a string makes sense.
        $html = preg_replace_callback(
            self::TAG,
            fn(array $tag): string => self::tag($tag, $values),
            $text
        );

        return self::marks($html, $values);
    }

    /** One component tag: fill the attributes, render, give back the html. */
    protected static function tag(array $found, array $values): string
    {
        // The escaped spelling carries &quot; in its attributes, so the tag is
        // decoded as a whole — but only the tag: entities in the text around it
        // are somebody else's business.
        $tag = html_entity_decode($found[0], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (preg_match(self::BLADE, $tag)) {
            \MproHelper::addLog('feed', [
                'error' => 'blade syntax inside a component tag',
                'tag'   => $tag,
            ]);

            return self::UNSUPPORTED;
        }

        $attributes = html_entity_decode($found['attrs'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $filled = preg_replace_callback(
            self::ATTR,
            fn(array $attribute): string => self::attribute($attribute, $values),
            $attributes
        );

        $blade = '<x-magic::' . $found['name'] . $filled . ' />';

        // Сначала компиляция, и только потом рендер.
        //
        // Несуществующий компонент — это опечатка в тексте записи, из-за неё не
        // должна падать страница. Но одним catch вокруг рендера не обойтись:
        // упавший внутренний рендер Laravel завершает вызовом flushState(), а
        // тот чистит секции всей внешней страницы, и она валится уже на своём
        // @endsection. Компиляция же разрешает имя компонента до всякого
        // рендера и ничего общего не трогает.
        try {
            Blade::compileString($blade);
        } catch (\Throwable $e) {
            \MproHelper::addLog('feed', [
                'error' => $e->getMessage(),
                'tag'   => $tag,
            ]);

            return self::UNSUPPORTED;
        }

        return Blade::render($blade);
    }

    /**
     * One attribute with its marks replaced.
     *
     * A bound attribute gets a php literal, so the component receives the value
     * itself — an array stays an array, a number stays a number. A plain one
     * gets text. Text with a double quote in it would break the attribute, and
     * Blade does not read entities there, so such a value is passed bound as
     * well: the component sees the same string either way.
     */
    protected static function attribute(array $attribute, array $values): string
    {
        $bound = $attribute['bound'] === ':';
        $name  = $attribute['name'];
        $value = $attribute['value'];

        // The whole value is one mark: the value of the field goes as it is,
        // with its type. Anything else is a string built out of pieces.
        if ($bound && preg_match(self::MARK_ONLY, $value, $one)) {
            $found = self::valueOf($one['path'], $values);

            return ' :' . $name . '="' . self::literal($found === null ? '' : $found) . '"';
        }

        $filled = self::marks($value, $values);

        if ($bound || str_contains($filled, '"')) {
            return ' :' . $name . '="' . self::literal($filled) . '"';
        }

        return ' ' . $name . '="' . $filled . '"';
    }

    /** Marks replaced by text. Whatever cannot be printed becomes empty. */
    protected static function marks(string $text, array $values): string
    {
        return preg_replace_callback(
            self::MARK,
            function (array $mark) use ($values): string {
                $value = self::valueOf($mark['path'], $values);

                // no such field — the text keeps its hashes: they are used in
                // ordinary writing too, and must not disappear on their own
                if ($value === null) {
                    return $mark[0];
                }

                // an array has no place in a text: there is nothing to print
                if (is_array($value) || (is_object($value) && ! method_exists($value, '__toString'))) {
                    return '';
                }

                return is_bool($value) ? ($value ? '1' : '') : (string) $value;
            },
            $text
        );
    }

    /**
     * Value of a field by the path of a mark, or null when the record has no
     * such field at all — then the mark stays in the text as it was written.
     *
     * Once the field is found, everything else gives an empty string. A key
     * missing inside it is not a mistake in the mark: alt of an image is
     * written by the operator in the form, and an image without one simply has
     * no such key. "Not filled in" must not leak hashes onto the page.
     */
    protected static function valueOf(string $path, array $values): mixed
    {
        $steps = explode('.', $path);
        $first = array_shift($steps);

        if (! array_key_exists($first, $values)) {
            return null;
        }

        $value = $values[$first];

        foreach ($steps as $step) {
            if (! is_array($value) || ! array_key_exists($step, $value)) {
                return '';
            }

            $value = $value[$step];
        }

        return $value ?? '';
    }

    /**
     * A php literal for a bound attribute.
     *
     * var_export writes strings in single quotes, so a double quote can only
     * come from the value itself — and it would end the attribute early. Such
     * quotes are assembled by chr(34) instead: the literal then holds none.
     */
    protected static function literal(mixed $value): string
    {
        return str_replace('"', "' . chr(34) . '", var_export($value, true));
    }
}
