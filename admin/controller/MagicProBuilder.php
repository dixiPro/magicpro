<?php

namespace MagicProAdminControllers;

use Illuminate\Support\Facades\File;
use MagicProSrc\MagicFile;
use MagicProSrc\Config\MagicGlobals;

// 🧩 default controller
define('DEFAULT_CONTROLLER', __DIR__ . '/default/defaultController.php');

// ⚡ default livewire controller
define('DEFAULT_LIVEWIRE_CONTROLLER', __DIR__ . '/default/defaultControllerLivewire.php');

// ⚡ default livewire blade: the markup for the default livewire controller
define('DEFAULT_LIVEWIRE_VIEW', __DIR__ . '/default/defaultBladeLivewire.blade');


/**
 * create/update resources for an article.
 * required keys: id, name, isRoute, controllerText, viewText
 *  - when isRoute=false: the route is removed, the view is still created or
 *    updated, and the controller does not depend on this flag at all: it is
 *    written whenever its text is not empty and routeParams.useController is
 *    on. That is what lets an article without a route carry a cron task
 */
function readDefaultController(): string
{
    return read_file_or_fail(DEFAULT_CONTROLLER);
}

function readDefaultLivewireController(): string
{
    return read_file_or_fail(DEFAULT_LIVEWIRE_CONTROLLER);
}

function readDefaultLivewireView(): string
{
    return read_file_or_fail(DEFAULT_LIVEWIRE_VIEW);
}

function createMpro(array $article): array
{

    $id       = $article['id']        ?: throw new \InvalidArgumentException('id is empty');
    $name     = trim($article['name'])  ?: throw new \InvalidArgumentException('name is empty');
    $isRoute  = $article['isRoute'];
    $useController  = $article['routeParams']['useController'] ?? false;

    $result = ['view' => false, 'controller' => false];

    // if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
    //     throw new \InvalidArgumentException("invalid name : {$name}");
    // }

    // delete everything
    deleteMpro($article);

    // VIEW (always create/update)
    // deleteMpro() выше уже сняла и запечённую html-копию
    $viewText       = $article['body'];
    $viewFile       = fileNameView($article);
    write_file_or_fail($viewFile, $viewText);
    $result['view'] = true;

    // CONTROLLER 
    // delete old controller
    $controllerFile = fileNameController($article);
    $controllerText = trim(dataController($article));
    if ($controllerText !== '' && $useController) {
        checkControllerName($name);

        $result['controller'] = true;
        write_file_or_fail($controllerFile, $controllerText);

        $cmd = 'php -l ' . escapeshellarg($controllerFile) . ' 2>&1';
        $out = shell_exec($cmd);

        if ($out === null) {
            throw new \RuntimeException('php lint failed');
        }

        if (!str_contains($out, 'No syntax errors detected')) {
            throw new \RuntimeException($out);
        }
    }
    return ($result);
}


/**
 * full deletion of resources for an article.
 * required keys: id, name
 */
function deleteMpro(array $article): void
{
    $nameView = $article['name'] ?? throw new \InvalidArgumentException('name is empty');
    $nameController = getNameController($article);

    // FILES: delete controller and view
    delete_file(MAGIC_CONTROLLER_DIR . '/' . $nameController . '.php');
    delete_file(MAGIC_VIEW_DIR . '/' . $nameView . '.blade.php');

    forgetStaticHtml($nameView);
}

/**
 * Снимает запечённую html-копию статьи.
 *
 * nginx отдаёт `STATIC_HTML_DIR` раньше php: `try_files /html$uri.html …`. Пока
 * там лежит файл статьи, правка в редакторе на сайт не попадает — посетитель
 * получает страницу с момента последнего обхода краулера, хоть через месяц.
 *
 * Поэтому как только файлы статьи пересобраны или удалены, её html уходит:
 * nginx не находит файла и передаёт запрос в php, страница свежая. Запечь
 * заново — дело краулера.
 *
 * Уходит и `html/<имя>/` — там лежат запечённые варианты того же адреса с
 * параметрами. Адреса у статей плоские, `/<имя>`, поэтому чужого там нет.
 * У главной файл называется `index.html`.
 */
function forgetStaticHtml(string $name): void
{
    // имя статьи — латиница, цифры, дефис и подчёркивание: пути из него не
    // собрать, но проверка дешевле, чем удалённый чужой каталог
    if (! preg_match('/^[A-Za-z0-9_-]+$/', $name)) {
        return;
    }

    $dir = rtrim(base_path((string) (MagicGlobals::$INI['STATIC_HTML_DIR'] ?? '/public/html')), '/');

    if (! is_dir($dir)) {
        return;
    }

    @unlink($dir . '/' . $name . '.html');

    if (is_dir($dir . '/' . $name)) {
        File::deleteDirectory($dir . '/' . $name);
    }
}

/* ================= helpers ================= */

function getNameController(array $article): string
{
    $nameController = $article['name'] ?? throw new \InvalidArgumentException('name is empty');
    return $nameController;
}


function fileNameController(array $article): string
{
    $nameController = getNameController($article);
    return MAGIC_CONTROLLER_DIR . '/' . $nameController . '.php';
}

function fileNameView(array $article): string
{
    $name = $article['name'];
    return MAGIC_VIEW_DIR . '/' . $name . '.blade.php';
}

function dataController(array $article): string
{
    $nameController = getNameController($article);
    $controllerText = trim($article['controller']) ?: read_file_or_fail(DEFAULT_CONTROLLER);
    $controllerText = str_replace('Magic_Pro_Name_Controller', $nameController, $controllerText);
    return $controllerText;
}

/**
 * Имя статьи как имя класса контроллера.
 *
 * Статье хватает латиницы, цифр, дефиса и подчёркивания, а класс строже: без
 * дефиса и не с цифры. `price-list` и `2026price` — нормальные статьи, но
 * контроллер с таким именем не соберётся. Раньше это всплывало ошибкой
 * синтаксиса из `php -l`, по которой не понять, что не так именно имя.
 */
function checkControllerName(string $name): void
{
    if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
        throw new \RuntimeException(
            "the name \"{$name}\" cannot be a controller class: latin letters, digits and underscore only, not starting with a digit"
        );
    }
}

/**
 * Проверяет php-текст контроллера, ничего не трогая на месте.
 *
 * `php -l` умеет только файл, поэтому текст уезжает во временный и оттуда же
 * удаляется. Смысл в порядке: раньше контроллер сперва писался в рабочий
 * каталог и только потом проверялся, и синтаксическая ошибка оставляла на диске
 * битый файл, а в базе — уже сохранённую статью.
 *
 * Пустой текст проверять нечего.
 */
function lintControllerText(string $text): void
{
    $text = trim($text);

    if ($text === '') {
        return;
    }

    $file = tempnam(sys_get_temp_dir(), 'magicpro_lint_') . '.php';

    try {
        if (false === @file_put_contents($file, $text)) {
            throw new \RuntimeException('cannot write a temporary file for the lint');
        }

        $out = shell_exec('php -l ' . escapeshellarg($file) . ' 2>&1');

        if ($out === null) {
            throw new \RuntimeException('php lint failed');
        }

        if (! str_contains($out, 'No syntax errors detected')) {
            // во временном имени файла пользы нет, а путаницы много
            throw new \RuntimeException(str_replace($file, 'controller', $out));
        }
    } finally {
        @unlink($file);
    }
}

function write_file_or_fail(string $file, string $data): void
{
    MagicFile::saveToFile($file, $data);
}

function read_file_or_fail(string $file): string
{

    $res  = file_get_contents($file);
    if ($res === false) {
        throw new \RuntimeException("Error reading file: {$file}");
    }
    return $res;
}

function delete_file(string $file): void
{
    if (file_exists($file)) {
        @unlink($file);
    }
}
