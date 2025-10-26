<?php

namespace Modules\CRM\Controllers;

use Core\Request;
use Core\Response;
use Modules\CRM\Services\OpportunityService;
use Core\Validation\Validator;

/**
 * Opportunity Controller
 * 
 * Handles HTTP requests for sales opportunity management
 */
class OpportunityController
{
    private OpportunityService $opportunityService;
    
    public function __construct()
    {
        $this->opportunityService = new OpportunityService();
    }
    
    /**
     * Display list of opportunities
     */
    public function index(Request $request): Response
    {
        $accountId = $request->get('account_id');
        
        if ($accountId) {
            $opportunities = \Core\Database\DB::table('crm_opportunities')
                ->where('account_id', '=', $accountId)
                ->get();
            $opportunities = array_map(fn($row) => \Modules\CRM\Models\Opportunity::hydrate($row), $opportunities);
        } else {
            $opportunities = \Modules\CRM\Models\Opportunity::all();
        }
        
        if ($request->expectsJson()) {
            return Response::json(['opportunities' => $opportunities]);
        }
        
        return view('crm/opportunities/index', [
            'opportunities' => $opportunities,
            'accountId' => $accountId
        ]);
    }
    
    /**
     * Show pipeline view
     */
    public function pipeline(Request $request): Response
    {
        $stages = [
            'prospecting' => [],
            'qualification' => [],
            'needs_analysis' => [],
            'proposal' => [],
            'negotiation' => [],
            'closed_won' => [],
            'closed_lost' => [],
        ];
        
        $opportunities = \Modules\CRM\Models\Opportunity::all();
        
        foreach ($opportunities as $opportunity) {
            $stage = $opportunity->stage ?? 'prospecting';
            if (isset($stages[$stage])) {
                $stages[$stage][] = $opportunity;
            }
        }
        
        if ($request->expectsJson()) {
            return Response::json(['pipeline' => $stages]);
        }
        
        return view('crm/opportunities/pipeline', ['pipeline' => $stages]);
    }
    
    /**
     * Show create form
     */
    public function create(Request $request): Response
    {
        $accountId = $request->get('account_id');
        $contactId = $request->get('contact_id');
        
        // Get all accounts and contacts for dropdowns
        $accounts = \Modules\CRM\Models\Account::all();
        $contacts = \Modules\CRM\Models\Contact::all();
        
        return view('crm/opportunities/create', [
            'accounts' => $accounts,
            'contacts' => $contacts,
            'accountId' => $accountId,
            'contactId' => $contactId,
        ]);
    }
    
    /**
     * Store new opportunity
     */
    public function store(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'account_id' => 'integer',
            'contact_id' => 'integer',
            'name' => 'required|max:200',
            'amount' => 'required|numeric',
            'close_date' => 'required|date',
            'stage' => 'required|in:prospecting,qualification,needs_analysis,proposal,negotiation,closed_won,closed_lost',
            'probability' => 'integer|min:0|max:100',
            'type' => 'max:50',
            'lead_source' => 'max:100',
            'owner_id' => 'integer',
            'description' => 'max:1000',
            'next_steps' => 'max:500',
        ]);
        
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return Response::json(['errors' => $validator->errors()], 422);
            }
            return redirect('/crm/opportunities/create')->with('errors', $validator->errors());
        }
        
        try {
            $opportunity = $this->opportunityService->create($validator->validated(), auth()->id());
            
            if ($request->expectsJson()) {
                return Response::json(['opportunity' => $opportunity, 'message' => 'Opportunity created successfully'], 201);
            }
            
            return redirect('/crm/opportunities/' . $opportunity->id)->with('success', 'Opportunity created successfully');
        } catch (\Exception $e) {
            logger()->error('Opportunity creation failed', ['error' => $e->getMessage()]);
            
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Failed to create opportunity'], 500);
            }
            
            return redirect('/crm/opportunities/create')->with('error', 'Failed to create opportunity');
        }
    }
    
    /**
     * Show single opportunity
     */
    public function show(Request $request, int $id): Response
    {
        $opportunity = \Modules\CRM\Models\Opportunity::findOrFail($id);
        
        // Load relationships
        $account = $opportunity->account();
        $contact = $opportunity->contact();
        
        // Get activities for this opportunity
        $activities = \Core\Database\DB::table('crm_activities')
            ->where('related_type', '=', 'Opportunity')
            ->where('related_id', '=', $id)
            ->orderBy('activity_date', 'DESC')
            ->limit(10)
            ->get();
        
        if ($request->expectsJson()) {
            return Response::json([
                'opportunity' => $opportunity,
                'account' => $account,
                'contact' => $contact,
                'activities' => $activities,
            ]);
        }
        
        return view('crm/opportunities/show', [
            'opportunity' => $opportunity,
            'account' => $account,
            'contact' => $contact,
            'activities' => $activities,
        ]);
    }
    
    /**
     * Show edit form
     */
    public function edit(int $id): Response
    {
        $opportunity = \Modules\CRM\Models\Opportunity::findOrFail($id);
        
        // Get all accounts and contacts for dropdowns
        $accounts = \Modules\CRM\Models\Account::all();
        $contacts = \Modules\CRM\Models\Contact::all();
        
        return view('crm/opportunities/edit', [
            'opportunity' => $opportunity,
            'accounts' => $accounts,
            'contacts' => $contacts,
        ]);
    }
    
    /**
     * Update opportunity
     */
    public function update(Request $request, int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'account_id' => 'integer',
            'contact_id' => 'integer',
            'name' => 'required|max:200',
            'amount' => 'required|numeric',
            'close_date' => 'required|date',
            'stage' => 'required|in:prospecting,qualification,needs_analysis,proposal,negotiation,closed_won,closed_lost',
            'probability' => 'integer|min:0|max:100',
            'type' => 'max:50',
            'lead_source' => 'max:100',
            'owner_id' => 'integer',
            'description' => 'max:1000',
            'next_steps' => 'max:500',
        ]);
        
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return Response::json(['errors' => $validator->errors()], 422);
            }
            return redirect("/crm/opportunities/{$id}/edit")->with('errors', $validator->errors());
        }
        
        try {
            $opportunity = $this->opportunityService->update($id, $validator->validated(), auth()->id());
            
            if ($request->expectsJson()) {
                return Response::json(['opportunity' => $opportunity, 'message' => 'Opportunity updated successfully']);
            }
            
            return redirect('/crm/opportunities/' . $id)->with('success', 'Opportunity updated successfully');
        } catch (\Exception $e) {
            logger()->error('Opportunity update failed', ['error' => $e->getMessage(), 'opportunity_id' => $id]);
            
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Failed to update opportunity'], 500);
            }
            
            return redirect("/crm/opportunities/{$id}/edit")->with('error', 'Failed to update opportunity');
        }
    }
    
    /**
     * Delete opportunity
     */
    public function destroy(Request $request, int $id): Response
    {
        try {
            $opportunity = \Modules\CRM\Models\Opportunity::findOrFail($id);
            $opportunity->delete();
            
            if ($request->expectsJson()) {
                return Response::json(['message' => 'Opportunity deleted successfully']);
            }
            
            return redirect('/crm/opportunities')->with('success', 'Opportunity deleted successfully');
        } catch (\Exception $e) {
            logger()->error('Opportunity deletion failed', ['error' => $e->getMessage(), 'opportunity_id' => $id]);
            
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Failed to delete opportunity'], 500);
            }
            
            return redirect('/crm/opportunities')->with('error', 'Failed to delete opportunity');
        }
    }
    
    /**
     * Update opportunity stage
     */
    public function updateStage(Request $request, int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'stage' => 'required|in:prospecting,qualification,needs_analysis,proposal,negotiation,closed_won,closed_lost',
            'probability' => 'integer|min:0|max:100',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $opportunity = $this->opportunityService->updateStage(
                $id,
                $validator->validated()['stage'],
                $validator->validated()['probability'] ?? null,
                auth()->id()
            );
            
            return Response::json(['opportunity' => $opportunity, 'message' => 'Stage updated successfully']);
        } catch (\Exception $e) {
            logger()->error('Opportunity stage update failed', ['error' => $e->getMessage(), 'opportunity_id' => $id]);
            return Response::json(['error' => 'Failed to update stage'], 500);
        }
    }
    
    /**
     * Mark opportunity as won
     */
    public function markAsWon(Request $request, int $id): Response
    {
        try {
            $opportunity = $this->opportunityService->markAsWon($id, auth()->id());
            
            if ($request->expectsJson()) {
                return Response::json(['opportunity' => $opportunity, 'message' => 'Opportunity marked as won']);
            }
            
            return redirect('/crm/opportunities/' . $id)->with('success', 'Opportunity marked as won');
        } catch (\Exception $e) {
            logger()->error('Mark opportunity as won failed', ['error' => $e->getMessage(), 'opportunity_id' => $id]);
            
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Failed to mark as won'], 500);
            }
            
            return redirect('/crm/opportunities/' . $id)->with('error', 'Failed to mark as won');
        }
    }
    
    /**
     * Mark opportunity as lost
     */
    public function markAsLost(Request $request, int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'max:500',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $opportunity = $this->opportunityService->markAsLost(
                $id,
                $validator->validated()['reason'] ?? null,
                auth()->id()
            );
            
            if ($request->expectsJson()) {
                return Response::json(['opportunity' => $opportunity, 'message' => 'Opportunity marked as lost']);
            }
            
            return redirect('/crm/opportunities/' . $id)->with('success', 'Opportunity marked as lost');
        } catch (\Exception $e) {
            logger()->error('Mark opportunity as lost failed', ['error' => $e->getMessage(), 'opportunity_id' => $id]);
            
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Failed to mark as lost'], 500);
            }
            
            return redirect('/crm/opportunities/' . $id)->with('error', 'Failed to mark as lost');
        }
    }
    
    /**
     * Get opportunity statistics
     */
    public function statistics(Request $request): Response
    {
        try {
            $stats = $this->opportunityService->getStatistics();
            return Response::json(['statistics' => $stats]);
        } catch (\Exception $e) {
            logger()->error('Failed to get opportunity statistics', ['error' => $e->getMessage()]);
            return Response::json(['error' => 'Failed to get statistics'], 500);
        }
    }
}
