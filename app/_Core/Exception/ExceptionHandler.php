<?php
namespace Core\Exception;

use Core\Di\Injectable;
use Core\Validation\ValidationException;
use Throwable;

class ExceptionHandler
{
    use Injectable;

    /**
     * 
     * @param Throwable $e
     * @return \Core\Http\Response
     */
    public function handle(Throwable $e)
    {
        $config     = $this->getDI()->get('config');
        /** @var \Core\Logging\Logger $logger */
        $logger     = $this->getDI()->get('logger');
        /** @var \Core\Http\Request $request */
        $request    = $this->getDI()->get('request');
        /** @var \Core\Http\Response $response */
        $response   = $this->getDI()->get('response');

        $context    = [
            'code'      => $e->getCode(),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'trace'     => $e->getTraceAsString()
        ];

        /** @var BaseException $e */
        if ($e instanceof ValidationException) {
            $logger->warning($e->getMessage(), $context);
        } elseif ($e instanceof NotFoundException || $e instanceof AccessDeniedException) {
            $logger->notice($e->getMessage(), $context);
        } else {
            $logger->error($e->getMessage(), $context);
        }

        $statusCode = method_exists($e, 'getHttpStatusCode') ? $e->getHttpStatusCode() : 500;
        $response->setStatusCode($statusCode);
        $message = $e->getMessage();

        $debugOn = $config[['app']['debug']] ?? false;
        if ($debugOn) {
            $message = [
                'message'   => $e->getMessage(), 
                'file'      => $e->getFile(), 
                'line'      => $e->getLine()
            ];
        }

        if ($request->isAjax() ||$request->isJson()) {
            $response->setContent(json_encode(['error' => $message]));
            $response->setHeader('Content-Type', 'application/json');
        } else {
            $controller = new \Base\Controller\Error();
            $controller->setDI($this->getDI());
            $action = match ($statusCode) {
                403     => 'deniedAction',
                404     => 'notfoundAction',
                default => 'errorAction'
            };
            $response->setContent($controller->$action());
        }
        return $response;
    }
}