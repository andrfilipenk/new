<?php

namespace App\Services;

class ImportService
{
    /**
     * Import from CSV
     */
    public static function fromCSV(string $filepath, bool $hasHeaders = true): array
    {
        if (!file_exists($filepath)) {
            throw new \Exception('File not found');
        }
        
        $data = [];
        $headers = [];
        $handle = fopen($filepath, 'r');
        
        if ($handle === false) {
            throw new \Exception('Could not open file');
        }
        
        $rowIndex = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if ($rowIndex === 0 && $hasHeaders) {
                $headers = $row;
            } else {
                if (!empty($headers)) {
                    $data[] = array_combine($headers, $row);
                } else {
                    $data[] = $row;
                }
            }
            $rowIndex++;
        }
        
        fclose($handle);
        
        return $data;
    }
    
    /**
     * Import from JSON
     */
    public static function fromJSON(string $filepath): array
    {
        if (!file_exists($filepath)) {
            throw new \Exception('File not found');
        }
        
        $json = file_get_contents($filepath);
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON: ' . json_last_error_msg());
        }
        
        return $data;
    }
    
    /**
     * Import from XML
     */
    public static function fromXML(string $filepath): array
    {
        if (!file_exists($filepath)) {
            throw new \Exception('File not found');
        }
        
        $xml = simplexml_load_file($filepath);
        
        if ($xml === false) {
            throw new \Exception('Invalid XML');
        }
        
        return json_decode(json_encode($xml), true);
    }
    
    /**
     * Validate import data
     */
    public static function validate(array $data, array $rules): array
    {
        $errors = [];
        
        foreach ($data as $index => $row) {
            $rowErrors = [];
            
            foreach ($rules as $field => $rule) {
                if ($rule === 'required' && empty($row[$field])) {
                    $rowErrors[$field] = "Field {$field} is required";
                }
                
                if (str_contains($rule, 'email') && !filter_var($row[$field] ?? '', FILTER_VALIDATE_EMAIL)) {
                    $rowErrors[$field] = "Field {$field} must be a valid email";
                }
            }
            
            if (!empty($rowErrors)) {
                $errors[$index] = $rowErrors;
            }
        }
        
        return $errors;
    }
    
    /**
     * Batch insert data
     */
    public static function batchInsert(string $table, array $data, int $batchSize = 100): int
    {
        $inserted = 0;
        $batches = array_chunk($data, $batchSize);
        
        foreach ($batches as $batch) {
            foreach ($batch as $row) {
                \Core\Database\DB::table($table)->insert($row);
                $inserted++;
            }
        }
        
        return $inserted;
    }
}
