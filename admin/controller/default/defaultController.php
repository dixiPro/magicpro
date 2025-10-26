<?php

namespace MagicProControllers;

use Illuminate\Http\Request;
use MagicProSrc\MagicController;

class Magic_Pro_Name_Controller extends MagicController
{
    protected function process(...$args): array
    {
        [$request, $getParams] = $args;


        return ["Get" => $getParams];
    }
}

//
// Magic__Pro__Name__Controller оставить как есть, Мпро само его подменит
// [$request, $getParams] = $args; 
// первйы передается Ларавеловский $request
// второй аргумент гет параметры в одном массиве
// будет еще третий аргумент User
//
// работа с бд и модели
// use Illuminate\Support\Facades\DB;
// use MagicProDatabaseModels\Article;