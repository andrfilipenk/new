<?php

namespace Core\Calendar\Renderers;

use Core\Calendar\CalendarConfig;
use Core\Calendar\Models\CalendarData;
use Core\Calendar\Models\Dimensions;
use Core\Calendar\Models\Bar;
use Core\Calendar\Svg\SvgDocument;
use Core\Calendar\Svg\SvgBuilder;
use Core\Calendar\Styling\StyleManager;
use Core\Calendar\Styling\ThemeInterface;
use Core\Calendar\Events\EventHandler;
use Core\Calendar\Utilities\DateCalculator;
use Core\Calendar\Exceptions\RenderException;

/**
 * Abstract base class for view renderers
 */
abstract class AbstractViewRenderer implements ViewRendererInterface
{
    protected StyleManager $styleManager;

    public function __construct(?StyleManager $styleManager = null)
    {
        $this->styleManager = $styleManager ?? new StyleManager();
    }

    /**
     * Render the complete calendar
     */
    public function render(CalendarConfig $config, CalendarData $data): string
    {
        try {
            $dimensions = $this->calculateDimensions($config);
            $svg = new SvgDocument($dimensions->getWidth(), $dimensions->getHeight());
            
            // Apply theme if provided
            if ($config->getTheme()) {
                $this->styleManager->setTheme($config->getTheme());
            }
            
            // Add styles
            $svg->addStyle($this->styleManager->generateStyles($config->getName()));
            
            // Add calendar class group
            $calendarGroup = SvgBuilder::group([
                'class' => "calendar-{$config->getName()}",
            ]);
            
            // Render header
            $header = $this->renderHeader($config, $dimensions);
            if ($header) {
                $calendarGroup->appendChild($header);
            }
            
            // Render body
            $body = $this->renderBody($config, $data, $dimensions);
            if ($body) {
                $calendarGroup->appendChild($body);
            }
            
            // Render bars
            $bars = $this->renderBars($config, $data, $dimensions);
            if ($bars) {
                $calendarGroup->appendChild($bars);
            }
            
            $svg->addElement($calendarGroup);
            
            return $svg->render();
        } catch (\Exception $e) {
            throw new RenderException(
                $config->getViewType(),
                'render',
                "Failed to render {$config->getViewType()} view: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Render the header section
     */
    abstract protected function renderHeader(CalendarConfig $config, Dimensions $dimensions): ?\Core\Calendar\Svg\SvgElement;

    /**
     * Render the body section (main calendar grid)
     */
    abstract protected function renderBody(CalendarConfig $config, CalendarData $data, Dimensions $dimensions): ?\Core\Calendar\Svg\SvgElement;

    /**
     * Render bar overlays
     */
    abstract protected function renderBars(CalendarConfig $config, CalendarData $data, Dimensions $dimensions): ?\Core\Calendar\Svg\SvgElement;

    /**
     * Get theme
     */
    protected function getTheme(CalendarConfig $config): ThemeInterface
    {
        return $config->getTheme() ?? $this->styleManager->getTheme();
    }

    /**
     * Create clickable attributes for an element
     */
    protected function getClickableAttrs(CalendarConfig $config, string $type, array $data = []): array
    {
        if (!$config->isClickable()) {
            return [];
        }
        
        $eventHandler = new EventHandler($config->getName(), $config->getInteractionConfig());
        return $eventHandler->getClickAttributes($type, $data);
    }

    /**
     * Helper to create a text element with theme styling
     */
    protected function createThemedText(
        string $content,
        float $x,
        float $y,
        CalendarConfig $config,
        array $additionalAttrs = []
    ): \Core\Calendar\Svg\SvgElement {
        $theme = $this->getTheme($config);
        
        $attrs = array_merge([
            'fill' => $theme->getTextColor(),
            'font-family' => $theme->getFontFamily(),
            'font-size' => $theme->getFontSize(),
        ], $additionalAttrs);
        
        return SvgBuilder::text($content, $x, $y, $attrs);
    }

    /**
     * Helper to create a rectangle with theme styling
     */
    protected function createThemedRect(
        float $x,
        float $y,
        float $width,
        float $height,
        CalendarConfig $config,
        array $additionalAttrs = []
    ): \Core\Calendar\Svg\SvgElement {
        $theme = $this->getTheme($config);
        
        $attrs = array_merge([
            'fill' => $theme->getBackgroundColor(),
            'stroke' => $theme->getBorderColor(),
            'stroke-width' => 1,
        ], $additionalAttrs);
        
        return SvgBuilder::rect($x, $y, $width, $height, $attrs);
    }
}
