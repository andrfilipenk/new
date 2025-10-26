<?php

namespace Modules\CRM\Controllers;

use Core\Request;
use Core\Response;
use Modules\CRM\Services\ContactService;
use Core\Validation\Validator;

/**
 * Contact Controller
 * 
 * Handles HTTP requests for contact management
 */
class ContactController
{
    private ContactService $contactService;
    
    public function __construct()
    {
        $this->contactService = new ContactService();
    }
    
    /**
     * Display list of contacts
     */
    public function index(Request $request): Response
    {
        $accountId = $request->get('account_id');
        
        if ($accountId) {
            $contacts = \Core\Database\DB::table('crm_contacts')
                ->where('account_id', '=', $accountId)
                ->get();
            $contacts = array_map(fn($row) => \Modules\CRM\Models\Contact::hydrate($row), $contacts);
        } else {
            $contacts = \Modules\CRM\Models\Contact::all();
        }
        
        if ($request->expectsJson()) {
            return Response::json(['contacts' => $contacts]);
        }
        
        return view('crm/contacts/index', [
            'contacts' => $contacts,
            'accountId' => $accountId
        ]);
    }
    
    /**
     * Show create form
     */
    public function create(Request $request): Response
    {
        $accountId = $request->get('account_id');
        
        // Get all accounts for dropdown
        $accounts = \Modules\CRM\Models\Account::all();
        
        return view('crm/contacts/create', [
            'accounts' => $accounts,
            'accountId' => $accountId
        ]);
    }
    
    /**
     * Store new contact
     */
    public function store(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'account_id' => 'integer',
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100',
            'title' => 'max:100',
            'email' => 'required|email|max:255',
            'phone' => 'max:20',
            'mobile' => 'max:20',
            'address' => 'max:500',
            'linkedin' => 'max:255',
            'owner_id' => 'integer',
            'notes' => 'max:1000',
        ]);
        
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return Response::json(['errors' => $validator->errors()], 422);
            }
            return redirect('/crm/contacts/create')->with('errors', $validator->errors());
        }
        
        try {
            $contact = $this->contactService->create($validator->validated(), auth()->id());
            
            if ($request->expectsJson()) {
                return Response::json(['contact' => $contact, 'message' => 'Contact created successfully'], 201);
            }
            
            return redirect('/crm/contacts/' . $contact->id)->with('success', 'Contact created successfully');
        } catch (\Exception $e) {
            logger()->error('Contact creation failed', ['error' => $e->getMessage()]);
            
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Failed to create contact'], 500);
            }
            
            return redirect('/crm/contacts/create')->with('error', 'Failed to create contact');
        }
    }
    
    /**
     * Show single contact
     */
    public function show(Request $request, int $id): Response
    {
        $contact = \Modules\CRM\Models\Contact::findOrFail($id);
        
        // Load relationships
        $account = $contact->account();
        
        // Get activities for this contact
        $activities = \Core\Database\DB::table('crm_activities')
            ->where('related_type', '=', 'Contact')
            ->where('related_id', '=', $id)
            ->orderBy('activity_date', 'DESC')
            ->limit(10)
            ->get();
        
        if ($request->expectsJson()) {
            return Response::json([
                'contact' => $contact,
                'account' => $account,
                'activities' => $activities,
            ]);
        }
        
        return view('crm/contacts/show', [
            'contact' => $contact,
            'account' => $account,
            'activities' => $activities,
        ]);
    }
    
    /**
     * Show edit form
     */
    public function edit(int $id): Response
    {
        $contact = \Modules\CRM\Models\Contact::findOrFail($id);
        
        // Get all accounts for dropdown
        $accounts = \Modules\CRM\Models\Account::all();
        
        return view('crm/contacts/edit', [
            'contact' => $contact,
            'accounts' => $accounts
        ]);
    }
    
    /**
     * Update contact
     */
    public function update(Request $request, int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'account_id' => 'integer',
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100',
            'title' => 'max:100',
            'email' => 'required|email|max:255',
            'phone' => 'max:20',
            'mobile' => 'max:20',
            'address' => 'max:500',
            'linkedin' => 'max:255',
            'owner_id' => 'integer',
            'notes' => 'max:1000',
        ]);
        
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return Response::json(['errors' => $validator->errors()], 422);
            }
            return redirect("/crm/contacts/{$id}/edit")->with('errors', $validator->errors());
        }
        
        try {
            $contact = $this->contactService->update($id, $validator->validated(), auth()->id());
            
            if ($request->expectsJson()) {
                return Response::json(['contact' => $contact, 'message' => 'Contact updated successfully']);
            }
            
            return redirect('/crm/contacts/' . $id)->with('success', 'Contact updated successfully');
        } catch (\Exception $e) {
            logger()->error('Contact update failed', ['error' => $e->getMessage(), 'contact_id' => $id]);
            
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Failed to update contact'], 500);
            }
            
            return redirect("/crm/contacts/{$id}/edit")->with('error', 'Failed to update contact');
        }
    }
    
    /**
     * Delete contact
     */
    public function destroy(Request $request, int $id): Response
    {
        try {
            $contact = \Modules\CRM\Models\Contact::findOrFail($id);
            $contact->delete();
            
            if ($request->expectsJson()) {
                return Response::json(['message' => 'Contact deleted successfully']);
            }
            
            return redirect('/crm/contacts')->with('success', 'Contact deleted successfully');
        } catch (\Exception $e) {
            logger()->error('Contact deletion failed', ['error' => $e->getMessage(), 'contact_id' => $id]);
            
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Failed to delete contact'], 500);
            }
            
            return redirect('/crm/contacts')->with('error', 'Failed to delete contact');
        }
    }
    
    /**
     * Assign contact to user
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
            $contact = $this->contactService->assign($id, $validator->validated()['owner_id'], auth()->id());
            return Response::json(['contact' => $contact, 'message' => 'Contact assigned successfully']);
        } catch (\Exception $e) {
            logger()->error('Contact assignment failed', ['error' => $e->getMessage(), 'contact_id' => $id]);
            return Response::json(['error' => 'Failed to assign contact'], 500);
        }
    }
    
    /**
     * Link contact to account
     */
    public function linkToAccount(Request $request, int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|integer',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $contact = $this->contactService->linkToAccount($id, $validator->validated()['account_id'], auth()->id());
            return Response::json(['contact' => $contact, 'message' => 'Contact linked to account successfully']);
        } catch (\Exception $e) {
            logger()->error('Contact-Account linking failed', ['error' => $e->getMessage(), 'contact_id' => $id]);
            return Response::json(['error' => 'Failed to link contact to account'], 500);
        }
    }
}
