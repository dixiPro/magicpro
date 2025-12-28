<?php

namespace MagicProAdminControllers;

use Illuminate\Support\Facades\File;
use MagicProSrc\MagicFile;

// 🧩 default controller
define('DEFAULT_CONTROLLER', __DIR__ . '/default/defaultController.php');

// ⚡ default livewire controller
define('DEFAULT_LIVEWIRE_CONTROLLER', __DIR__ . '/default/defaultControllerLivewire.php');


/**
 * create/update resources for an article.
 * required keys: id, name, isRoute, controllerText, viewText
 *  - when isRoute=false: route is removed, controller is removed, view is created/updated
 */
function readDefaultController(): string
{
    return read_file_or_fail(DEFAULT_CONTROLLER);
}

function readDefaultLiveWareController(): string
{
    return read_file_or_fail(DEFAULT_LIVEWIRE_CONTROLLER);
}

function createMpro(array $article): array
{

    $id       = $article['id']        ?: throw new \InvalidArgumentException('id is empty');
    $name     = trim($article['name'])  ?: throw new \InvalidArgumentException('name is empty');
    $isRoute  = $article['isRoute'];
    $useController  = $article['routeParams']['useController'] ?? false;

    $result = ['view' => false, 'controller => false'];

    // if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
    //     throw new \InvalidArgumentException("invalid name : {$name}");
    // }

    // delete everything
    deleteMpro($article);

    // VIEW (always create/update)
    $viewText       = $article['body'];
    $viewFile       = fileNameView($article);
    write_file_or_fail($viewFile, $viewText);
    $result['view'] = true;

    // CONTROLLER 
    // delete old controller
    $controllerFile = fileNameController($article);
    $controllerText = trim(dataController($article));
    if ($controllerText !== '' && $useController) {
        $result['controller'] = true;
        write_file_or_fail($controllerFile, $controllerText);
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
