<?php

namespace MagicProAdminControllers;

use Illuminate\Support\Facades\File;

/**
 * Создать/обновить ресурсы под статью.
 * Обязательные ключи: id, name, isRoute, controllerText, viewText
 *  - при isRoute=false: роут удаляется, контроллер удаляется, вью создаётся/обновляется
 */
function readDefaultController(): string
{
    return read_file_or_fail(DEFAULT_CONTROLLER);
}

function readDefaultLiveWareController(): string
{
    return read_file_or_fail(DEFAULT_LIVEWIRE_CONTROLLER);
}

function createMpro(array $article): void
{

    $id       = $article['id']        ?: throw new \InvalidArgumentException('id is empty');
    $name     = trim($article['name'])  ?: throw new \InvalidArgumentException('name is empty');
    $isRoute  = $article['isRoute'];

    if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
        throw new \InvalidArgumentException("Некорректное имя : {$name}");
    }

    // удаляем все
    deleteMpro($article);

    // VIEW (создаём/обновляем всегда)
    $viewText       = $article['body'];
    $viewFile       = fileNameView($article);
    write_file_or_fail($viewFile, $viewText);

    // CONTROLLER 
    // удаляем старый контроллер
    $controllerFile = fileNameController($article);
    $controllerText = trim(dataController($article));
    if ($controllerText !== '') {
        write_file_or_fail($controllerFile, $controllerText);
    }
}


/**
 * Полное удаление ресурсов под статью.
 * Обязательные ключи: id, name
 */
function deleteMpro(array $article): void
{
    $nameView = $article['name'] ?? throw new \InvalidArgumentException('name is empty');
    $nameController = getNameController($article);

    // FILES: удалить контроллер и вью
    delete_file(MAGIC_CONTROLLER_DIR . '/' . $nameController . '.php');
    delete_file(MAGIC_VIEW_DIR . '/' . $nameView . '.blade.php');
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

function write_file_or_fail(string $file, string $data): void
{

    $res = file_put_contents($file, $data, LOCK_EX);
    if ($res === false) {
        throw new \RuntimeException("Error writing file: {$file}");
    }
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
