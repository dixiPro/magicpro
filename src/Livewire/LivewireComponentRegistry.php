<?php

namespace MagicProSrc\Livewire;

use Livewire\Mechanisms\ComponentRegistry;

/**
 * `magic::name` ↔ `MagicProControllers\name`, both ways.
 *
 * Name → class: the tag <livewire:magic::name /> and every `/livewire/update`
 * after it. Livewire on its own looks for classes in App\Livewire only.
 *
 * Class → name: once the class is found Livewire recomputes the name from it
 * ("deterministic names") and puts that into the snapshot the browser sends
 * back on every click. Without this side the snapshot would carry
 * `magic-pro-controllers.name`, a name nothing here resolves, and the first
 * click would end with "component not found".
 */
class LivewireComponentRegistry extends ComponentRegistry
{
    private const PREFIX = 'magic::';

    private const NAMESPACE = 'MagicProControllers\\';

    protected function generateClassFromName($name)
    {
        if (str_starts_with($name, self::PREFIX)) {
            $class = self::NAMESPACE . substr($name, strlen(self::PREFIX));

            if (class_exists($class)) {
                return $class;
            }
        }

        // other packages (Filament and the like) and the application resolve
        // their components as Livewire does
        return parent::generateClassFromName($name);
    }

    protected function generateNameFromClass($class)
    {
        $class = ltrim($class, '\\');

        if (str_starts_with($class, self::NAMESPACE)) {
            return self::PREFIX . substr($class, strlen(self::NAMESPACE));
        }

        return parent::generateNameFromClass($class);
    }
}
