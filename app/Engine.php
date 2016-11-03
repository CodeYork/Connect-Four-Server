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

namespace CodeYork\ConnectFour;

use CodeYork\ConnectFour\Exceptions\GameAlreadyFullException;
use CodeYork\ConnectFour\Exceptions\GameNotFoundException;
use CodeYork\ConnectFour\Exceptions\GameNotStartedException;
use CodeYork\ConnectFour\Exceptions\InvalidMoveException;
use CodeYork\ConnectFour\Exceptions\OpponentMovingException;
use Illuminate\Contracts\Cache\Repository;

final class Engine
{
    /**
     * The pending entry.
     *
     * @var string[]
     */
    const PENDING = 'pending';

    /**
     * The timeout.
     *
     * @var int
     */
    const TIMEOUT = 60;

    /**
     * The cache repository instance.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    private $store;

    /**
     * Create a new engine instance.
     *
     * @param \Illuminate\Contracts\Cache\Repository $store
     *
     * @return void
     */
    public function __construct(Repository $store)
    {
        $this->store = $store;
    }

    /**
     * Start a new game, or join an existing game.
     *
     * @return array
     */
    public function start()
    {
        $game = str_random(8);
        $data = State::create()->toArray();

        $this->store->put($game, $data = $data, self::TIMEOUT);

        return ['game' => $game, 'player' => State::WHITE];
    }

    /**
     * Join an existing game.
     *
     * @param string $game
     *
     * @return array
     */
    public function join(string $game)
    {
        if (!($data = $this->store->get($game))) {
            throw new GameNotFoundException('The given game does not exist.');
        }

        $state = State::create($data);

        if ($state->hasStarted()) {
            throw new GameAlreadyFullException('The given game is already full.');
        }

        $state->start();

        $this->store->put($game, $data = $state->toArray(), self::TIMEOUT);

        return ['game' => $game, 'player' => State::BLACK];
    }

    /**
     * Get some game date.
     *
     * @param string $game
     *
     * @return array
     */
    public function get(string $game)
    {
        if (!($data = $this->store->get($game))) {
            throw new GameNotFoundException('The given game does not exist.');
        }

        return $data;
    }

    /**
     * Attempt to make the given move.
     *
     * @param string $game
     * @param int    $player
     * @param int[]  $from
     * @param int[]  $to
     *
     * @return array
     */
    public function move(string $game, int $player, int $col)
    {
        if (!($data = $this->store->get($game))) {
            throw new GameNotFoundException('The given game does not exist.');
        }

        $state = State::create($data);

        if (!$state->hasStarted()) {
            throw new GameNotStartedException('The given game has not started yet.');
        }

        if ($state->getCurrentPlayer() !== $player) {
            throw new OpponentMovingException('Your opponent is currently moving.');
        }

        $board = $state->getBoard();
        $player = $state->getCurrentPlayer();

        if ($col < 0 || $col >= State::WIDTH) {
            throw new InvalidMoveException('Invalid column index provided.');
        }

        if (!$state->makeMove($col)) {
            throw new InvalidMoveException('The given column is already full.');
        }

        $this->store->put($game, $data = $state->toArray(), self::TIMEOUT);
    }
}
