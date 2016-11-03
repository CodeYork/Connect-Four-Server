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

use Illuminate\Contracts\Container\Container;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use CodeYork\ConnectFour\Exceptions\GameAlreadyFullException;
use CodeYork\ConnectFour\Exceptions\GameNotFoundException;
use CodeYork\ConnectFour\Exceptions\InvalidMoveException;
use CodeYork\ConnectFour\Exceptions\OpponentMovingException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->get('/', function () {
            return new JsonResponse([
                'success' => ['message' => 'You have arrived!'],
            ]);
        });

        $this->app->post('game', function (Request $request) {
            $data = app(Engine::class)->start();

            return new JsonResponse([
                'success' => ['message' => 'Your game will begin once the other player joins!'],
                'data'    => $data,
            ], 200);
        });

        $this->app->get('game/{game}', function (string $game, Request $request) {
            try {
                $data = app(Engine::class)->get($game);
            } catch (GameNotFoundException $e) {
                throw new NotFoundHttpException($e->getMessage());
            }

            return new JsonResponse([
                'data' => $data,
            ], 200);
        });

        $this->app->post('game/{game}/join', function (string $game, Request $request) {
            try {
                $data = app(Engine::class)->join($game);
            } catch (GameNotFoundException $e) {
                throw new NotFoundHttpException($e->getMessage());
            } catch (GameAlreadyFullException $e) {
                throw new AccessDeniedHttpException($e->getMessage());
            }

            return new JsonResponse([
                'success' => ['message' => 'Your game will begin shortly!'],
                'data'    => $data,
            ], 200);
        });

        $this->app->post('game/{game}/move', function (string $game, Request $request) {
            if ($request->get('player') === null || $request->get('col') === null) {
                throw new BadRequestHttpException('Not all the required parameters were provided.');
            }

            try {
                app(Engine::class)->move($game, (int) $request->get('player'), (int) $request->get('col'));
            } catch (GameNotFoundException $e) {
                throw new NotFoundHttpException($e->getMessage());
            } catch (OpponentMovingException $e) {
                throw new AccessDeniedHttpException($e->getMessage());
            } catch (InvalidMoveException $e) {
                throw new BadRequestHttpException($e->getMessage());
            }

            return new JsonResponse([
                'success' => ['message' => 'You move has been accepted!'],
            ], 200);
        });
    }
}
