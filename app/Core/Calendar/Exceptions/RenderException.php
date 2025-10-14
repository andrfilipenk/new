<?php

namespace Core\Calendar\Exceptions;

/**
 * Exception thrown when rendering fails
 */
class RenderException extends CalendarException
{
    private string $viewType;
    private string $renderStage;

    public function __construct(string $viewType, string $renderStage, string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        $this->viewType = $viewType;
        $this->renderStage = $renderStage;
        
        if ($message === '') {
            $message = sprintf(
                'Rendering failed for view type "%s" at stage "%s"',
                $viewType,
                $renderStage
            );
        }
        
        parent::__construct($message, $code, $previous);
    }

    public function getViewType(): string
    {
        return $this->viewType;
    }

    public function getRenderStage(): string
    {
        return $this->renderStage;
    }
}
