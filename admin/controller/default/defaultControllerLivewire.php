<?php

namespace   MagicProControllers;

use Livewire\Component;

class Magic_Pro_Name_Controller  extends Component
{
    public string $text = 'Привет из Livewire!!';

    public function render()
    {
        return view('magic::lvcomponent', [
            'text' => $this->text,
        ]);
    }
}
