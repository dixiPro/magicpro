<?php

class DumpHelper
{
    public static function dump($var): void
    {
        try {
            echo '<xmp style="line-height:1.2; font-size:12px;">'
                . json_encode($var, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                . '</xmp>';
        } catch (\Throwable $e) {
            echo '<pre style="color:red">Ошибка дампа: ' . '</pre>';
        }
    }
}
