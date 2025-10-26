<?php

namespace Modules\PIM\Controllers;

use Core\Request;
use Core\Response;
use Modules\PIM\Models\Attribute;
use Core\Validation\Validator;

/**
 * Attribute Controller
 * 
 * Handles dynamic product attribute management
 */
class AttributeController
{
    public function index(Request $request): Response
    {
        $attributes = Attribute::all();
        
        if ($request->expectsJson()) {
            return Response::json(['attributes' => $attributes]);
        }
        
        return view('pim/attributes/index', ['attributes' => $attributes]);
    }
    
    public function store(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:100',
            'code' => 'required|max:50',
            'type' => 'required|in:text,number,boolean,date,select,multiselect',
            'is_required' => 'boolean',
            'is_filterable' => 'boolean',
            'options' => '',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $attribute = Attribute::create($validator->validated());
            
            return Response::json(['attribute' => $attribute, 'message' => 'Attribute created successfully'], 201);
        } catch (\Exception $e) {
            logger()->error('Attribute creation failed', ['error' => $e->getMessage()]);
            return Response::json(['error' => 'Failed to create attribute'], 500);
        }
    }
    
    public function update(Request $request, int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:100',
            'type' => 'required|in:text,number,boolean,date,select,multiselect',
            'is_required' => 'boolean',
            'is_filterable' => 'boolean',
            'options' => '',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $attribute = Attribute::findOrFail($id);
            $attribute->update($validator->validated());
            
            return Response::json(['attribute' => $attribute, 'message' => 'Attribute updated successfully']);
        } catch (\Exception $e) {
            logger()->error('Attribute update failed', ['error' => $e->getMessage(), 'attribute_id' => $id]);
            return Response::json(['error' => 'Failed to update attribute'], 500);
        }
    }
    
    public function destroy(Request $request, int $id): Response
    {
        try {
            $attribute = Attribute::findOrFail($id);
            $attribute->delete();
            
            return Response::json(['message' => 'Attribute deleted successfully']);
        } catch (\Exception $e) {
            logger()->error('Attribute deletion failed', ['error' => $e->getMessage(), 'attribute_id' => $id]);
            return Response::json(['error' => 'Failed to delete attribute'], 500);
        }
    }
}
