<?php

namespace Modules\PIM\Controllers;

use Core\Request;
use Core\Response;
use Modules\PIM\Models\PriceList;
use Modules\PIM\Models\PriceListItem;
use Core\Validation\Validator;

/**
 * Price List Controller
 * 
 * Handles multi-currency pricing management
 */
class PriceListController
{
    public function index(Request $request): Response
    {
        $priceLists = PriceList::all();
        
        if ($request->expectsJson()) {
            return Response::json(['price_lists' => $priceLists]);
        }
        
        return view('pim/price-lists/index', ['priceLists' => $priceLists]);
    }
    
    public function store(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:200',
            'currency' => 'required|max:3',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $priceList = PriceList::create($validator->validated());
            
            return Response::json(['price_list' => $priceList, 'message' => 'Price list created successfully'], 201);
        } catch (\Exception $e) {
            logger()->error('Price list creation failed', ['error' => $e->getMessage()]);
            return Response::json(['error' => 'Failed to create price list'], 500);
        }
    }
    
    public function show(Request $request, int $id): Response
    {
        $priceList = PriceList::findOrFail($id);
        $items = PriceListItem::where('price_list_id', $id)->get();
        
        if ($request->expectsJson()) {
            return Response::json(['price_list' => $priceList, 'items' => $items]);
        }
        
        return view('pim/price-lists/show', ['priceList' => $priceList, 'items' => $items]);
    }
    
    public function addItem(Request $request, int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer',
            'price' => 'required|numeric',
            'min_quantity' => 'integer',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $data = $validator->validated();
            $data['price_list_id'] = $id;
            
            $item = PriceListItem::create($data);
            
            return Response::json(['item' => $item, 'message' => 'Price added successfully']);
        } catch (\Exception $e) {
            logger()->error('Price list item creation failed', ['error' => $e->getMessage()]);
            return Response::json(['error' => 'Failed to add price'], 500);
        }
    }
}
