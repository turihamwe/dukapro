<?php

namespace App\Exceptions;

use App\Services\ErrorLogService;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
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
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Log user-impacting failures before Laravel skips HttpException reporting.
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function report(Throwable $e)
    {
        $e = $this->mapException($e);

        try {
            app(ErrorLogService::class)->logBackendException($e, request());
        } catch (Throwable $loggingFailure) {
            // Never break Laravel's default exception reporting.
        }

        parent::report($e);
    }
}
