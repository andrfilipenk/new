<?php

namespace Modules\ERP\Controllers;

use Core\Request;
use Core\Response;
use Modules\ERP\Models\Finance\Invoice;
use Modules\ERP\Models\Finance\Payment;
use Core\Validation\Validator;
use App\Services\AuditService;

/**
 * Invoice Controller
 * 
 * Handles invoice generation, payment tracking, and accounting
 */
class InvoiceController
{
    /**
     * Display list of invoices
     */
    public function index(Request $request): Response
    {
        $status = $request->get('status');
        
        $query = \Core\Database\DB::table('erp_invoices');
        
        if ($status) {
            $query->where('status', '=', $status);
        }
        
        $invoices = $query->orderBy('invoice_date', 'DESC')->get();
        $invoices = array_map(fn($row) => Invoice::hydrate($row), $invoices);
        
        if ($request->expectsJson()) {
            return Response::json(['invoices' => $invoices]);
        }
        
        return view('erp/invoices/index', [
            'invoices' => $invoices,
            'status' => $status,
        ]);
    }
    
    /**
     * Show create form
     */
    public function create(): Response
    {
        $customers = \Core\Database\DB::table('erp_customers')->get();
        
        return view('erp/invoices/create', [
            'customers' => $customers,
        ]);
    }
    
    /**
     * Store new invoice
     */
    public function store(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|integer',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date',
            'payment_terms' => 'max:200',
            'notes' => 'max:1000',
            'discount_amount' => 'numeric',
            'items' => 'required',
        ]);
        
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return Response::json(['errors' => $validator->errors()], 422);
            }
            return redirect('/erp/invoices/create')->with('errors', $validator->errors());
        }
        
        try {
            $data = $validator->validated();
            
            // Generate invoice number
            $data['invoice_number'] = $this->generateInvoiceNumber();
            $data['status'] = 'draft';
            $data['created_by'] = auth()->id();
            $data['subtotal'] = 0;
            $data['tax_amount'] = 0;
            $data['total_amount'] = 0;
            $data['paid_amount'] = 0;
            $data['balance'] = 0;
            
            $invoice = Invoice::create($data);
            
            // Add items
            $items = json_decode($data['items'], true);
            foreach ($items as $item) {
                \Core\Database\DB::table('erp_invoice_items')->insert([
                    'invoice_id' => $invoice['id'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'tax_amount' => ($item['quantity'] * $item['unit_price'] * ($item['tax_rate'] ?? 0) / 100),
                    'line_total' => $item['quantity'] * $item['unit_price'],
                ]);
            }
            
            // Calculate totals
            $this->calculateTotals($invoice['id']);
            
            AuditService::logCreate('Invoice', $invoice['id'], $data, auth()->id());
            
            if ($request->expectsJson()) {
                return Response::json(['invoice' => $invoice, 'message' => 'Invoice created successfully'], 201);
            }
            
            return redirect('/erp/invoices/' . $invoice['id'])->with('success', 'Invoice created successfully');
        } catch (\Exception $e) {
            logger()->error('Invoice creation failed', ['error' => $e->getMessage()]);
            
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Failed to create invoice'], 500);
            }
            
            return redirect('/erp/invoices/create')->with('error', 'Failed to create invoice');
        }
    }
    
    /**
     * Show single invoice
     */
    public function show(Request $request, int $id): Response
    {
        $invoice = Invoice::findOrFail($id);
        
        $items = \Core\Database\DB::table('erp_invoice_items')
            ->where('invoice_id', '=', $id)
            ->get();
        
        $payments = Payment::where('invoice_id', $id)->get();
        
        if ($request->expectsJson()) {
            return Response::json([
                'invoice' => $invoice,
                'items' => $items,
                'payments' => $payments,
            ]);
        }
        
        return view('erp/invoices/show', [
            'invoice' => $invoice,
            'items' => $items,
            'payments' => $payments,
        ]);
    }
    
    /**
     * Record payment
     */
    public function recordPayment(Request $request, int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,check,credit_card,bank_transfer,other',
            'reference' => 'max:100',
            'notes' => 'max:500',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $invoice = Invoice::findOrFail($id);
            $data = $validator->validated();
            
            if ($data['amount'] > $invoice['balance']) {
                throw new \Exception('Payment amount cannot exceed invoice balance');
            }
            
            // Create payment record
            $payment = Payment::create([
                'invoice_id' => $id,
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);
            
            // Update invoice
            $newPaidAmount = $invoice['paid_amount'] + $data['amount'];
            $newBalance = $invoice['total_amount'] - $newPaidAmount;
            
            $invoice->update([
                'paid_amount' => $newPaidAmount,
                'balance' => $newBalance,
                'status' => $newBalance == 0 ? 'paid' : ($newPaidAmount > 0 ? 'partially_paid' : 'sent'),
            ]);
            
            AuditService::logCreate('Payment', $payment['id'], $data, auth()->id());
            
            return Response::json(['payment' => $payment, 'message' => 'Payment recorded successfully']);
        } catch (\Exception $e) {
            logger()->error('Payment recording failed', ['error' => $e->getMessage(), 'invoice_id' => $id]);
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Send invoice
     */
    public function send(Request $request, int $id): Response
    {
        try {
            $invoice = Invoice::findOrFail($id);
            
            if ($invoice['status'] !== 'draft') {
                throw new \Exception('Only draft invoices can be sent');
            }
            
            $invoice->update([
                'status' => 'sent',
                'sent_date' => date('Y-m-d'),
            ]);
            
            // TODO: Send email to customer
            
            return Response::json(['message' => 'Invoice sent successfully']);
        } catch (\Exception $e) {
            logger()->error('Invoice send failed', ['error' => $e->getMessage(), 'invoice_id' => $id]);
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Void invoice
     */
    public function void(Request $request, int $id): Response
    {
        try {
            $invoice = Invoice::findOrFail($id);
            
            if ($invoice['paid_amount'] > 0) {
                throw new \Exception('Cannot void invoice with payments');
            }
            
            $invoice->update(['status' => 'void']);
            
            return Response::json(['message' => 'Invoice voided successfully']);
        } catch (\Exception $e) {
            logger()->error('Invoice void failed', ['error' => $e->getMessage(), 'invoice_id' => $id]);
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Generate invoice number
     */
    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $date = date('Ymd');
        $lastInvoice = \Core\Database\DB::table('erp_invoices')
            ->where('invoice_number', 'LIKE', "{$prefix}{$date}%")
            ->orderBy('id', 'DESC')
            ->first();
        
        if ($lastInvoice) {
            $lastNumber = (int)substr($lastInvoice['invoice_number'], -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $date . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Calculate invoice totals
     */
    private function calculateTotals(int $invoiceId): void
    {
        $items = \Core\Database\DB::table('erp_invoice_items')
            ->where('invoice_id', '=', $invoiceId)
            ->get();
        
        $subtotal = 0;
        $taxAmount = 0;
        
        foreach ($items as $item) {
            $subtotal += $item['line_total'];
            $taxAmount += $item['tax_amount'];
        }
        
        $invoice = Invoice::findOrFail($invoiceId);
        $discountAmount = $invoice['discount_amount'] ?? 0;
        $totalAmount = $subtotal + $taxAmount - $discountAmount;
        
        $invoice->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'balance' => $totalAmount - ($invoice['paid_amount'] ?? 0),
        ]);
    }
}
