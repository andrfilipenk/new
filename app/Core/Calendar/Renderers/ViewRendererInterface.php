<?php

namespace Core\Calendar\Renderers;

use Core\Calendar\CalendarConfig;
use Core\Calendar\Models\CalendarData;
use Core\Calendar\Models\Dimensions;

/**
 * Interface for calendar view renderers
 */
interface ViewRendererInterface
{
    /**
     * Render the calendar view
     */
    public function render(CalendarConfig $config, CalendarData $data): string;

    /**
     * Calculate dimensions for the view
     */
    public function calculateDimensions(CalendarConfig $config): Dimensions;
}
