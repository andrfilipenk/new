<?php

namespace Core\Calendar\Renderers;

use Core\Calendar\CalendarConfig;
use Core\Calendar\Models\CalendarData;
use Core\Calendar\Models\Dimensions;
use DateTimeImmutable;

/**
 * Custom period view renderer with auto-detection of appropriate view type
 */
class CustomViewRenderer extends AbstractViewRenderer
{
    private ViewRendererInterface $delegateRenderer;

    public function __construct($styleManager = null)
    {
        parent::__construct($styleManager);
    }

    public function calculateDimensions(CalendarConfig $config): Dimensions
    {
        $this->selectDelegateRenderer($config);
        return $this->delegateRenderer->calculateDimensions($config);
    }

    protected function renderHeader(CalendarConfig $config, Dimensions $dimensions): ?\Core\Calendar\Svg\SvgElement
    {
        // Delegate to the selected renderer
        return null; // Will be handled by the delegate in render()
    }

    protected function renderBody(CalendarConfig $config, CalendarData $data, Dimensions $dimensions): ?\Core\Calendar\Svg\SvgElement
    {
        // Delegate to the selected renderer
        return null; // Will be handled by the delegate in render()
    }

    protected function renderBars(CalendarConfig $config, CalendarData $data, Dimensions $dimensions): ?\Core\Calendar\Svg\SvgElement
    {
        // Delegate to the selected renderer
        return null; // Will be handled by the delegate in render()
    }

    /**
     * Override render to delegate to the appropriate renderer
     */
    public function render(CalendarConfig $config, CalendarData $data): string
    {
        $this->selectDelegateRenderer($config);
        return $this->delegateRenderer->render($config, $data);
    }

    /**
     * Select the appropriate renderer based on date range
     */
    private function selectDelegateRenderer(CalendarConfig $config): void
    {
        if (isset($this->delegateRenderer)) {
            return;
        }

        $startDate = DateTimeImmutable::createFromInterface($config->getStartDate());
        $endDate = DateTimeImmutable::createFromInterface($config->getEndDate());
        $days = $startDate->diff($endDate)->days;

        // Auto-detect view type based on date range
        if ($days === 0) {
            $this->delegateRenderer = new DayViewRenderer($this->styleManager);
        } elseif ($days <= 7) {
            $this->delegateRenderer = new WeekViewRenderer($this->styleManager);
        } elseif ($days <= 35) {
            $this->delegateRenderer = new MultiWeekRenderer($this->styleManager);
        } elseif ($days <= 365) {
            $this->delegateRenderer = new MonthViewRenderer($this->styleManager);
        } else {
            $this->delegateRenderer = new YearViewRenderer($this->styleManager);
        }
    }
}
