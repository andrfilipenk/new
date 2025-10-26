<?php

namespace Modules\CRM\Controllers;

use Core\Request;
use Core\Response;
use Modules\CRM\Services\LeadService;
use Core\Validation\Validator;

/**
 * Lead Controller
 * 
 * Handles HTTP requests for lead management
 */
class LeadController
{
    private LeadService $leadService;
    
    public function __construct()
    {
        $this->leadService = new LeadService();
    }
    
    /**
     * Display list of leads
     */
    public function index(Request $request): Response
    {
        $leads = \Modules\CRM\Models\Lead::all();
        
        if ($request->expectsJson()) {
            return Response::json(['leads' => $leads]);
        }
        
        return view('crm/leads/index', ['leads' => $leads]);
    }
    
    /**
     * Show create form
     */
    public function create(): Response
    {
        return view('crm/leads/create');
    }
    
    /**
     * Store new lead
     */
    public function store(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100',
            'email' => 'required|email',
            'company' => 'required|max:200',
            'phone' => 'max:20',
            'source' => 'required',
            'status' => 'required',
        ]);
        
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return Response::json(['errors' => $validator->errors()], 422);
            }
            return redirect('/crm/leads/create')->with('errors', $validator->errors());
        }
        
        try {
            $lead = $this->leadService->create($validator->validated(), auth()->id());
            
            if ($request->expectsJson()) {
                return Response::json(['lead' => $lead, 'message' => 'Lead created successfully'], 201);
            }
            
            return redirect('/crm/leads/' . $lead->id)->with('success', 'Lead created successfully');
        } catch (\Exception $e) {
            logger()->error('Lead creation failed', ['error' => $e->getMessage()]);
            
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Failed to create lead'], 500);
            }
            
            return redirect('/crm/leads/create')->with('error', 'Failed to create lead');
        }
    }
    
    /**
     * Show single lead
     */
    public function show(Request $request, int $id): Response
    {
        $lead = \Modules\CRM\Models\Lead::findOrFail($id);
        
        if ($request->expectsJson()) {
            return Response::json(['lead' => $lead]);
        }
        
        return view('crm/leads/show', ['lead' => $lead]);
    }
    
    /**
     * Show edit form
     */
    public function edit(int $id): Response
    {
        $lead = \Modules\CRM\Models\Lead::findOrFail($id);
        return view('crm/leads/edit', ['lead' => $lead]);
    }
    
    /**
     * Update lead
     */
    public function update(Request $request, int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100',
            'email' => 'required|email',
            'company' => 'max:200',
            'phone' => 'max:20',
        ]);
        
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return Response::json(['errors' => $validator->errors()], 422);
            }
            return redirect("/crm/leads/{$id}/edit")->with('errors', $validator->errors());
        }
        
        try {
            $lead = $this->leadService->update($id, $validator->validated(), auth()->id());
            
            if ($request->expectsJson()) {
                return Response::json(['lead' => $lead, 'message' => 'Lead updated successfully']);
            }
            
            return redirect('/crm/leads/' . $id)->with('success', 'Lead updated successfully');
        } catch (\Exception $e) {
            logger()->error('Lead update failed', ['error' => $e->getMessage(), 'lead_id' => $id]);
            
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Failed to update lead'], 500);
            }
            
            return redirect("/crm/leads/{$id}/edit")->with('error', 'Failed to update lead');
        }
    }
    
    /**
     * Delete lead
     */
    public function destroy(Request $request, int $id): Response
    {
        try {
            $lead = \Modules\CRM\Models\Lead::findOrFail($id);
            $lead->delete();
            
            if ($request->expectsJson()) {
                return Response::json(['message' => 'Lead deleted successfully']);
            }
            
            return redirect('/crm/leads')->with('success', 'Lead deleted successfully');
        } catch (\Exception $e) {
            logger()->error('Lead deletion failed', ['error' => $e->getMessage(), 'lead_id' => $id]);
            
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Failed to delete lead'], 500);
            }
            
            return redirect('/crm/leads')->with('error', 'Failed to delete lead');
        }
    }
    
    /**
     * Convert lead to opportunity
     */
    public function convert(Request $request, int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:200',
            'stage' => 'required',
            'amount' => 'required|numeric',
            'close_date' => 'required|date',
        ]);
        
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return Response::json(['errors' => $validator->errors()], 422);
            }
            return redirect("/crm/leads/{$id}")->with('errors', $validator->errors());
        }
        
        try {
            $result = $this->leadService->convertToOpportunity($id, $validator->validated(), auth()->id());
            
            if ($request->expectsJson()) {
                return Response::json([
                    'message' => 'Lead converted successfully',
                    'opportunity' => $result['opportunity'],
                    'contact' => $result['contact'],
                    'account' => $result['account'],
                ]);
            }
            
            return redirect('/crm/opportunities/' . $result['opportunity']->id)
                ->with('success', 'Lead converted to opportunity successfully');
        } catch (\Exception $e) {
            logger()->error('Lead conversion failed', ['error' => $e->getMessage(), 'lead_id' => $id]);
            
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Failed to convert lead'], 500);
            }
            
            return redirect("/crm/leads/{$id}")->with('error', 'Failed to convert lead');
        }
    }
    
    /**
     * Qualify lead
     */
    public function qualify(Request $request, int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'qualification' => 'required|in:qualified,disqualified',
            'reason' => 'required_if:qualification,disqualified',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $data = $validator->validated();
            
            if ($data['qualification'] === 'qualified') {
                $lead = $this->leadService->qualify($id, 'qualified', auth()->id());
            } else {
                $lead = $this->leadService->disqualify($id, $data['reason'], auth()->id());
            }
            
            return Response::json(['lead' => $lead, 'message' => 'Lead qualification updated']);
        } catch (\Exception $e) {
            logger()->error('Lead qualification failed', ['error' => $e->getMessage(), 'lead_id' => $id]);
            return Response::json(['error' => 'Failed to update qualification'], 500);
        }
    }
    
    /**
     * Assign lead to user
     */
    public function assign(Request $request, int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'assigned_to' => 'required|integer',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $lead = $this->leadService->assign($id, $validator->validated()['assigned_to'], auth()->id());
            return Response::json(['lead' => $lead, 'message' => 'Lead assigned successfully']);
        } catch (\Exception $e) {
            logger()->error('Lead assignment failed', ['error' => $e->getMessage(), 'lead_id' => $id]);
            return Response::json(['error' => 'Failed to assign lead'], 500);
        }
    }
}
