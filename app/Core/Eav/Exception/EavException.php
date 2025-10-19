<?php
// app/Eav/Exception/EavException.php
namespace Eav\Exception;

use Exception;

/**
 * Base EAV Exception
 * 
 * All EAV-related exceptions extend from this base class.
 */
abstract class EavException extends Exception
{
    /**
     * Context information for debugging
     */
    protected array $context = [];

    /**
     * Set context information
     */
    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Get context information
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get formatted error message with context
     */
    public function getFullMessage(): string
    {
        $message = $this->getMessage();
        if (!empty($this->context)) {
            $message .= ' | Context: ' . json_encode($this->context);
        }
        return $message;
    }
}
