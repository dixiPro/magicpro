<?php

/**
 * Settings of the AI agent of the admin panel, section MCP.
 *
 * This is the copy that comes with the package. It is copied to
 * `storage/app/private/ai-sessions/configAI.php` when the site has none, and
 * the site edits its copy — this file is overwritten by the next update.
 *
 * The password is empty on purpose: an empty password forbids starting an
 * agent. The section stays off until somebody sets one.
 *
 * Documentation — packages/dixipro/magicpro/docs/ru/aiAgent/use.md
 */

return [
    // Дополнительный пароль запуска. Пустой пароль запрещает запуск.
    'password' => '',

    'agents' => [
        [
            'name' => 'codex',

            // Рабочая папка агента. Пусто — корень проекта.
            //  агент читает AGENTS.md из своей рабочей папки, а она задаётся ключом cwd. 
            // Поменял cwd — правила должны переехать вместе  с ней, иначе агент останется без них и никто этого не заметит.
            'cwd' => '',

            // Окружение сеанса. Агенту обычно нужен свой HOME: логин, настройки
            // и список MCP он держит в нём. Подробности — «Права и HOME» в доке.
            'env' => [
                // 'HOME' => '/var/www',
            ],

            // Чем запускать. Первая строка предлагается по умолчанию.
            'startCommands' => [
                ['Продолжить (рекомендуется)' => 'codex resume --last'],
                ['Выбрать сеанс' => 'codex resume'],
                ['Заново' => 'codex'],
            ],

            // Чем завершать. Первая уходит агенту, когда сеанс закрывает уборка.
            'exitCommands' => [
                ['завершить' => '/exit'],
            ],
        ],
    ],

    'poll_interval' => 1000,        // как часто браузер спрашивает новый вывод, мс
    'session_timeout' => 600,       // молчит столько секунд — сеанс закрывается
    'session_kill_timeout' => 720,  // молчит столько секунд — сеанс убивается
];
