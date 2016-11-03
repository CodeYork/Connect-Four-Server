<?php

declare(strict_types=1);

/*
 * This file is part of Code York Connect Four.
 *
 * (c) Graham Campbell
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | This api key points the bugsnag notifier to the project in your account.
    |
    */

    'key' => env('BUGSNAG_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | User Logging
    |--------------------------------------------------------------------------
    |
    | This lets us know if we should attempt to log the current user.
    |
    */

    'user' => false,

];
