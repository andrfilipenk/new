<?php
// app/Admin/Controller/Holidays.php
namespace Admin\Controller;

use Core\Mvc\Controller;
use Admin\Model\HolidayRequest;
use Admin\Model\User;

class Holidays extends Controller
{
    // List all holiday requests
    public function indexAction()
    {
        $requests = HolidayRequest::with(['user'])->get();
        return $this->render('holidays/index', ['requests' => $requests]);
    }

    // Show create form and handle creation
    public function createAction()
    {
        if ($this->isPost()) {
            $data = $this->getRequest()->all();
            $userId = (int) ($data['user_id'] ?? 0);
            $beginDate = trim($data['begin_date'] ?? '');
            $endDate = trim($data['end_date'] ?? '');
            if ($userId && $beginDate && $endDate) {
                $request = HolidayRequest::createRequest($userId, $beginDate, $endDate);
                if ($request && $request->isValidDateRange()) {
                    $this->flashSuccess('Holiday request created successfully.');
                    return $this->redirect('admin/holidays');
                } else {
                    $this->flashError('Invalid date range or failed to create request.');
                }
            } else {
                $this->flashError('All fields are required.');
            }
        }
        $users = User::all();
        return $this->render('holidays/create', ['users' => $users]);
    }

    // Grant or deny request
    public function manageAction()
    {
        $id = $this->getDispatcher()->getParam('id');
        $request = HolidayRequest::find($id);
        if (!$request) {
            $this->flashError('Holiday request not found.');
            return $this->redirect('admin/holidays');
        }
        if ($this->isPost()) {
            $data = $this->getRequest()->all();
            $action = $data['action'] ?? '';
            if ($action === 'grant') {
                $request->setGranted(true);
                $this->flashSuccess('Holiday request granted.');
            } elseif ($action === 'deny') {
                $request->setGranted(false);
                $this->flashSuccess('Holiday request denied.');
            }
            return $this->redirect('admin/holidays');
        }
        return $this->render('holidays/manage', ['request' => $request]);
    }

    // Delete request
    public function deleteAction()
    {
        $id = $this->getDispatcher()->getParam('id');
        $request = HolidayRequest::find($id);
        if ($request) {
            if ($request->delete()) {
                $this->flashSuccess('Holiday request deleted.');
            } else {
                $this->flashError('Failed to delete holiday request.');
            }
        } else {
            $this->flashError('Holiday request not found.');
        }
        
        return $this->redirect('admin/holidays');
    }

    // User's own requests
    public function myrequestsAction()
    {
        // For demo purposes, we'll use user ID 1
        // In real implementation, get from session/authentication
        $userId = 1;
        $requests = HolidayRequest::query()
            ->where('user_id', $userId)
            ->get();
        
        $userRequests = array_map([HolidayRequest::class, 'newFromBuilder'], $requests);
        return $this->render('holidays/my-requests', ['requests' => $userRequests]);
    }
}