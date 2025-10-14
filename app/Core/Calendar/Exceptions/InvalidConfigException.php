<?php

namespace Core\Calendar\Exceptions;

/**
 * Exception thrown when invalid configuration is provided
 */
class InvalidConfigException extends CalendarException
{
    private string $configKey;
    private mixed $providedValue;

    public function __construct(string $configKey, mixed $providedValue, string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        $this->configKey = $configKey;
        $this->providedValue = $providedValue;
        
        if ($message === '') {
            $message = sprintf(
                'Invalid configuration value for "%s": %s',
                $configKey,
                is_scalar($providedValue) ? $providedValue : gettype($providedValue)
            );
        }
        
        parent::__construct($message, $code, $previous);
    }

    public function getConfigKey(): string
    {
        return $this->configKey;
    }

    public function getProvidedValue(): mixed
    {
        return $this->providedValue;
    }
}
