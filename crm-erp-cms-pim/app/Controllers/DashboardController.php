<?php

namespace App\Controllers;

use Core\Request;
use Core\Response;
use Core\Auth\Auth;
use Core\Database\DB;

/**
 * Dashboard Controller
 * 
 * Main landing page for authenticated users
 */
class DashboardController extends Controller
{
    /**
     * Display the dashboard
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        
        // Get dashboard statistics
        $stats = $this->getDashboardStats();
        
        return $this->view('dashboard', [
            'user' => $user,
            'stats' => $stats,
        ]);
    }
    
    /**
     * Get dashboard statistics
     */
    private function getDashboardStats(): array
    {
        $stats = [
            'total_leads' => 0,
            'total_opportunities' => 0,
            'total_revenue' => 0,
            'pending_tasks' => 0,
            'total_customers' => 0,
            'low_stock_items' => 0,
            'published_pages' => 0,
            'total_products' => 0,
        ];
        
        try {
            // Query real statistics from database
            $stats['total_leads'] = (int) DB::table('crm_leads')->count();
            $stats['total_opportunities'] = (int) DB::table('crm_opportunities')->count();
            
            // Calculate total revenue from paid invoices
            $totalRevenue = DB::table('erp_invoices')
                ->where('status', 'paid')
                ->sum('total_amount');
            $stats['total_revenue'] = (float) ($totalRevenue ?? 0);
            
            // Count pending/incomplete activities
            $pendingTasks = DB::table('crm_activities')
                ->where('status', 'pending')
                ->count() +
            DB::table('crm_activities')
                ->where('status', 'in_progress')
                ->count();
            $stats['pending_tasks'] = (int) $pendingTasks;
            
            // Additional useful statistics
            $stats['total_customers'] = (int) DB::table('erp_customers')->count();
            
            // Low stock items (below 10 units as threshold)
            $stats['low_stock_items'] = (int) DB::table('erp_inventory')
                ->where('quantity', '<=', 10)
                ->count();
            
            // Published content pages
            $stats['published_pages'] = (int) DB::table('cms_pages')
                ->where('status', 'published')
                ->count();
            
            // Active products
            $stats['total_products'] = (int) DB::table('pim_products')
                ->where('status', 'active')
                ->count();
                
        } catch (\Exception $e) {
            // Log error with full context
            logger()->error('Failed to fetch dashboard statistics', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Flash warning message to user if in debug mode
            if (config('app.debug', false)) {
                \Core\Session\SessionManager::flash('warning', 
                    'Unable to load some dashboard statistics. Error: ' . $e->getMessage());
            } else {
                \Core\Session\SessionManager::flash('warning', 
                    'Some dashboard statistics are currently unavailable.');
            }
        }
        
        return $stats;
    }
    
    /**
     * Get recent activity feed
     */
    private function getRecentActivity(): array
    {
        try {
            // Get recent activities from CRM
            $activities = DB::table('crm_activities')
                ->select('crm_activities.*', 'users.name as user_name')
                ->leftJoin('users', 'crm_activities.assigned_to', '=', 'users.id')
                ->orderBy('crm_activities.created_at', 'DESC')
                ->limit(10)
                ->get();
            
            return $activities;
        } catch (\Exception $e) {
            logger()->error('Failed to fetch recent activity', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
