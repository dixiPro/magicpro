<?php

namespace MagicProSrc\Mcp;

/**
 * `install.zip` from the MCP page: the folder an agent works from on Windows.
 *
 * The template lives in `docs/mcp-install/` — launchers, the configs of
 * Claude Code and Codex and a three-line `AGENTS.md`. The rules themselves are
 * not in the archive: `AGENTS.md` sends the agent to read `ru/mcp/agent.md`
 * through the МСП, so they are always those of this site and of its version,
 * and a change reaches every agent without a new archive. The site knows its own
 * address, so it is written into the configs while the archive is built:
 * unpack, run, type the token. The token itself goes into no file.
 *
 * Only Windows for now: a Linux archive needs its own launchers and its own
 * instructions, and nobody has tested them yet.
 */
class InstallArchive
{
    private const TEMPLATE = __DIR__ . '/../../docs/mcp-install';

    /** What is replaced by the address of the МСП in every file of the template. */
    private const URL_MARK = '__MCP_URL__';

    public const FILE = 'install.zip';

    /** The archive needs the zip extension of PHP; without it the page says so. */
    public static function available(): bool
    {
        return class_exists(\ZipArchive::class);
    }

    /** The address of the МСП of this site, the same the MCP page shows. */
    public static function url(): string
    {
        return url('/mcp/magicpro');
    }

    /**
     * Builds the archive in a temporary file and answers with its path. The
     * caller sends it and deletes it.
     */
    public static function build(): string
    {
        if (! self::available()) {
            throw new \RuntimeException('the zip extension of PHP is not installed');
        }

        $root = realpath(self::TEMPLATE);

        if ($root === false) {
            throw new \RuntimeException('the template of the archive is missing: docs/mcp-install');
        }

        $path = tempnam(sys_get_temp_dir(), 'mcp_install_');

        $zip = new \ZipArchive();

        if ($zip->open($path, \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('cannot create the archive');
        }

        // one folder inside, named after the site: unpacked next to another
        // site's folder, it does not mix with it
        $folder = self::folder();

        $walk = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($walk as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $rel  = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            $text = str_replace(self::URL_MARK, self::url(), (string) file_get_contents($file->getPathname()));

            $zip->addFromString($folder . '/' . $rel, $text);
        }

        if (! $zip->close()) {
            throw new \RuntimeException('cannot write the archive');
        }

        return $path;
    }

    /** `magicpro-mcp-<host>`: only what is safe in a folder name. */
    private static function folder(): string
    {
        $host = (string) parse_url(self::url(), PHP_URL_HOST);
        $host = preg_replace('/[^A-Za-z0-9.-]/', '', $host) ?: 'site';

        return 'magicpro-mcp-' . $host;
    }
}
