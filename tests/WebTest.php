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
     * Test cannot join with a bad game id.
     *
     * @return void
     */
    public function testCannotJoinWithBadGameId()
    {
        $this->post('game/abc/join');

        $this->assertResponseStatus(404);
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
     * Test cannot start too early.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @expectedExceptionMessage The given game has not started yet.
     *
     * @return void
     */
    public function testCannotStartTooEarly()
    {
        $game = app(Engine::class)->start()['game'];

        $this->post("game/{$game}/move?player=0&col=3");
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
     * Test cannot get state with a bad game id.
     *
     * @return void
     */
    public function testCannotGetStateWithBadGameId()
    {
        $this->get('game/abc');

        $this->assertResponseStatus(404);
    }

    /**
     * Test cannot move with a bad game id.
     *
     * @return void
     */
    public function testCannotMoveWithBadGameId()
    {
        $this->post('game/abc/move?player=1&col=3');

        $this->assertResponseStatus(404);
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
     * Test can cannot make double move.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @expectedExceptionMessage Your opponent is currently moving.
     *
     * @return void
     */
    public function testCannotMakeDoubleMove()
    {
        $game = app(Engine::class)->start()['game'];
        app(Engine::class)->join($game);
        app(Engine::class)->move($game, 0, 2);

        $this->post("game/{$game}/move?player=0&col=1");
    }

    /**
     * Test can get state after white win.
     *
     * @return void
     */
    public function testGetStateAfterWhiteWin()
    {
        $game = app(Engine::class)->start()['game'];
        app(Engine::class)->join($game);
        app(Engine::class)->move($game, 0, 4);
        app(Engine::class)->move($game, 1, 1);
        app(Engine::class)->move($game, 0, 4);
        app(Engine::class)->move($game, 1, 2);
        app(Engine::class)->move($game, 0, 4);
        app(Engine::class)->move($game, 1, 0);
        app(Engine::class)->move($game, 0, 4);

        $this->get("game/{$game}");

        $board = $this->getEmptyBoard();

        $board[5][4] = 0;
        $board[4][4] = 0;
        $board[3][4] = 0;
        $board[2][4] = 0;
        $board[5][0] = 1;
        $board[5][1] = 1;
        $board[5][2] = 1;

        $this->seeJsonEquals(['data' => ['board' => $board, 'current' => 1, 'started' => true, 'winner' => 0]]);
    }

    /**
     * Test can get state after black win.
     *
     * @return void
     */
    public function testGetStateAfterWhiteBlack()
    {
        $game = app(Engine::class)->start()['game'];
        app(Engine::class)->join($game);
        app(Engine::class)->move($game, 0, 5);
        app(Engine::class)->move($game, 1, 0);
        app(Engine::class)->move($game, 0, 5);
        app(Engine::class)->move($game, 1, 1);
        app(Engine::class)->move($game, 0, 0);
        app(Engine::class)->move($game, 1, 2);
        app(Engine::class)->move($game, 0, 1);
        app(Engine::class)->move($game, 1, 3);

        $this->get("game/{$game}");

        $board = $this->getEmptyBoard();

        $board[4][0] = 0;
        $board[4][1] = 0;
        $board[5][5] = 0;
        $board[4][5] = 0;
        $board[5][0] = 1;
        $board[5][1] = 1;
        $board[5][2] = 1;
        $board[5][3] = 1;

        $this->seeJsonEquals(['data' => ['board' => $board, 'current' => 0, 'started' => true, 'winner' => 1]]);
    }

    /**
     * Test cannot overfill column.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage The given column is already full.
     *
     * @return void
     */
    public function testCannotOverFillColumn()
    {
        $game = app(Engine::class)->start()['game'];
        app(Engine::class)->join($game);
        app(Engine::class)->move($game, 0, 3);
        app(Engine::class)->move($game, 1, 3);
        app(Engine::class)->move($game, 0, 3);
        app(Engine::class)->move($game, 1, 3);
        app(Engine::class)->move($game, 0, 3);
        app(Engine::class)->move($game, 1, 3);

        $this->post("game/{$game}/move?player=0&col=3");
    }

    /**
     * Test that the column must be valid.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage Invalid column index provided.
     *
     * @return void
     */
    public function testCannotProvideInvalidColumn()
    {
        $game = app(Engine::class)->start()['game'];
        app(Engine::class)->join($game);

        $this->post("game/{$game}/move?player=0&col=42");
    }

    /**
     * Test that a column must be provided.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage Not all the required parameters were provided.
     *
     * @return void
     */
    public function testMustProvideColumn()
    {
        $game = app(Engine::class)->start()['game'];
        app(Engine::class)->join($game);

        $this->post("game/{$game}/move?player=0");
    }

    /**
     * Test that a player id must be provided.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage Not all the required parameters were provided.
     *
     * @return void
     */
    public function testMustProvidePlayer()
    {
        $game = app(Engine::class)->start()['game'];
        app(Engine::class)->join($game);

        $this->post("game/{$game}/move?col=0");
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
