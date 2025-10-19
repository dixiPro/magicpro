<?php
namespace   MagicProControllers;
use Livewire\Component;

class lvcomponent  extends Component
{
    public string $inputText = 'Livewire';
    public string $title = ''; // 👈 обязательно объявить, если передается в компонент

    public function render()
    {
return view('magic::lvcomponent', [
    'text' => $this->inputText,
]);
    }
}