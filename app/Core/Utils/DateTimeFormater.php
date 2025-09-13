<?php
namespace Core\Utils;


// In your view templates or as a view helper
class DateTimeFormatter
{
    public static function format(\DateTimeInterface $datetime, string $format = 'Y-m-d H:i:s'): string
    {
        return $datetime->format($format);
    }
    
    public static function humanReadable(\DateTimeInterface $datetime): string
    {
        $now = new \DateTimeImmutable();
        $diff = $now->diff($datetime);
        
        if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
        if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
        if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
        if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        
        return 'Just now';
    }
    
    public static function timezoneSelect(string $name, string $selected = null, array $attributes = []): string
    {
        $timezones = DateTimeHelper::getTimezoneList();
        $html = '<select name="' . $name . '"';
        
        foreach ($attributes as $attr => $value) {
            $html .= ' ' . $attr . '="' . htmlspecialchars($value) . '"';
        }
        
        $html .= '>';
        
        foreach ($timezones as $value => $label) {
            $selectedAttr = $selected === $value ? ' selected' : '';
            $html .= '<option value="' . $value . '"' . $selectedAttr . '>' . $label . '</option>';
        }
        
        $html .= '</select>';
        
        return $html;
    }
}