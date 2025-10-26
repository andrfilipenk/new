<?php

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use Core\Request;
use Core\Response;
use Core\Config\Config;

class SettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $settings = [
            'app_name' => Config::get('app.name', 'CRM-ERP-CMS-PIM'),
            'app_env' => Config::get('app.env', 'production'),
            'app_debug' => Config::get('app.debug', false),
            'timezone' => Config::get('app.timezone', 'UTC'),
        ];
        
        return $this->view('admin/settings/index', ['settings' => $settings]);
    }
    
    public function update(Request $request): Response
    {
        // In a real implementation, this would update configuration files
        // For now, just redirect back with success message
        return $this->redirect('/admin/settings')->with('success', 'Settings updated successfully');
    }
}
