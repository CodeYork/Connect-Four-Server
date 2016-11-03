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

namespace CodeYork\ConnectFour\Exceptions;

use InvalidArgumentException;

class InvalidMoveException extends InvalidArgumentException implements GameExceptionInterface
{
    //
}
