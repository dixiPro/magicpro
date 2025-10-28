<?php
// 
//  Magic_Pro_Name_Controller оставить, система сама переименует
// 
//  ДОЛЖЕН НАЗЫВАТЬСЯ С ЗАГЛАВНОЙ
//  иначе Loaravel контроллер на запустит, а вызовет компонент напрямую
// 
//  Laravel производит два вызова 
// 
//  __construct в котором передает параметры
//   render — вызывает в итоге вьюху
//  
//
namespace MagicProControllers;

use Illuminate\View\Component;
use Illuminate\Contracts\View\View;

class Magic_Pro_Name_Controller extends Component
{
    
    public string $name;  

    public function __construct(string $name = '') // // обязательно объявить переменные которые принимает
    {
        // действия с данными если надо
        // сохранить до вызова render или обработать
        $this->name = $name; 
    }
    
    public function render(): View
    {
        $time = now()->format('H:i:s');
        $nameWithTime = $this->name . ' ' . $time;

        return view('magic::' . class_basename(static::class), 
        // тут возвращаемые параметры
        [
         'nameChat' => $nameWithTime,
        ]);
    }
}

// вызов из блейда
//  <x-magic::ИмяСтатьи name="GPT"></x-magic::ИмяСтатьи>
// 
// 
// в соседнем окошке
// <h2> {{ $nameChat }}</h2>
// 




