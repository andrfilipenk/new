<?php
// app/Core/Exception/ExceptionHandler.php
namespace Core\Exception;

use Core\Di\Injectable;

class ExceptionHandler
{
    use Injectable;

    /**
     * 
     * @param Throwable $e
     * @return object
     */
    public function handle($e)
    {
        $config     = $this->getDI()->get('config');
        /** @var \Core\Logging\Logger $logger */
        $logger     = $this->getDI()->get('logger');

        $context    = [
            'code'      => $e->getCode(),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'trace'     => $e->getTraceAsString()
        ];

        /** @var BaseException $e */
        if ($e instanceof BaseException) {
            $logger->warning($e->getMessage(), $context);
        } elseif ($e instanceof NotFoundException || $e instanceof AccessDeniedException) {
            $logger->notice($e->getMessage(), $context);
        } else {
            $logger->error($e->getMessage(), $context);
        }

        $statusCode = method_exists($e, 'getHttpStatusCode') ? $e->getHttpStatusCode() : 500;
        $result = new \stdClass;
        $result->statusCode = $statusCode;
        $result->message = $e->getMessage();

        $debugOn = $config['app']['debug'] ?? false;
        if ($debugOn) {
            $result->file = $e->getFile();
            $result->line = $e->getLine();
            $result->context = $context;
        }
        return $result;
    }
}