<?php
/*
 * This is an unofficial snippet, with no support! You may copy-paste and use at your risk.
 */

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    public function render($request, Exception $exception)
    {
        if (! $request->is('api/*')) {
            return $this->getApiResponse($exception);
        }

        return parent::render($request, $exception);
    }

    protected function getApiResponse($exception)
    {
        if ($exception instanceof AuthenticationException) {
            return api_error(401);
        } elseif ($exception instanceof AuthorizationException) {
            return api_error(403);
        } elseif ($exception instanceof ModelNotFoundException) {
            return api_error(404);
        } elseif ($exception instanceof ValidationException) {
            return api_error(422, null, $exception->validator->errors()->getMessages());
        } elseif ($exception instanceof HttpException) {
            return api_error($exception->getStatusCode());
        }

        return api_error(500);
    }
}
