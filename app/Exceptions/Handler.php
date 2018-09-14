<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        if ($request->expectsJson()) {
            if($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return $this->errorsException('Incorect route', Response::HTTP_NOT_FOUND);
            }

            if($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return $this->errorsException('Model not found', Response::HTTP_NOT_FOUND);
            }

            if ($e instanceof \Prettus\Validator\Exceptions\ValidatorException) {
                $message = 'Failed';
                $errors  = $e->getMessageBag();
                $status  = Response::HTTP_UNPROCESSABLE_ENTITY;
                return response()->json(
                    compact('message', 'errors', 'status'),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            if($e instanceof \Illuminate\Auth\AuthenticationException) {
                return $this->errorsException('Unauthentication', Response::HTTP_UNAUTHORIZED);
            }

            if($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                return $this->errorsException('Method is not defined', Response::HTTP_METHOD_NOT_ALLOWED);
            }
        }

        return parent::render($request, $e);
    }

    public function errorsException($message, $status)
    {
        return response()->json([
            'error'      => true,
            'message'     => $message,
            'status'      => $status
        ], $status);
    }
}
