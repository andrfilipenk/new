<?php

namespace App\Services;

class ExportService
{
    /**
     * Export data to CSV
     */
    public static function toCSV(array $data, array $headers = [], string $filename = 'export.csv'): string
    {
        $output = fopen('php://temp', 'r+');
        
        // Add headers if provided
        if (!empty($headers)) {
            fputcsv($output, $headers);
        } elseif (!empty($data)) {
            // Auto-generate headers from first row
            fputcsv($output, array_keys($data[0]));
        }
        
        // Add data rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        // Save to file
        $filepath = storage_path('exports/' . $filename);
        
        // Create directory if not exists
        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($filepath, $csv);
        
        return $filepath;
    }
    
    /**
     * Export data to JSON
     */
    public static function toJSON(array $data, string $filename = 'export.json', bool $pretty = true): string
    {
        $json = json_encode($data, $pretty ? JSON_PRETTY_PRINT : 0);
        
        $filepath = storage_path('exports/' . $filename);
        
        // Create directory if not exists
        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($filepath, $json);
        
        return $filepath;
    }
    
    /**
     * Export data to XML
     */
    public static function toXML(array $data, string $rootElement = 'data', string $filename = 'export.xml'): string
    {
        $xml = new \SimpleXMLElement("<{$rootElement}/>");
        
        self::arrayToXML($data, $xml);
        
        $filepath = storage_path('exports/' . $filename);
        
        // Create directory if not exists
        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $xml->asXML($filepath);
        
        return $filepath;
    }
    
    /**
     * Convert array to XML
     */
    private static function arrayToXML(array $data, \SimpleXMLElement &$xml): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $key = 'item' . $key;
                }
                $subnode = $xml->addChild($key);
                self::arrayToXML($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars((string)$value));
            }
        }
    }
    
    /**
     * Download file
     */
    public static function download(string $filepath, $filename = null): void
    {
        if (!file_exists($filepath)) {
            throw new \Exception('File not found');
        }
        
        $filename = $filename ?? basename($filepath);
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        readfile($filepath);
        exit;
    }
}
