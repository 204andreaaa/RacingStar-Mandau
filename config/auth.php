<?php

return [

    'defaults' => [
        // Pakai guard khusus user_bestrising
        'guard' => 'bestrising',
        'passwords' => 'bestrising_users',
    ],

    'guards' => [
        // Tetap simpan guard web bawaan (kalau masih ada kebutuhan lain)
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        // Guard khusus tabel user_bestrising
        'bestrising' => [
            'driver'  => 'session',
            'provider'=> 'bestrising_users',
        ],
    ],

    'providers' => [
        // Provider default bawaan Laravel (boleh tetap ada)
        'users' => [
            'driver' => 'eloquent',
            'model'  => App\Models\User::class,
        ],

        // Provider buat user_bestrising
        'bestrising_users' => [
            'driver' => 'eloquent',
            'model'  => App\Models\UserBestrising::class,
        ],
    ],

    'passwords' => [
        // Reset config untuk users bawaan (opsional)
        'users' => [
            'provider' => 'users',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],

        // Reset config untuk user_bestrising
        'bestrising_users' => [
            'provider' => 'bestrising_users',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];
