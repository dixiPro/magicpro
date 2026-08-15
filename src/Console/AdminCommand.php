<?php

namespace MagicProSrc\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use MagicProDatabaseModels\MagicProUser;

/**
 * Creates an admin of the panel.
 *
 * Used to be done by the users migration through readline(), which asked even
 * when there was nobody to ask: a non-interactive migrate created an admin with
 * an empty password and said nothing. Here values are only ever typed by hand,
 * and without a terminal the command creates nothing.
 */
class AdminCommand extends Command
{
    protected $signature = 'magicpro:admin';

    protected $description = 'Creates a MagicPro admin';

    /** The model validates the stored value, so the typed one is checked here. */
    private const PASSWORD_MIN = 4;

    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('magicpro:admin needs a terminal: email and password are typed by hand.');

            return self::FAILURE;
        }

        $email = trim((string) $this->ask('Email'));

        // secret() hides the input, so the password never shows up on screen
        // and there is nothing to print back afterwards.
        $password = (string) $this->secret('Password (min ' . self::PASSWORD_MIN . ' characters)');

        if (mb_strlen($password) < self::PASSWORD_MIN) {
            $this->error('Password must be at least ' . self::PASSWORD_MIN . ' characters.');

            return self::FAILURE;
        }

        try {
            // Format, uniqueness and role are checked by the model itself.
            MagicProUser::create([
                'name'     => 'Admin',
                'email'    => $email,
                'password' => Hash::make($password),
                'role'     => 'admin',
            ]);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Admin created: ' . $email);
        $this->info('Open the admin panel: ' . url('/a_dmin'));

        return self::SUCCESS;
    }
}
