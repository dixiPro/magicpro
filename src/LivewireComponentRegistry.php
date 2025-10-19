<?php

namespace MagicProSrc;

use Livewire\Mechanisms\ComponentRegistry;

class LivewireComponentRegistry extends ComponentRegistry
{
    public function generateClassFromName($name)
    {
        // 1) Handle namespaced aliases like: <livewire:magic::lvcomponent />
        if (str_starts_with($name, 'magic::')) {
            // Remove the "magic::" prefix
            $short = substr($name, strlen('magic::'));

            // Map to the MagicProControllers namespace
            $class = 'MagicProControllers\\' . $short;

            // Return if the class exists
            if (class_exists($class)) {
                return $class;
            }
            // Don't return; fall through to try legacy mapping below as well.
        }

        // 2) Legacy prefix support (kept for backward compatibility):
        //    Accept aliases like "magic-pro-controllers.lvcomponent"
        //    and map them to "MagicProControllers\lvcomponent".
        $normalized = str_replace('magic-pro-controllers.', '', $name);
        if ($normalized !== $name) {
            $class = 'MagicProControllers\\' . $normalized;

            if (class_exists($class)) {
                return $class;
            }
        }

        // 3) Fallback to the default Livewire resolver for everything else.
        return parent::generateClassFromName($name);
    }
}
