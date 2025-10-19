<?php

namespace   MagicProControllers;

use Illuminate\Http\Request;
use MagicProSrc\MagicController;

class Magic_Pro_Name_Controller extends MagicController
{
    protected function process(Request $request): array
    {
        return ['Var' => 'test'];
    }
}

//
// Magic__Pro__Name__Controller оставить как есть, Мпро само его подменит
//
// работа с бд и модели
// use Illuminate\Support\Facades\DB;
// use MagicProDatabaseModels\Article;