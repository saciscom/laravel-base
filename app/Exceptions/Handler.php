<?php

namespace App\Exceptions;

use App\Shared\ApiResponser;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    use ApiResponser;

    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
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
     * @param \Throwable $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Throwable $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        if (config('app.debug') && config('app.env') == 'local') {
            return parent::render($request, $exception);
        }

        # Set default errors.
        $this->setStatus(JsonResponse::HTTP_INTERNAL_SERVER_ERROR)
            ->setMeta($exception->getMessage());

        # Default response for HttpException
        if ($exception instanceof HttpException) {
            $this->setData(null)
                ->setStatus($exception->getStatusCode())
                ->setMeta(__($exception->getMessage()));
        }

        if ($exception instanceof AuthenticationException) {
            $message = $exception->getMessage();
            if ($message == 'Unauthenticated.') {
                $message = 'お手元のカードで、もう一度簡易登録から始めてください。';
            }
            $this->setStatus(JsonResponse::HTTP_UNAUTHORIZED)
                ->setMeta(__($message));
        }

        if ($exception instanceof NotFoundHttpException) {
            $this->setStatus(JsonResponse::HTTP_NOT_FOUND)
                ->setMeta(trans('error.request-not-found'));
        }

        if ($exception instanceof \HttpResponseException) {
            $data = $exception->getResponse()->original;
            $statusCode = $exception->getResponse()->getStatusCode();
            $this->setStatus($statusCode)
                ->setMeta($data['meta']['message']);
        }

        if ($exception instanceof ValidationException) {
            $validationErrors = $exception->validator->errors();
            $this->setStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
                ->setMeta($validationErrors->first())->setData($validationErrors);
        }

        if ($exception instanceof ModelNotFoundException) {
            $this->setStatus(JsonResponse::HTTP_NOT_FOUND)
                ->setMeta(trans('error.request-not-found'));
        }

        if ($exception instanceof AuthorizationException) {
            $this->setStatus(JsonResponse::HTTP_FORBIDDEN)
                ->setMeta(__('messages.request_forbidden'));
        }

        if ($exception instanceof BadRequestHttpException) {
            $this->setStatus(JsonResponse::HTTP_BAD_REQUEST)
                ->setMeta(trans('error.bad-request'));
        }

        if ($exception instanceof UnauthorizedException) {
            $this->setMeta(trans('auth.errors.unauthenticated'))
                ->setStatus(JsonResponse::HTTP_UNAUTHORIZED);
        }

        if ($exception instanceof UnauthorizedHttpException) {
            $this->setStatus(JsonResponse::HTTP_UNAUTHORIZED)
                ->setMeta(__('messages.token_expired'));
        }

        if ($exception instanceof ConnectException) {
            $this->setStatus(JsonResponse::HTTP_REQUEST_TIMEOUT)
                ->setMeta($exception->getMessage());
        }

        if ($exception instanceof BadResponseException) {
            $responseBody = $exception->getResponse();
            $errors = [];
            $res = json_decode($responseBody->getBody()->getContents(), true);
            $message = empty($res['meta']) ? $res["message"] : $res['meta']['message'];

            if (!empty($res['meta']['errors'])) {
                $errors = ["errors" => $res['meta']['errors']];
            }

            return $this->setStatus($responseBody->getStatusCode())
                ->setMeta($message, $errors)
                ->jsonOut();
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            $this->setStatus(JsonResponse::HTTP_METHOD_NOT_ALLOWED)
                ->setMeta(__('messages.method_not_allowed'));
        }

        if ($exception instanceof QueryException) {
            $this->setStatus(JsonResponse::HTTP_BAD_REQUEST)->setMeta(implode(",", $exception->errorInfo));
        }
        return $this->jsonOut();
    }
}
