<?php

namespace App\Services;

use Core\Database\DB;

class ReportService
{
    /**
     * Generate sales report
     */
    public static function salesReport(string $startDate, string $endDate): array
    {
        return DB::table('erp_sales_orders')
            ->select(
                'DATE(order_date) as date',
                'COUNT(*) as order_count',
                'SUM(total_amount) as total_sales',
                'AVG(total_amount) as average_order'
            )
            ->where('order_date', '>=', $startDate)
            ->where('order_date', '<=', $endDate)
            ->groupBy('DATE(order_date)')
            ->orderBy('date', 'ASC')
            ->get();
    }
    
    /**
     * Generate inventory report
     */
    public static function inventoryReport(): array
    {
        return DB::table('erp_inventory')
            ->select(
                'erp_inventory.*',
                'erp_products.name as product_name',
                'erp_products.sku',
                'erp_warehouses.name as warehouse_name'
            )
            ->join('erp_products', 'erp_inventory.product_id', '=', 'erp_products.id')
            ->join('erp_warehouses', 'erp_inventory.warehouse_id', '=', 'erp_warehouses.id')
            ->where('erp_inventory.available_quantity', '>', 0)
            ->orderBy('erp_products.name', 'ASC')
            ->get();
    }
    
    /**
     * Generate low stock report
     */
    public static function lowStockReport(): array
    {
        return DB::select("
            SELECT 
                p.name,
                p.sku,
                p.reorder_level,
                SUM(i.available_quantity) as total_stock
            FROM erp_products p
            LEFT JOIN erp_inventory i ON p.id = i.product_id
            WHERE p.track_inventory = 1
            GROUP BY p.id
            HAVING total_stock <= p.reorder_level
            ORDER BY total_stock ASC
        ");
    }
    
    /**
     * Generate customer report
     */
    public static function customerReport(): array
    {
        return DB::select("
            SELECT 
                c.name,
                c.email,
                COUNT(so.id) as order_count,
                SUM(so.total_amount) as total_spent,
                MAX(so.order_date) as last_order_date
            FROM erp_customers c
            LEFT JOIN erp_sales_orders so ON c.id = so.customer_id
            GROUP BY c.id
            ORDER BY total_spent DESC
        ");
    }
    
    /**
     * Generate user activity report
     */
    public static function userActivityReport(int $days = 30): array
    {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        
        return DB::table('audit_logs')
            ->select(
                'users.name',
                'users.email',
                'COUNT(*) as action_count',
                'MAX(audit_logs.created_at) as last_activity'
            )
            ->join('users', 'audit_logs.user_id', '=', 'users.id')
            ->where('audit_logs.created_at', '>=', $startDate)
            ->groupBy('users.id')
            ->orderBy('action_count', 'DESC')
            ->get();
    }
    
    /**
     * Generate dashboard statistics
     */
    public static function dashboardStats(): array
    {
        return [
            'total_customers' => DB::table('erp_customers')->count(),
            'total_products' => DB::table('pim_products')->count(),
            'pending_orders' => DB::table('erp_sales_orders')->where('status', 'pending')->count(),
            'revenue_today' => DB::table('erp_sales_orders')
                ->where('order_date', date('Y-m-d'))
                ->sum('total_amount'),
            'revenue_month' => DB::table('erp_sales_orders')
                ->where('order_date', '>=', date('Y-m-01'))
                ->sum('total_amount'),
            'low_stock_items' => DB::select("
                SELECT COUNT(*) as count
                FROM erp_products p
                LEFT JOIN erp_inventory i ON p.id = i.product_id
                WHERE p.track_inventory = 1
                GROUP BY p.id
                HAVING SUM(i.available_quantity) <= p.reorder_level
            ")[0]['count'] ?? 0
        ];
    }
}
