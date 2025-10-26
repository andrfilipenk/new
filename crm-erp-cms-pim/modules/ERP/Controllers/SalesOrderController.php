<?php

namespace Modules\ERP\Controllers;

use Core\Request;
use Core\Response;
use Modules\ERP\Services\SalesService;
use Core\Validation\Validator;

/**
 * Sales Order Controller
 */
class SalesOrderController
{
    private SalesService $salesService;
    
    public function __construct()
    {
        $this->salesService = new SalesService();
    }
    
    /**
     * Display list of orders
     */
    public function index(Request $request): Response
    {
        $orders = \Modules\ERP\Models\Sales\SalesOrder::all();
        
        if ($request->expectsJson()) {
            return Response::json(['orders' => $orders]);
        }
        
        return view('erp/sales/orders/index', ['orders' => $orders]);
    }
    
    /**
     * Create new order
     */
    public function store(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|integer',
            'order_date' => 'required|date',
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $data = $validator->validated();
            $items = $data['items'];
            unset($data['items']);
            
            $order = $this->salesService->createOrder($data, $items, auth()->id());
            
            return Response::json([
                'order' => $order,
                'message' => 'Order created successfully'
            ], 201);
        } catch (\Exception $e) {
            logger()->error('Order creation failed', ['error' => $e->getMessage()]);
            return Response::json(['error' => 'Failed to create order'], 500);
        }
    }
    
    /**
     * Confirm order
     */
    public function confirm(Request $request, int $id): Response
    {
        try {
            $order = $this->salesService->confirmOrder($id, auth()->id());
            return Response::json(['order' => $order, 'message' => 'Order confirmed']);
        } catch (\Exception $e) {
            logger()->error('Order confirmation failed', ['error' => $e->getMessage()]);
            return Response::json(['error' => $e->getMessage()], 400);
        }
    }
    
    /**
     * Fulfill order
     */
    public function fulfill(Request $request, int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|integer',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $order = $this->salesService->fulfillOrder($id, $validator->validated()['warehouse_id'], auth()->id());
            return Response::json(['order' => $order, 'message' => 'Order fulfilled']);
        } catch (\Exception $e) {
            logger()->error('Order fulfillment failed', ['error' => $e->getMessage()]);
            return Response::json(['error' => $e->getMessage()], 400);
        }
    }
    
    /**
     * Cancel order
     */
    public function cancel(Request $request, int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $order = $this->salesService->cancelOrder($id, $validator->validated()['reason'], auth()->id());
            return Response::json(['order' => $order, 'message' => 'Order cancelled']);
        } catch (\Exception $e) {
            logger()->error('Order cancellation failed', ['error' => $e->getMessage()]);
            return Response::json(['error' => 'Failed to cancel order'], 500);
        }
    }
    
    /**
     * Create invoice from order
     */
    public function createInvoice(Request $request, int $id): Response
    {
        try {
            $invoice = $this->salesService->createInvoice($id, auth()->id());
            return Response::json(['invoice' => $invoice, 'message' => 'Invoice created']);
        } catch (\Exception $e) {
            logger()->error('Invoice creation failed', ['error' => $e->getMessage()]);
            return Response::json(['error' => 'Failed to create invoice'], 500);
        }
    }
    
    /**
     * Get sales statistics
     */
    public function statistics(Request $request): Response
    {
        $period = $request->input('period', 'month');
        $stats = $this->salesService->getSalesStatistics($period);
        
        return Response::json(['statistics' => $stats]);
    }
}
