<?php

namespace Modules\CMS\Controllers;

use Core\Request;
use Core\Response;
use Modules\CMS\Models\Menu;
use Modules\CMS\Models\MenuItem;
use Core\Validation\Validator;

/**
 * Menu Controller
 * 
 * Handles navigation menu management with drag-drop ordering
 */
class MenuController
{
    public function index(Request $request): Response
    {
        $menus = Menu::all();
        
        if ($request->expectsJson()) {
            return Response::json(['menus' => $menus]);
        }
        
        return view('cms/menus/index', ['menus' => $menus]);
    }
    
    public function show(Request $request, int $id): Response
    {
        $menu = Menu::findOrFail($id);
        $menuModel = Menu::findOrFail($id);
        
        $items = $menuModel->items();
        
        if ($request->expectsJson()) {
            return Response::json(['menu' => $menu, 'items' => $items]);
        }
        
        return view('cms/menus/show', ['menu' => $menu, 'items' => $items]);
    }
    
    public function store(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:100',
            'location' => 'required|max:50',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $menu = Menu::create($validator->validated());
            return Response::json(['menu' => $menu, 'message' => 'Menu created successfully'], 201);
        } catch (\Exception $e) {
            logger()->error('Menu creation failed', ['error' => $e->getMessage()]);
            return Response::json(['error' => 'Failed to create menu'], 500);
        }
    }
    
    public function updateItems(Request $request, int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $items = json_decode($validator->validated()['items'], true);
            
            // Delete existing items
            \Core\Database\DB::table('cms_menu_items')->where('menu_id', '=', $id)->delete();
            
            // Insert new items
            foreach ($items as $index => $item) {
                MenuItem::create([
                    'menu_id' => $id,
                    'parent_id' => $item['parent_id'] ?? null,
                    'title' => $item['title'],
                    'url' => $item['url'],
                    'target' => $item['target'] ?? '_self',
                    'sort_order' => $index,
                ]);
            }
            
            return Response::json(['message' => 'Menu items updated successfully']);
        } catch (\Exception $e) {
            logger()->error('Menu items update failed', ['error' => $e->getMessage(), 'menu_id' => $id]);
            return Response::json(['error' => 'Failed to update menu items'], 500);
        }
    }
}
