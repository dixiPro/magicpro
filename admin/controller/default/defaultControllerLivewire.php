<?php

namespace MagicProControllers;

use MagicProSrc\Livewire\MagicProLivewire;

// the builder replaces Magic_Pro_Name_Controller with the article name,
// and MagicProLivewire renders the blade of this same article
class Magic_Pro_Name_Controller extends MagicProLivewire
{
    // a public property is the state of the component: it survives every
    // request and is visible in the blade as $count
    public int $count = 0;

    // a public method is called from the blade: wire:click="add"
    public function add(): void
    {
        $this->count++;
    }
}
