<?php

namespace Core\Calendar\Events;

/**
 * Generates JavaScript for calendar interactivity
 */
class ScriptGenerator
{
    /**
     * Generate JavaScript for calendar interactions
     */
    public static function generate(string $calendarName, InteractionConfig $config): string
    {
        $js = "(function() {\n";
        $js .= "  const calendar = {\n";
        $js .= "    name: '{$calendarName}',\n";
        $js .= "    selectedDate: null,\n";
        $js .= "    rangeStart: null,\n";
        $js .= "    rangeEnd: null,\n";
        
        // Date click handler
        if ($config->isClickable() || $config->isSelectable()) {
            $js .= self::generateDateClickHandler($calendarName, $config);
        }
        
        // Range selection handler
        if ($config->isRangeSelection()) {
            $js .= self::generateRangeSelectionHandler($calendarName, $config);
        }
        
        // Bar click handler
        if ($config->getOnBarClick()) {
            $js .= self::generateBarClickHandler($calendarName, $config);
        }
        
        // Week click handler
        if ($config->getOnWeekClick()) {
            $js .= self::generateWeekClickHandler($calendarName, $config);
        }
        
        // Form field bindings
        if (!empty($config->getFormFields())) {
            $js .= self::generateFormFieldBindings($config);
        }
        
        $js .= "  };\n";
        $js .= "  window.calendar_{$calendarName} = calendar;\n";
        $js .= "})();\n";
        
        return $js;
    }

    private static function generateDateClickHandler(string $calendarName, InteractionConfig $config): string
    {
        $customHandler = $config->getOnDateClick();
        
        $js = "    onDateClick: function(date, element) {\n";
        $js .= "      this.selectedDate = date;\n";
        $js .= "      // Update visual state\n";
        $js .= "      document.querySelectorAll('.calendar-{$calendarName} .cell.selected').forEach(function(el) {\n";
        $js .= "        el.classList.remove('selected');\n";
        $js .= "      });\n";
        $js .= "      if (element) element.classList.add('selected');\n";
        
        if ($customHandler) {
            $js .= "      // Custom handler\n";
            $js .= "      if (typeof {$customHandler} === 'function') {\n";
            $js .= "        {$customHandler}(date, element);\n";
            $js .= "      }\n";
        }
        
        $js .= "    },\n";
        
        return $js;
    }

    private static function generateRangeSelectionHandler(string $calendarName, InteractionConfig $config): string
    {
        $customHandler = $config->getOnRangeSelect();
        
        $js = "    onRangeSelect: function(startDate, endDate) {\n";
        $js .= "      this.rangeStart = startDate;\n";
        $js .= "      this.rangeEnd = endDate;\n";
        
        if ($customHandler) {
            $js .= "      // Custom handler\n";
            $js .= "      if (typeof {$customHandler} === 'function') {\n";
            $js .= "        {$customHandler}(startDate, endDate);\n";
            $js .= "      }\n";
        }
        
        $js .= "    },\n";
        $js .= "    selectRange: function(date, element) {\n";
        $js .= "      if (!this.rangeStart || this.rangeEnd) {\n";
        $js .= "        this.rangeStart = date;\n";
        $js .= "        this.rangeEnd = null;\n";
        $js .= "        element.classList.add('range-start');\n";
        $js .= "      } else {\n";
        $js .= "        this.rangeEnd = date;\n";
        $js .= "        element.classList.add('range-end');\n";
        $js .= "        this.onRangeSelect(this.rangeStart, this.rangeEnd);\n";
        $js .= "      }\n";
        $js .= "    },\n";
        
        return $js;
    }

    private static function generateBarClickHandler(string $calendarName, InteractionConfig $config): string
    {
        $customHandler = $config->getOnBarClick();
        
        $js = "    onBarClick: function(barId, barData, element) {\n";
        
        if ($customHandler) {
            $js .= "      if (typeof {$customHandler} === 'function') {\n";
            $js .= "        {$customHandler}(barId, barData, element);\n";
            $js .= "      }\n";
        }
        
        $js .= "    },\n";
        
        return $js;
    }

    private static function generateWeekClickHandler(string $calendarName, InteractionConfig $config): string
    {
        $customHandler = $config->getOnWeekClick();
        
        $js = "    onWeekClick: function(weekNumber, year, element) {\n";
        
        if ($customHandler) {
            $js .= "      if (typeof {$customHandler} === 'function') {\n";
            $js .= "        {$customHandler}(weekNumber, year, element);\n";
            $js .= "      }\n";
        }
        
        $js .= "    },\n";
        
        return $js;
    }

    private static function generateFormFieldBindings(InteractionConfig $config): string
    {
        $fields = $config->getFormFields();
        $js = "    updateFormFields: function(date, isStart) {\n";
        
        if (isset($fields['date'])) {
            $js .= "      const dateField = document.getElementById('{$fields['date']}');\n";
            $js .= "      if (dateField) dateField.value = date;\n";
        }
        
        if (isset($fields['start']) && isset($fields['end'])) {
            $js .= "      if (isStart) {\n";
            $js .= "        const startField = document.getElementById('{$fields['start']}');\n";
            $js .= "        if (startField) startField.value = date;\n";
            $js .= "      } else {\n";
            $js .= "        const endField = document.getElementById('{$fields['end']}');\n";
            $js .= "        if (endField) endField.value = date;\n";
            $js .= "      }\n";
        }
        
        $js .= "    },\n";
        
        return $js;
    }
}
