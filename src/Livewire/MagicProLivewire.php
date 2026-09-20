<?php

namespace MagicProSrc\Livewire;

use Livewire\Component;

/**
 * Base of every Livewire component that lives in an article.
 *
 * The builder names the controller class after its article, so the class
 * name is the name of the article's own blade: a component renders
 * `magic::<its name>` without writing that name anywhere, and renaming the
 * article breaks nothing. A component needs its own render() only when the
 * blade wants computed data; public properties reach the blade by themselves.
 *
 * Called from a page by tag only: <livewire:magic::name />.
 */
abstract class MagicProLivewire extends Component
{
    public function render()
    {
        return view('magic::' . class_basename(static::class));
    }
}
