<?php

namespace App\Core\Eav\Cache;

/**
 * Query Signature Generator
 * 
 * Generates consistent cache keys from query parameters.
 * - Deterministic signature generation
 * - Supports complex query structures
 * - Compact MD5 signatures
 * - Parameter normalization
 * 
 * @package App\Core\Eav\Cache
 */
class QuerySignature
{
    /**
     * Generate signature from query parameters
     * 
     * @param array $params Query parameters
     * @return string MD5 signature (32 chars)
     */
    public static function generate(array $params): string
    {
        $normalized = self::normalize($params);
        $serialized = serialize($normalized);
        return md5($serialized);
    }

    /**
     * Generate signature from SQL query and bindings
     * 
     * @param string $sql SQL query string
     * @param array $bindings Query bindings
     * @return string MD5 signature
     */
    public static function fromSql(string $sql, array $bindings = []): string
    {
        return self::generate([
            'sql' => $sql,
            'bindings' => $bindings,
        ]);
    }

    /**
     * Generate signature for entity load operation
     * 
     * @param string $entityType Entity type code
     * @param int $entityId Entity ID
     * @param array $attributes Attributes to load (empty = all)
     * @return string MD5 signature
     */
    public static function forEntityLoad(string $entityType, int $entityId, array $attributes = []): string
    {
        return self::generate([
            'operation' => 'entity_load',
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'attributes' => $attributes,
        ]);
    }

    /**
     * Generate signature for entity collection query
     * 
     * @param string $entityType Entity type code
     * @param array $filters Filter conditions
     * @param array $sort Sort criteria
     * @param int|null $limit Result limit
     * @param int|null $offset Result offset
     * @return string MD5 signature
     */
    public static function forEntityCollection(
        string $entityType,
        array $filters = [],
        array $sort = [],
        ?int $limit = null,
        ?int $offset = null
    ): string {
        return self::generate([
            'operation' => 'entity_collection',
            'entity_type' => $entityType,
            'filters' => $filters,
            'sort' => $sort,
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    /**
     * Generate signature for attribute value query
     * 
     * @param string $entityType Entity type code
     * @param string $attributeCode Attribute code
     * @param int $entityId Entity ID
     * @return string MD5 signature
     */
    public static function forAttributeValue(string $entityType, string $attributeCode, int $entityId): string
    {
        return self::generate([
            'operation' => 'attribute_value',
            'entity_type' => $entityType,
            'attribute_code' => $attributeCode,
            'entity_id' => $entityId,
        ]);
    }

    /**
     * Generate signature for search query
     * 
     * @param string $entityType Entity type code
     * @param string $searchTerm Search term
     * @param array $searchableAttributes Attributes to search in
     * @param array $filters Additional filters
     * @param int|null $limit Result limit
     * @return string MD5 signature
     */
    public static function forSearch(
        string $entityType,
        string $searchTerm,
        array $searchableAttributes = [],
        array $filters = [],
        ?int $limit = null
    ): string {
        return self::generate([
            'operation' => 'search',
            'entity_type' => $entityType,
            'search_term' => $searchTerm,
            'searchable_attributes' => $searchableAttributes,
            'filters' => $filters,
            'limit' => $limit,
        ]);
    }

    /**
     * Generate signature for aggregate query
     * 
     * @param string $entityType Entity type code
     * @param string $function Aggregate function (COUNT, SUM, AVG, MIN, MAX)
     * @param string|null $attributeCode Attribute to aggregate (null for COUNT(*))
     * @param array $filters Filter conditions
     * @param string|null $groupBy Group by attribute
     * @return string MD5 signature
     */
    public static function forAggregate(
        string $entityType,
        string $function,
        ?string $attributeCode = null,
        array $filters = [],
        ?string $groupBy = null
    ): string {
        return self::generate([
            'operation' => 'aggregate',
            'entity_type' => $entityType,
            'function' => strtoupper($function),
            'attribute_code' => $attributeCode,
            'filters' => $filters,
            'group_by' => $groupBy,
        ]);
    }

    /**
     * Generate signature for related entities query
     * 
     * @param string $entityType Entity type code
     * @param int $entityId Entity ID
     * @param string $relationType Relation type
     * @param array $filters Additional filters
     * @return string MD5 signature
     */
    public static function forRelatedEntities(
        string $entityType,
        int $entityId,
        string $relationType,
        array $filters = []
    ): string {
        return self::generate([
            'operation' => 'related_entities',
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'relation_type' => $relationType,
            'filters' => $filters,
        ]);
    }

    /**
     * Generate signature with custom prefix
     * 
     * @param string $prefix Custom prefix for signature
     * @param array $params Query parameters
     * @return string Prefixed signature
     */
    public static function withPrefix(string $prefix, array $params): string
    {
        $signature = self::generate($params);
        return $prefix . '_' . $signature;
    }

    /**
     * Normalize parameters for consistent signatures
     * 
     * @param array $params Parameters to normalize
     * @return array Normalized parameters
     */
    private static function normalize(array $params): array
    {
        // Sort array keys recursively for consistency
        ksort($params);
        
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $params[$key] = self::normalize($value);
            } elseif (is_bool($value)) {
                // Normalize booleans to integers
                $params[$key] = (int)$value;
            } elseif (is_null($value)) {
                // Normalize nulls to empty string
                $params[$key] = '';
            } elseif (is_float($value)) {
                // Normalize floats to avoid precision issues
                $params[$key] = round($value, 8);
            }
        }
        
        return $params;
    }

    /**
     * Generate signature from object
     * 
     * @param object $object Object to generate signature from
     * @return string MD5 signature
     */
    public static function fromObject(object $object): string
    {
        if (method_exists($object, 'toArray')) {
            return self::generate($object->toArray());
        }
        
        // Use object properties
        $properties = get_object_vars($object);
        return self::generate($properties);
    }

    /**
     * Combine multiple signatures into one
     * 
     * @param array $signatures Array of signatures to combine
     * @return string Combined signature
     */
    public static function combine(array $signatures): string
    {
        sort($signatures); // Ensure consistent order
        return md5(implode('|', $signatures));
    }

    /**
     * Generate versioned signature (includes version in signature)
     * 
     * @param array $params Query parameters
     * @param string $version Cache version (e.g., schema version)
     * @return string Versioned signature
     */
    public static function versioned(array $params, string $version): string
    {
        $params['__cache_version__'] = $version;
        return self::generate($params);
    }

    /**
     * Generate signature with TTL hint embedded
     * Useful for automatic TTL extraction
     * 
     * @param array $params Query parameters
     * @param int $ttl Suggested TTL
     * @return array ['signature' => string, 'ttl' => int]
     */
    public static function withTtl(array $params, int $ttl): array
    {
        return [
            'signature' => self::generate($params),
            'ttl' => $ttl,
        ];
    }

    /**
     * Generate human-readable signature (for debugging)
     * Not for production use - returns readable string instead of hash
     * 
     * @param array $params Query parameters
     * @return string Human-readable signature
     */
    public static function debug(array $params): string
    {
        $normalized = self::normalize($params);
        return json_encode($normalized, JSON_PRETTY_PRINT);
    }

    /**
     * Validate signature format
     * 
     * @param string $signature Signature to validate
     * @return bool True if valid MD5 signature
     */
    public static function isValid(string $signature): bool
    {
        return (bool)preg_match('/^[a-f0-9]{32}$/i', $signature);
    }

    /**
     * Extract entity type from tagged signature
     * Used when signatures include entity type prefix
     * 
     * @param string $signature Tagged signature (e.g., "product_abc123...")
     * @return array ['entity_type' => string|null, 'signature' => string]
     */
    public static function parse(string $signature): array
    {
        $parts = explode('_', $signature, 2);
        
        if (count($parts) === 2 && self::isValid($parts[1])) {
            return [
                'entity_type' => $parts[0],
                'signature' => $parts[1],
            ];
        }
        
        return [
            'entity_type' => null,
            'signature' => $signature,
        ];
    }
}
