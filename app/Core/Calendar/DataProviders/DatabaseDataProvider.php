<?php

namespace Core\Calendar\DataProviders;

use Core\Calendar\Models\DateRange;
use Core\Calendar\Models\CalendarData;
use Core\Calendar\Models\Bar;
use Core\Database\Database;

/**
 * Database-backed data provider with caching support
 */
class DatabaseDataProvider implements DataProviderInterface
{
    private Database $db;
    private string $tableName;
    private array $fieldMapping;
    private array $cache = [];
    private bool $cacheEnabled;
    private array $metadata = [];

    /**
     * @param Database $db Database instance
     * @param string $tableName Table containing bar/event data
     * @param array $fieldMapping Map of Bar fields to database columns
     * @param bool $cacheEnabled Whether to cache queries
     */
    public function __construct(
        Database $db,
        string $tableName,
        array $fieldMapping = [],
        bool $cacheEnabled = true
    ) {
        $this->db = $db;
        $this->tableName = $tableName;
        $this->fieldMapping = array_merge($this->getDefaultFieldMapping(), $fieldMapping);
        $this->cacheEnabled = $cacheEnabled;
    }

    private function getDefaultFieldMapping(): array
    {
        return [
            'id' => 'id',
            'title' => 'title',
            'start_date' => 'start_date',
            'end_date' => 'end_date',
            'color' => 'color',
            'background_color' => 'background_color',
            'url' => 'url',
            'click_handler' => 'click_handler',
            'metadata' => 'metadata',
            'z_index' => 'z_index',
        ];
    }

    public function getDataForRange(DateRange $range): CalendarData
    {
        $bars = $this->getBarsForRange($range);
        return new CalendarData($bars, $this->metadata);
    }

    public function getBarsForRange(DateRange $range): array
    {
        $cacheKey = $range->getStartDate()->format('Y-m-d') . '_' . $range->getEndDate()->format('Y-m-d');
        
        if ($this->cacheEnabled && isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        
        $bars = $this->fetchBarsFromDatabase($range);
        
        if ($this->cacheEnabled) {
            $this->cache[$cacheKey] = $bars;
        }
        
        return $bars;
    }

    private function fetchBarsFromDatabase(DateRange $range): array
    {
        $startField = $this->fieldMapping['start_date'];
        $endField = $this->fieldMapping['end_date'];
        
        $sql = "SELECT * FROM {$this->tableName} 
                WHERE {$startField} <= ? AND {$endField} >= ?
                ORDER BY {$startField} ASC";
        
        $rows = $this->db->fetchAll($sql, [
            $range->getEndDate()->format('Y-m-d H:i:s'),
            $range->getStartDate()->format('Y-m-d H:i:s'),
        ]);
        
        $bars = [];
        foreach ($rows as $row) {
            $bars[] = $this->rowToBar($row);
        }
        
        return $bars;
    }

    private function rowToBar(array $row): Bar
    {
        $metadata = [];
        if (isset($row[$this->fieldMapping['metadata']])) {
            $metadataValue = $row[$this->fieldMapping['metadata']];
            $metadata = is_string($metadataValue) ? json_decode($metadataValue, true) : $metadataValue;
        }
        
        return new Bar(
            id: (string)$row[$this->fieldMapping['id']],
            title: $row[$this->fieldMapping['title']],
            startDate: new \DateTimeImmutable($row[$this->fieldMapping['start_date']]),
            endDate: new \DateTimeImmutable($row[$this->fieldMapping['end_date']]),
            color: $row[$this->fieldMapping['color']] ?? null,
            backgroundColor: $row[$this->fieldMapping['background_color']] ?? null,
            url: $row[$this->fieldMapping['url']] ?? null,
            clickHandler: $row[$this->fieldMapping['click_handler']] ?? null,
            metadata: $metadata ?? [],
            zIndex: (int)($row[$this->fieldMapping['z_index']] ?? 0)
        );
    }

    public function hasDataForDate(\DateTimeInterface $date): bool
    {
        $range = new DateRange($date, $date);
        $bars = $this->getBarsForRange($range);
        
        return !empty($bars);
    }

    public function clearCache(): void
    {
        $this->cache = [];
    }

    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
