<?php

namespace MagicProSrc;

use Livewire\Mechanisms\ComponentRegistry;

class LivewireComponentRegistry extends ComponentRegistry
{
    public function generateClassFromName($name)
    {
        // 1) namespaced aliases like: <livewire:magic::lvcomponent />
        if (str_starts_with($name, 'magic::')) {
            $short = substr($name, strlen('magic::'));

            $class = 'MagicProControllers\\' . $short;
            if (class_exists($class)) {
                return $class;
            }

            // critical: do not break filament/livewire default resolving
            return parent::generateClassFromName($name);
        }

        // 2) legacy prefix support: "magic-pro-controllers.lvcomponent"
        $withoutPrefix = str_replace('magic-pro-controllers.', '', $name);
        if ($withoutPrefix !== $name) {
            $class = 'MagicProControllers\\' . $withoutPrefix;
            if (class_exists($class)) {
                return $class;
            }
        }

        // 3) fallback for everything else
        return parent::generateClassFromName($name);
    }
}
