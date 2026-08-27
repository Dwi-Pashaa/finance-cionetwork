<?php

namespace App\Exceptions;

use App\Services\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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

        $this->renderable(function (Throwable $e, $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $request->attributes->set('request_id', $request->attributes->get('request_id') ?? 'req_'.substr(md5(uniqid('', true)), 0, 24));

            if ($e instanceof ValidationException) {
                return ApiResponse::error(
                    'The given data was invalid',
                    'VALIDATION_ERROR',
                    422,
                    $e->errors()
                );
            }

            if ($e instanceof AuthenticationException) {
                return ApiResponse::error('Unauthenticated', 'UNAUTHENTICATED', 401);
            }

            if ($e instanceof AuthorizationException) {
                return ApiResponse::error('Forbidden', 'PERMISSION_DENIED', 403);
            }

            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return ApiResponse::error('Resource not found', 'NOT_FOUND', 404);
            }

            if ($e instanceof ThrottleRequestsException) {
                return ApiResponse::error('Too many requests', 'RATE_LIMITED', 429);
            }

            report($e);

            return ApiResponse::error('Internal server error', 'INTERNAL_ERROR', 500);
        });
    }
}
