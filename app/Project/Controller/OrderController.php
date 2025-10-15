<?php

namespace Project\Controller;

use Core\Mvc\Controller;
use Project\Service\OrderService;
use Project\Model\Order;

/**
 * OrderController
 * Handles order detail view and operations
 */
class OrderController extends Controller
{
    protected $orderService;

    public function __construct()
    {
        parent::__construct();
        $this->orderService = new OrderService();
    }

    /**
     * Order detail view with phases, positions, materials, activities
     */
    public function detailAction()
    {
        $orderId = (int) $this->request->getParam('id');
        $activeTab = $this->request->get('tab') ?? 'positions';

        // Get complete order data
        $data = $this->orderService->getOrderDetail($orderId);

        // Get phase timeline
        $phaseTimeline = $this->orderService->getPhaseTimeline($orderId);

        $this->view->setVar('order', $data['order']);
        $this->view->setVar('project', $data['project']);
        $this->view->setVar('positions', $data['positions']);
        $this->view->setVar('phases', $data['phases']);
        $this->view->setVar('phaseTimeline', $phaseTimeline);
        $this->view->setVar('materials', $data['materials']);
        $this->view->setVar('activities', $data['activities']);
        $this->view->setVar('comments', $data['comments']);
        $this->view->setVar('progress', $data['progress']);
        $this->view->setVar('activeTab', $activeTab);

        $this->view->render('project/order/detail');
    }

    /**
     * Create new order
     */
    public function createAction()
    {
        $projectId = (int) $this->request->getParam('projectId');

        if ($this->request->isPost()) {
            $data = [
                'project_id' => $projectId,
                'order_number' => $this->request->post('order_number'),
                'title' => $this->request->post('title'),
                'description' => $this->request->post('description'),
                'start_date' => $this->request->post('start_date'),
                'end_date' => $this->request->post('end_date'),
                'status' => $this->request->post('status') ?? Order::STATUS_DRAFT,
                'total_value' => $this->request->post('total_value'),
                'created_by' => $this->session->get('user_id'),
            ];

            $order = new Order($data);
            
            if ($order->save()) {
                $this->session->setFlash('success', 'Order created successfully');
                $this->response->redirect('/orders/' . $order->id);
                return;
            } else {
                $this->session->setFlash('error', 'Failed to create order');
            }
        }

        $this->view->setVar('projectId', $projectId);
        $this->view->setVar('statuses', Order::getStatuses());
        $this->view->render('project/order/create');
    }

    /**
     * Update existing order
     */
    public function updateAction()
    {
        $orderId = (int) $this->request->getParam('id');
        $order = Order::findOrFail($orderId);

        if ($this->request->isPost()) {
            $data = [
                'order_number' => $this->request->post('order_number'),
                'title' => $this->request->post('title'),
                'description' => $this->request->post('description'),
                'start_date' => $this->request->post('start_date'),
                'end_date' => $this->request->post('end_date'),
                'status' => $this->request->post('status'),
                'total_value' => $this->request->post('total_value'),
                'updated_by' => $this->session->get('user_id'),
            ];

            $order->fill($data);
            
            if ($order->save()) {
                $this->session->setFlash('success', 'Order updated successfully');
                $this->response->redirect('/orders/' . $order->id);
                return;
            } else {
                $this->session->setFlash('error', 'Failed to update order');
            }
        }

        $this->view->setVar('order', $order);
        $this->view->setVar('statuses', Order::getStatuses());
        $this->view->render('project/order/edit');
    }

    /**
     * Update order phase
     */
    public function updatePhaseAction()
    {
        if (!$this->request->isPost()) {
            $this->response->setStatusCode(405);
            return;
        }

        $phaseId = (int) $this->request->getParam('phaseId');
        
        $data = [
            'status' => $this->request->post('status'),
            'completion_percentage' => $this->request->post('completion_percentage'),
        ];

        $phase = $this->orderService->updatePhase($phaseId, $data);

        $this->response->setJsonContent([
            'success' => true,
            'phase' => $phase,
        ]);
    }

    /**
     * Add comment to order
     */
    public function addCommentAction()
    {
        if (!$this->request->isPost()) {
            $this->response->setStatusCode(405);
            return;
        }

        $orderId = (int) $this->request->getParam('id');
        $userId = $this->session->get('user_id');
        $content = $this->request->post('content');
        $parentId = $this->request->post('parent_id');

        $comment = $this->orderService->addComment($orderId, $userId, $content, $parentId);

        if ($this->request->isAjax()) {
            $this->response->setJsonContent([
                'success' => true,
                'comment' => $comment,
            ]);
        } else {
            $this->session->setFlash('success', 'Comment added successfully');
            $this->response->redirect('/orders/' . $orderId . '?tab=comments');
        }
    }
}
