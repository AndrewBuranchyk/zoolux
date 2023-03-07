<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        if (request()->is('api/*')) {
            $this->renderable(function (ValidationException $e) {
                logger($e->errors());
                return response()->json($e->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
            })
                ->renderable(function (AuthenticationException $e) {
                    return response()->json([
                        'message' => (!empty($e->getMessage()))? $e->getMessage() : 'Unauthorized'
                    ], Response::HTTP_UNAUTHORIZED);
                })
                ->renderable(function (NotFoundHttpException $e) {
                    return response()->json([
                        'message' => (!empty($e->getMessage()))? $e->getMessage() : 'Not found'
                    ], Response::HTTP_NOT_FOUND);
                })
                ->renderable(function (Throwable $e) {
                    $message = (!empty($e->getMessage()))? $e->getMessage() : 'Internal server error';
                    logger($message);
                    return response()->json([
                        'message' => $message
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
                });
        }
    }
}
