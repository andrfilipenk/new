<?php

namespace Modules\ERP\Controllers;

use Core\Request;
use Core\Response;
use Core\Database\DB;

/**
 * Finance Controller
 * 
 * Handles financial overview including invoices, payments, and revenue
 */
class FinanceController
{
    /**
     * Display financial dashboard
     */
    public function index(Request $request): Response
    {
        $stats = $this->getFinancialStats();
        
        if ($request->expectsJson()) {
            return Response::json(['stats' => $stats]);
        }
        
        return view('erp/finance/index', [
            'stats' => $stats,
        ]);
    }
    
    /**
     * Get financial dashboard
     */
    public function dashboard(Request $request): Response
    {
        return $this->index($request);
    }
    
    /**
     * Get financial statistics
     */
    private function getFinancialStats(): array
    {
        try {
            // Total revenue from paid invoices
            $totalRevenue = DB::table('erp_invoices')
                ->where('status', 'paid')
                ->sum('total_amount');
            
            // Pending revenue (unpaid invoices)
            $pendingRevenue = DB::table('erp_invoices')
                ->where('status', 'pending')
                ->orWhere('status', 'sent')
                ->sum('total_amount');
            
            // Total expenses (could be from purchase orders or dedicated expense table)
            $totalExpenses = DB::table('erp_purchase_orders')
                ->where('status', 'completed')
                ->sum('total_amount');
            
            // Count of invoices by status
            $invoiceCount = [
                'total' => DB::table('erp_invoices')->count(),
                'paid' => DB::table('erp_invoices')->where('status', 'paid')->count(),
                'pending' => DB::table('erp_invoices')->where('status', 'pending')->count(),
                'overdue' => DB::table('erp_invoices')
                    ->where('status', 'pending')
                    ->where('due_date', '<', date('Y-m-d'))
                    ->count(),
            ];
            
            // Count of payments
            $paymentCount = DB::table('erp_payments')->count();
            
            // Recent payments
            $recentPayments = DB::table('erp_payments')
                ->select('erp_payments.*', 'erp_invoices.invoice_number')
                ->leftJoin('erp_invoices', 'erp_payments.invoice_id', '=', 'erp_invoices.id')
                ->orderBy('erp_payments.payment_date', 'DESC')
                ->limit(10)
                ->get();
            
            // Outstanding invoices
            $outstandingInvoices = DB::table('erp_invoices')
                ->where('status', '!=', 'paid')
                ->where('status', '!=', 'void')
                ->orderBy('due_date', 'ASC')
                ->limit(10)
                ->get();
            
            return [
                'total_revenue' => (float) ($totalRevenue ?? 0),
                'pending_revenue' => (float) ($pendingRevenue ?? 0),
                'total_expenses' => (float) ($totalExpenses ?? 0),
                'net_profit' => (float) (($totalRevenue ?? 0) - ($totalExpenses ?? 0)),
                'invoice_count' => $invoiceCount,
                'payment_count' => (int) $paymentCount,
                'recent_payments' => $recentPayments,
                'outstanding_invoices' => $outstandingInvoices,
            ];
        } catch (\Exception $e) {
            logger()->error('Failed to fetch financial statistics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'total_revenue' => 0,
                'pending_revenue' => 0,
                'total_expenses' => 0,
                'net_profit' => 0,
                'invoice_count' => [
                    'total' => 0,
                    'paid' => 0,
                    'pending' => 0,
                    'overdue' => 0,
                ],
                'payment_count' => 0,
                'recent_payments' => [],
                'outstanding_invoices' => [],
            ];
        }
    }
    
    /**
     * Get revenue data by period
     */
    public function getRevenueData(Request $request): Response
    {
        $period = $request->input('period', 'month');
        
        try {
            // This would need more complex date grouping logic
            // For now, return basic aggregated data
            $revenueByMonth = DB::table('erp_invoices')
                ->select(DB::raw('DATE_FORMAT(invoice_date, "%Y-%m") as month, SUM(total_amount) as revenue'))
                ->where('status', 'paid')
                ->groupBy('month')
                ->orderBy('month', 'DESC')
                ->limit(12)
                ->get();
            
            return Response::json(['revenue' => $revenueByMonth]);
        } catch (\Exception $e) {
            logger()->error('Failed to fetch revenue data', ['error' => $e->getMessage()]);
            return Response::json(['error' => 'Failed to fetch revenue data'], 500);
        }
    }
    
    /**
     * Get payment status statistics
     */
    public function getPaymentStatus(Request $request): Response
    {
        try {
            $stats = [
                'total_payments' => DB::table('erp_payments')->count(),
                'total_amount' => DB::table('erp_payments')->sum('amount'),
                'payment_methods' => DB::table('erp_payments')
                    ->select(DB::raw('payment_method, COUNT(*) as count, SUM(amount) as total'))
                    ->groupBy('payment_method')
                    ->get(),
            ];
            
            return Response::json(['stats' => $stats]);
        } catch (\Exception $e) {
            logger()->error('Failed to fetch payment status', ['error' => $e->getMessage()]);
            return Response::json(['error' => 'Failed to fetch payment status'], 500);
        }
    }
}
