<?php

namespace Core\Calendar\Events;

/**
 * Handles calendar event processing and script generation
 */
class EventHandler
{
    private InteractionConfig $config;
    private string $calendarName;

    public function __construct(string $calendarName, ?InteractionConfig $config = null)
    {
        $this->calendarName = $calendarName;
        $this->config = $config ?? new InteractionConfig();
    }

    public function getConfig(): InteractionConfig
    {
        return $this->config;
    }

    public function setConfig(InteractionConfig $config): void
    {
        $this->config = $config;
    }

    /**
     * Generate client-side JavaScript for event handling
     */
    public function generateScript(): string
    {
        return ScriptGenerator::generate($this->calendarName, $this->config);
    }

    /**
     * Get data attributes for an element to enable clicking
     */
    public function getClickAttributes(string $type, array $data = []): array
    {
        $attrs = [
            'data-calendar' => $this->calendarName,
            'data-type' => $type,
        ];
        
        foreach ($data as $key => $value) {
            $attrs["data-{$key}"] = is_scalar($value) ? $value : json_encode($value);
        }
        
        if ($this->config->isClickable()) {
            $attrs['style'] = (isset($attrs['style']) ? $attrs['style'] . '; ' : '') . 'cursor: pointer;';
        }
        
        return $attrs;
    }

    /**
     * Generate onclick attribute value
     */
    public function getOnClickHandler(string $type, array $params = []): string
    {
        $calendarVar = "window.calendar_{$this->calendarName}";
        
        switch ($type) {
            case 'date':
                $date = $params['date'] ?? 'null';
                return "{$calendarVar}.onDateClick('{$date}', this)";
                
            case 'bar':
                $barId = $params['barId'] ?? '';
                $barData = json_encode($params['barData'] ?? []);
                return "{$calendarVar}.onBarClick('{$barId}', {$barData}, this)";
                
            case 'week':
                $weekNum = $params['weekNumber'] ?? 0;
                $year = $params['year'] ?? date('Y');
                return "{$calendarVar}.onWeekClick({$weekNum}, {$year}, this)";
                
            default:
                return '';
        }
    }
}
