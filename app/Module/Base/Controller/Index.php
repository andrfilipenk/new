<?php
// app/Module/Base/Controller/Index.php
namespace Module\Base\Controller;

use Core\Mvc\Controller;

class Index extends Controller
{
    public function indexAction(): array
    {
        // Return data to be passed to the view (auto-rendered)
        return [
            'title' => 'Home Page',
            'name' => 'John Doe',
            'users' => [
                ['name' => 'Alice'],
                ['name' => 'Bob'],
                ['name' => 'Charlie']
            ]
        ];
    }
    
    public function afterExecute(): void
    {
        // This is called after the action executes
        // You can modify the response here if needed
        parent::afterExecute();
    }
}