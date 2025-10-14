<?php

namespace Core\Calendar;

use Core\Calendar\Models\Bar;
use Core\Calendar\Models\DateRange;
use Core\Calendar\Models\CalendarData;
use Core\Calendar\Renderers\ViewRendererInterface;
use Core\Calendar\Renderers\MonthViewRenderer;
use Core\Calendar\Events\EventHandler;
use Core\Calendar\Exceptions\RenderException;

/**
 * Immutable calendar instance
 */
class Calendar
{
    private CalendarConfig $config;
    private ViewRendererInterface $renderer;
    private EventHandler $eventHandler;

    public function __construct(CalendarConfig $config, ?ViewRendererInterface $renderer = null)
    {
        $this->config = $config;
        $this->renderer = $renderer ?? $this->createDefaultRenderer();
        $this->eventHandler = new EventHandler($config->getName(), $config->getInteractionConfig());
    }

    private function createDefaultRenderer(): ViewRendererInterface
    {
        return match ($this->config->getViewType()) {
            'day' => new Renderers\DayViewRenderer(),
            'week' => new Renderers\WeekViewRenderer(),
            'multi-week' => new Renderers\MultiWeekRenderer(),
            'month' => new Renderers\MonthViewRenderer(),
            'year' => new Renderers\YearViewRenderer(),
            'custom' => new Renderers\CustomViewRenderer(),
            default => new Renderers\MonthViewRenderer(),
        };
    }

    /**
     * Render the calendar to SVG
     */
    public function render(): string
    {
        try {
            // Get data from provider
            $data = $this->getData();
            
            // Render the calendar
            return $this->renderer->render($this->config, $data);
        } catch (\Exception $e) {
            throw new RenderException(
                $this->config->getViewType(),
                'render',
                "Failed to render calendar: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Force SVG output
     */
    public function toSvg(): string
    {
        return $this->render();
    }

    /**
     * Get configuration
     */
    public function getConfig(): CalendarConfig
    {
        return $this->config;
    }

    /**
     * Get all bars
     * @return Bar[]
     */
    public function getBars(): array
    {
        return $this->getData()->getBars();
    }

    /**
     * Get active date range
     */
    public function getDateRange(): DateRange
    {
        return new DateRange($this->config->getStartDate(), $this->config->getEndDate());
    }

    /**
     * Get client-side JavaScript for interactivity
     */
    public function getClientScript(): string
    {
        return $this->eventHandler->generateScript();
    }

    /**
     * Get calendar name
     */
    public function getName(): string
    {
        return $this->config->getName();
    }

    /**
     * Get data from provider or empty data
     */
    private function getData(): CalendarData
    {
        $provider = $this->config->getDataProvider();
        
        if ($provider === null) {
            return new CalendarData();
        }
        
        $range = $this->getDateRange();
        return $provider->getDataForRange($range);
    }

    /**
     * Get complete HTML output including script tag
     */
    public function toHtml(bool $includeScript = true): string
    {
        $html = $this->render();
        
        if ($includeScript && $this->config->isClickable()) {
            $html .= "\n<script>\n" . $this->getClientScript() . "\n</script>\n";
        }
        
        return $html;
    }
}
