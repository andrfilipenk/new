<?php

namespace Project\Controller;

use Core\Mvc\Controller;
use Project\Model\Position;
use Project\Model\Material;

/**
 * PositionController
 * Handles position management and material tracking
 */
class PositionController extends Controller
{
    /**
     * Position detail view with materials
     */
    public function detailAction()
    {
        $positionId = (int) $this->request->getParam('id');
        $position = Position::findOrFail($positionId);

        // Get materials for this position
        $materials = $position->materials()->get();
        
        // Calculate totals
        $requiredQty = [];
        $usedQty = [];
        $totalCost = $position->getTotalMaterialCost();

        $this->view->setVar('position', $position);
        $this->view->setVar('order', $position->order);
        $this->view->setVar('materials', $materials);
        $this->view->setVar('totalCost', $totalCost);
        $this->view->setVar('statuses', Position::getStatuses());

        $this->view->render('project/position/detail');
    }

    /**
     * Create new position
     */
    public function createAction()
    {
        $orderId = (int) $this->request->getParam('orderId');

        if ($this->request->isPost()) {
            $data = [
                'order_id' => $orderId,
                'position_number' => $this->request->post('position_number'),
                'product_code' => $this->request->post('product_code'),
                'description' => $this->request->post('description'),
                'quantity' => $this->request->post('quantity'),
                'unit' => $this->request->post('unit'),
                'unit_price' => $this->request->post('unit_price'),
                'status' => $this->request->post('status') ?? Position::STATUS_PENDING,
                'assigned_to' => $this->request->post('assigned_to'),
                'target_date' => $this->request->post('target_date'),
                'specifications' => $this->request->post('specifications'),
            ];

            $position = new Position($data);
            
            if ($position->save()) {
                $this->session->setFlash('success', 'Position created successfully');
                $this->response->redirect('/positions/' . $position->id);
                return;
            } else {
                $this->session->setFlash('error', 'Failed to create position');
            }
        }

        $this->view->setVar('orderId', $orderId);
        $this->view->setVar('statuses', Position::getStatuses());
        $this->view->render('project/position/create');
    }

    /**
     * Update existing position
     */
    public function updateAction()
    {
        $positionId = (int) $this->request->getParam('id');
        $position = Position::findOrFail($positionId);

        if ($this->request->isPost()) {
            $data = [
                'position_number' => $this->request->post('position_number'),
                'product_code' => $this->request->post('product_code'),
                'description' => $this->request->post('description'),
                'quantity' => $this->request->post('quantity'),
                'unit' => $this->request->post('unit'),
                'unit_price' => $this->request->post('unit_price'),
                'status' => $this->request->post('status'),
                'assigned_to' => $this->request->post('assigned_to'),
                'target_date' => $this->request->post('target_date'),
                'specifications' => $this->request->post('specifications'),
            ];

            $position->fill($data);
            
            if ($position->save()) {
                $this->session->setFlash('success', 'Position updated successfully');
                $this->response->redirect('/positions/' . $position->id);
                return;
            } else {
                $this->session->setFlash('error', 'Failed to update position');
            }
        }

        $this->view->setVar('position', $position);
        $this->view->setVar('statuses', Position::getStatuses());
        $this->view->render('project/position/edit');
    }

    /**
     * Delete position
     */
    public function deleteAction()
    {
        if (!$this->request->isPost()) {
            $this->response->setStatusCode(405);
            return;
        }

        $positionId = (int) $this->request->getParam('id');
        $position = Position::findOrFail($positionId);
        $orderId = $position->order_id;

        if ($position->delete()) {
            $this->session->setFlash('success', 'Position deleted successfully');
            $this->response->redirect('/orders/' . $orderId);
        } else {
            $this->session->setFlash('error', 'Failed to delete position');
            $this->response->redirect('/positions/' . $positionId);
        }
    }

    /**
     * Update material for position
     */
    public function updateMaterialAction()
    {
        if (!$this->request->isPost()) {
            $this->response->setStatusCode(405);
            return;
        }

        $positionId = (int) $this->request->getParam('id');
        $materialId = $this->request->post('material_id');

        if ($materialId) {
            // Update existing material
            $material = Material::findOrFail($materialId);
        } else {
            // Create new material
            $material = new Material();
            $material->position_id = $positionId;
            $position = Position::findOrFail($positionId);
            $material->order_id = $position->order_id;
        }

        $material->fill([
            'material_type' => $this->request->post('material_type'),
            'specification' => $this->request->post('specification'),
            'quantity' => $this->request->post('quantity'),
            'unit' => $this->request->post('unit'),
            'unit_cost' => $this->request->post('unit_cost'),
            'supplier' => $this->request->post('supplier'),
            'usage_date' => $this->request->post('usage_date'),
            'notes' => $this->request->post('notes'),
        ]);

        if ($material->save()) {
            $this->response->setJsonContent([
                'success' => true,
                'material' => $material,
            ]);
        } else {
            $this->response->setJsonContent([
                'success' => false,
                'error' => 'Failed to save material',
            ]);
        }
    }
}
