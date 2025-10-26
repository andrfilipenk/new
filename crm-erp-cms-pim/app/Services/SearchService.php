<?php

namespace App\Services;

use Core\Database\DB;

class SearchService
{
    /**
     * Search across multiple tables
     */
    public static function search(string $query, array $tables = [], int $limit = 50): array
    {
        $results = [];
        $searchTerm = '%' . $query . '%';
        
        // Default tables if none specified
        if (empty($tables)) {
            $tables = ['users', 'crm_contacts', 'crm_accounts', 'pim_products', 'cms_posts'];
        }
        
        foreach ($tables as $table) {
            $results[$table] = self::searchTable($table, $searchTerm, $limit);
        }
        
        return $results;
    }
    
    /**
     * Search in a specific table
     */
    private static function searchTable(string $table, string $searchTerm, int $limit): array
    {
        $searchFields = self::getSearchFields($table);
        
        if (empty($searchFields)) {
            return [];
        }
        
        $query = DB::table($table);
        
        // Build WHERE clause with OR conditions
        $firstField = array_shift($searchFields);
        $query->where($firstField, 'LIKE', $searchTerm);
        
        foreach ($searchFields as $field) {
            $query->orWhere($field, 'LIKE', $searchTerm);
        }
        
        return $query->limit($limit)->get();
    }
    
    /**
     * Get searchable fields for a table
     */
    private static function getSearchFields(string $table): array
    {
        $fieldMap = [
            'users' => ['name', 'email'],
            'crm_contacts' => ['first_name', 'last_name', 'email', 'company'],
            'crm_accounts' => ['name', 'website', 'industry'],
            'crm_leads' => ['first_name', 'last_name', 'email', 'company'],
            'crm_opportunities' => ['name', 'description'],
            'pim_products' => ['name', 'sku', 'description'],
            'erp_products' => ['name', 'sku', 'description'],
            'erp_customers' => ['name', 'email', 'code'],
            'erp_vendors' => ['name', 'email', 'code'],
            'cms_posts' => ['title', 'content', 'excerpt'],
            'cms_pages' => ['title', 'content', 'excerpt'],
        ];
        
        return $fieldMap[$table] ?? [];
    }
    
    /**
     * Search contacts
     */
    public static function searchContacts(string $query, int $limit = 20): array
    {
        return DB::table('crm_contacts')
            ->where('first_name', 'LIKE', "%{$query}%")
            ->orWhere('last_name', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->orWhere('company', 'LIKE', "%{$query}%")
            ->limit($limit)
            ->get();
    }
    
    /**
     * Search products
     */
    public static function searchProducts(string $query, int $limit = 20): array
    {
        return DB::table('pim_products')
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('sku', 'LIKE', "%{$query}%")
            ->orWhere('description', 'LIKE', "%{$query}%")
            ->limit($limit)
            ->get();
    }
    
    /**
     * Search posts
     */
    public static function searchPosts(string $query, int $limit = 20): array
    {
        return DB::table('cms_posts')
            ->where('title', 'LIKE', "%{$query}%")
            ->orWhere('content', 'LIKE', "%{$query}%")
            ->where('status', 'published')
            ->limit($limit)
            ->get();
    }
}
