<?php

namespace App\Package\Response;

use App\Package\Exceptions\BaseException;
use Slim\Http\Request;
use Slim\Http\Response;
use Throwable;

class ExceptionMiddleware
{
    public function __invoke(Request $request, Response $response, $next)
    {
        try {
            return $next($request, $response);
        } catch (BaseException $exception) {
            $this->logException($exception);
            return $exception->respond($response);
        } catch (Throwable $exception) {
            $this->logException($exception);
            return ResponseFactory::internalServerError($response);
        }
    }

    private function logException(Throwable $throwable): void
    {
        if ($throwable instanceof BaseException && $throwable->shouldDisplayError()) {
            return; // Don't log client errors
        }
        if (extension_loaded('newrelic')) {
            newrelic_notice_error($throwable);
        }
        if ($throwable instanceof \JsonSerializable) {
            error_log("Exception: " . json_encode($throwable));
            return;
        }
        error_log("Exception: " . json_encode($this->throwableAsArray($throwable)));
    }

    private function throwableAsArray(Throwable $throwable): array
    {
        return [
            'class'       => get_class($throwable),
            'message'     => $throwable->getMessage(),
            'code'        => $throwable->getCode(),
            'file'        => $throwable->getFile(),
            'line'        => $throwable->getLine(),
            'trace'       => $throwable->getTrace(),
            'traceString' => $throwable->getTraceAsString(),
        ];
    }
}