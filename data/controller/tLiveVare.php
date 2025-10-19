<?php

namespace   MagicProControllers;

use Illuminate\Http\Request;
// use Livewire\Livewire;
use MagicProSrc\MagicController;


class tLiveVare extends MagicController
{
    protected function process(Request $request): array
    {
        // Livewire::componentNamespace('MagicProControllers', 'mpro');
        // Livewire::component('mpro_31', mpro_31::class);
        return ['Var' => 'test'];
    }
}

//
// Magic__Pro__Name__Controller оставить как есть, Мпро само его подменит
//
// работа с бд и модели
// use Illuminate\Support\Facades\DB;
// use MagicProDatabaseModels\Article;