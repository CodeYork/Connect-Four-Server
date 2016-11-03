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

namespace CodeYork\Tests\ConnectFour;

use CodeYork\ConnectFour\Engine;

class WebTest extends AbstractTestCase
{
    /**
     * Test the homepage works.
     *
     * @return void
     */
    public function testHome()
    {
        $this->get('/');

        $this->assertResponseOk();

        $this->seeJsonEquals(['success' => ['message' => 'You have arrived!']]);
    }

    /**
     * Test can create a new game.
     *
     * @return void
     */
    public function testCanCreate()
    {
        $this->post('game');

        $this->assertResponseOk();

        $this->seeJsonStructure(['success' => ['message'], 'data' => ['game', 'player']]);
        $this->seeJsonContains(['success' => ['message' => 'Your game will begin once the other player joins!']]);
    }

    /**
     * Test can join an exisiting game.
     *
     * @return void
     */
    public function testCanJoin()
    {
        $game = app(Engine::class)->start()['game'];

        $this->post("game/{$game}/join");

        $this->assertResponseOk();

        $this->seeJsonStructure(['success' => ['message'], 'data' => ['game', 'player']]);
        $this->seeJsonContains(['success' => ['message' => 'Your game will begin shortly!']]);
    }

    /**
     * Test cannot join a game twice.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @expectedExceptionMessage The given game is already full.
     *
     * @return void
     */
    public function testCannotJoinTwice()
    {
        $game = app(Engine::class)->start()['game'];
        app(Engine::class)->join($game);

        $this->post("game/{$game}/join");

        $this->seeJsonStructure(['success' => ['message'], 'data' => ['game', 'player']]);
    }

    /**
     * Test can get the state of an exisiting game.
     *
     * @return void
     */
    public function testCanGetInitialState()
    {
        $game = app(Engine::class)->start()['game'];

        $this->get("game/{$game}");

        $this->seeJsonEquals(['data' => ['board' => $this->getEmptyBoard(), 'current' => 0, 'started' => false, 'winner' => null]]);
    }

    /**
     * Test can get the state of an exisiting game.
     *
     * @return void
     */
    public function testCanGetStartedState()
    {
        $game = app(Engine::class)->start()['game'];
        app(Engine::class)->join($game);

        $this->get("game/{$game}");

        $this->seeJsonEquals(['data' => ['board' => $this->getEmptyBoard(), 'current' => 0, 'started' => true, 'winner' => null]]);
    }

    /**
     * Test can make move as white player.
     *
     * @return void
     */
    public function testMakeMoveAsWhitePlayer()
    {
        $game = app(Engine::class)->start()['game'];
        app(Engine::class)->join($game);

        $this->post("game/{$game}/move?player=0&col=3");

        $this->seeJsonEquals(['success' => ['message' => 'You move has been accepted!']]);
    }

    /**
     * Test can get state after white move.
     *
     * @return void
     */
    public function testGetStateAfterWhiteMove()
    {
        $game = app(Engine::class)->start()['game'];
        app(Engine::class)->join($game);
        app(Engine::class)->move($game, 0, 2);

        $this->get("game/{$game}");

        $board = $this->getEmptyBoard();

        $board[5][2] = 0;

        $this->seeJsonEquals(['data' => ['board' => $board, 'current' => 1, 'started' => true, 'winner' => null]]);
    }

    /**
     * Test can make move as black player.
     *
     * @return void
     */
    public function testMakeMoveAsBlackPlayer()
    {
        $game = app(Engine::class)->start()['game'];
        app(Engine::class)->join($game);
        app(Engine::class)->move($game, 0, 2);

        $this->post("game/{$game}/move?player=1&col=1");

        $this->seeJsonEquals(['success' => ['message' => 'You move has been accepted!']]);
    }

    /**
     * Test can get state after black move.
     *
     * @return void
     */
    public function testGetStateAfterBlackMove()
    {
        $game = app(Engine::class)->start()['game'];
        app(Engine::class)->join($game);
        app(Engine::class)->move($game, 0, 4);
        app(Engine::class)->move($game, 1, 4);

        $this->get("game/{$game}");

        $board = $this->getEmptyBoard();

        $board[5][4] = 0;
        $board[4][4] = 1;

        $this->seeJsonEquals(['data' => ['board' => $board, 'current' => 0, 'started' => true, 'winner' => null]]);
    }

    /**
     * Get an empty game board array.
     *
     * @return array
     */
    protected function getEmptyBoard()
    {
        return [
            [null, null, null, null, null, null, null],
            [null, null, null, null, null, null, null],
            [null, null, null, null, null, null, null],
            [null, null, null, null, null, null, null],
            [null, null, null, null, null, null, null],
            [null, null, null, null, null, null, null],
        ];
    }
}
