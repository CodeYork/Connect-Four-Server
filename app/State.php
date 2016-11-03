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

final class State
{
    /**
     * The white player.
     *
     * @var int
     */
    const WHITE = 0;

    /**
     * The black player.
     *
     * @var int
     */
    const BLACK = 1;

    /**
     * The emtpy player.
     *
     * @var null
     */
    const NONE = null;

    /**
     * The width of the board.
     *
     * @var int
     */
    const WIDTH = 7;

    /**
     * The height of the board.
     *
     * @var int
     */
    const HEIGHT = 6;

    /**
     * The required chain length.
     *
     * @var int
     */
    const CHAIN = 4;

    /**
     * The directions to check.
     *
     * @var int[]
     */
    const DIRECTIONS = [[0, 1], [1, 1], [1, 0], [1, -1], [0, -1], [-1, -1], [-1, 0], [-1, 1]];

    /**
     * The game board.
     *
     * @var int[][]
     */
    private $board;

    /**
     * If the game has started.
     *
     * @var bool
     */
    private $started;

    /**
     * The current payer.
     *
     * @var int
     */
    private $current;

    /**
     * Create a new state instance.
     *
     * @param array $data
     *
     * @return void
     */
    private function __construct(array $data)
    {
        $this->board = $data['board'];
        $this->started = $data['started'];
        $this->current = $data['current'];
    }

    /**
     * Create a new state instance.
     *
     * @param array|null $data
     *
     * @return \CodeYork\ConnectFour\State
     */
    public static function create(array $data = null)
    {
        if ($data === null) {
            $data['board'] = [];

            for ($i = 0; $i < self::HEIGHT; $i++) {
                for ($j = 0; $j < self::WIDTH; $j++) {
                    $data['board'][$i][$j] = self::NONE;
                }
            }

            $data['started'] = false;
            $data['current'] = self::WHITE;
        }

        return new self($data);
    }

    /**
     * Get the game board.
     *
     * @return array
     */
    public function getBoard()
    {
        return $this->board;
    }

    /**
     * Get the winner of the game.
     *
     * @return int|null
     */
    public function getWinner()
    {
        for ($i = 0; $i < self::HEIGHT; $i++) {
            for ($j = 0; $j < self::WIDTH; $j++) {
                if ($this->isConnected($i, $j, self::WHITE)) {
                    return self::WHITE;
                }

                if ($this->isConnected($i, $j, self::BLACK)) {
                    return self::BLACK;
                }
            }
        }
    }

    /**
     * Check if the given player has a chain at the given position.
     *
     * @param int $row
     * @param int $col
     * @param int $player
     *
     * @return bool
     */
    protected function isConnected(int $row, int $col, int $player)
    {
        if ($this->board[$row][$col] !== $player) {
            return false;
        }

        foreach (self::DIRECTIONS as $direction) {
            if ($this->checkDirection([$row, $col], $direction, self::CHAIN)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a counter connection exists in the given direction.
     *
     * @param int[] $pos
     * @param int[] $dir
     * @param int   $len
     *
     * @return bool
     */
    protected function checkDirection(array $pos, array $dir, int $len)
    {
        $owner = $this->board[$pos[0]][$pos[1]];

        for ($i = 1; $i < $len; $i++) { 
            if (($this->board[$pos[0] + $dir[0] * $i][$pos[1] + $dir[1] * $i] ?? null) !== $owner) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the current player.
     *
     * @return int
     */
    public function getCurrentPlayer()
    {
        return $this->current;
    }

    /**
     * Get if the game has started.
     *
     * @return bool
     */
    public function hasStarted()
    {
        return $this->started;
    }

    /**
     * Start the game if not started.
     *
     * @return bool
     */
    public function start()
    {
        $this->started = true;
    }

    /**
     * Make a move.
     *
     * @param int $col
     *
     * @return bool
     */
    public function makeMove(int $col)
    {
        if (!$this->placeCounter($col)) {
            return false;
        }

        $this->swapTurns();

        return true;
    }

    /**
     * Move the piece to the given positions.
     *
     * @param int $col
     *
     * @return void
     */
    private function placeCounter(int $col)
    {
        for ($row = self::HEIGHT - 1; $row >= 0; $row--) { 
            if ($this->board[$row][$col] === null) {
                $this->board[$row][$col] = $this->current;

                return true;
            }
        }

        return false;
    }

    /**
     * Swap turns with the other player.
     *
     * @return void
     */
    private function swapTurns()
    {
        $this->current = $this->current === self::WHITE ? self::BLACK : self::WHITE;
    }

    /**
     * Get the state in array form.
     *
     * @return array
     */
    public function toArray()
    {
        return ['board' => $this->board, 'started' => $this->started, 'current' => $this->current, 'winner' => $this->getWinner()];
    }
}
