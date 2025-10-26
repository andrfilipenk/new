<?php

namespace Modules\CRM\Controllers;

use Core\Request;
use Core\Response;
use Modules\CRM\Services\AccountService;
use Core\Validation\Validator;

/**
 * Account Controller
 * 
 * Handles HTTP requests for account (company/organization) management
 */
class AccountController
{
    private AccountService $accountService;
    
    public function __construct()
    {
        $this->accountService = new AccountService();
    }
    
    /**
     * Display list of accounts
     */
    public function index(Request $request): Response
    {
        $accounts = \Modules\CRM\Models\Account::all();
        
        if ($request->expectsJson()) {
            return Response::json(['accounts' => $accounts]);
        }
        
        return view('crm/accounts/index', ['accounts' => $accounts]);
    }
    
    /**
     * Show create form
     */
    public function create(): Response
    {
        return view('crm/accounts/create');
    }
    
    /**
     * Store new account
     */
    public function store(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:200',
            'type' => 'required|in:prospect,customer,partner,vendor,other',
            'industry' => 'max:100',
            'website' => 'max:255',
            'phone' => 'max:20',
            'email' => 'email|max:255',
            'billing_address' => 'max:500',
            'shipping_address' => 'max:500',
            'annual_revenue' => 'numeric',
            'employees' => 'integer',
            'owner_id' => 'integer',
            'description' => 'max:1000',
        ]);
        
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return Response::json(['errors' => $validator->errors()], 422);
            }
            return redirect('/crm/accounts/create')->with('errors', $validator->errors());
        }
        
        try {
            $account = $this->accountService->create($validator->validated(), auth()->id());
            
            if ($request->expectsJson()) {
                return Response::json(['account' => $account, 'message' => 'Account created successfully'], 201);
            }
            
            return redirect('/crm/accounts/' . $account->id)->with('success', 'Account created successfully');
        } catch (\Exception $e) {
            logger()->error('Account creation failed', ['error' => $e->getMessage()]);
            
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Failed to create account'], 500);
            }
            
            return redirect('/crm/accounts/create')->with('error', 'Failed to create account');
        }
    }
    
    /**
     * Show single account with related data
     */
    public function show(Request $request, int $id): Response
    {
        $account = \Modules\CRM\Models\Account::findOrFail($id);
        
        // Load relationships
        $contacts = $account->contacts();
        $opportunities = $account->opportunities();
        
        if ($request->expectsJson()) {
            return Response::json([
                'account' => $account,
                'contacts' => $contacts,
                'opportunities' => $opportunities,
            ]);
        }
        
        return view('crm/accounts/show', [
            'account' => $account,
            'contacts' => $contacts,
            'opportunities' => $opportunities,
        ]);
    }
    
    /**
     * Show edit form
     */
    public function edit(int $id): Response
    {
        $account = \Modules\CRM\Models\Account::findOrFail($id);
        return view('crm/accounts/edit', ['account' => $account]);
    }
    
    /**
     * Update account
     */
    public function update(Request $request, int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:200',
            'type' => 'required|in:prospect,customer,partner,vendor,other',
            'industry' => 'max:100',
            'website' => 'max:255',
            'phone' => 'max:20',
            'email' => 'email|max:255',
            'billing_address' => 'max:500',
            'shipping_address' => 'max:500',
            'annual_revenue' => 'numeric',
            'employees' => 'integer',
            'owner_id' => 'integer',
            'description' => 'max:1000',
        ]);
        
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return Response::json(['errors' => $validator->errors()], 422);
            }
            return redirect("/crm/accounts/{$id}/edit")->with('errors', $validator->errors());
        }
        
        try {
            $account = $this->accountService->update($id, $validator->validated(), auth()->id());
            
            if ($request->expectsJson()) {
                return Response::json(['account' => $account, 'message' => 'Account updated successfully']);
            }
            
            return redirect('/crm/accounts/' . $id)->with('success', 'Account updated successfully');
        } catch (\Exception $e) {
            logger()->error('Account update failed', ['error' => $e->getMessage(), 'account_id' => $id]);
            
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Failed to update account'], 500);
            }
            
            return redirect("/crm/accounts/{$id}/edit")->with('error', 'Failed to update account');
        }
    }
    
    /**
     * Delete account
     */
    public function destroy(Request $request, int $id): Response
    {
        try {
            $account = \Modules\CRM\Models\Account::findOrFail($id);
            
            // Check if account has related contacts or opportunities
            $contacts = $account->contacts();
            $opportunities = $account->opportunities();
            
            if (count($contacts) > 0 || count($opportunities) > 0) {
                $message = 'Cannot delete account with associated contacts or opportunities';
                
                if ($request->expectsJson()) {
                    return Response::json(['error' => $message], 400);
                }
                
                return redirect('/crm/accounts')->with('error', $message);
            }
            
            $account->delete();
            
            if ($request->expectsJson()) {
                return Response::json(['message' => 'Account deleted successfully']);
            }
            
            return redirect('/crm/accounts')->with('success', 'Account deleted successfully');
        } catch (\Exception $e) {
            logger()->error('Account deletion failed', ['error' => $e->getMessage(), 'account_id' => $id]);
            
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Failed to delete account'], 500);
            }
            
            return redirect('/crm/accounts')->with('error', 'Failed to delete account');
        }
    }
    
    /**
     * Assign account to user
     */
    public function assign(Request $request, int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'owner_id' => 'required|integer',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $account = $this->accountService->assign($id, $validator->validated()['owner_id'], auth()->id());
            return Response::json(['account' => $account, 'message' => 'Account assigned successfully']);
        } catch (\Exception $e) {
            logger()->error('Account assignment failed', ['error' => $e->getMessage(), 'account_id' => $id]);
            return Response::json(['error' => 'Failed to assign account'], 500);
        }
    }
    
    /**
     * Get account statistics
     */
    public function statistics(Request $request): Response
    {
        try {
            $stats = $this->accountService->getStatistics();
            return Response::json(['statistics' => $stats]);
        } catch (\Exception $e) {
            logger()->error('Failed to get account statistics', ['error' => $e->getMessage()]);
            return Response::json(['error' => 'Failed to get statistics'], 500);
        }
    }
}
