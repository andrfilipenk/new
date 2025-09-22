<?php
// app/Core/Crud/CrudControllerTrait.php
namespace Core\Crud;

use Core\Http\Response;
use Core\Http\Request;
use Core\Validation\ValidationException;

trait CrudControllerTrait
{
    protected CrudService $crudService;
    protected string $viewPath;
    protected string $routePrefix;

    // INDEX - List view
    public function indexAction(Request $request)
    {
        $filters = $request->get() ?? [];
        $perPage = $request->get('per_page', 15);
        
        $data = $this->crudService->list($filters, $perPage);
        
        if ($request->isAjax()) {
            return Response::json($data);
        }
        
        return $this->render("{$this->viewPath}/index", [
            'data' => $data,
            'filters' => $filters
        ]);
    }

    // CREATE - Show creation form
    public function createAction()
    {
        return $this->render("{$this->viewPath}/form", [
            'action' => 'create',
            'item' => null
        ]);
    }

    // STORE - Handle creation
    public function storeAction(Request $request)
    {
        try {
            $item = $this->crudService->create($request->all());
            
            $this->flashSuccess($this->getSuccessMessage('created'));
            
            if ($request->isAjax()) {
                return Response::json($item, 201);
            }
            
            return $this->redirectToIndex();
            
        } catch (ValidationException $e) {
            return $this->handleValidationError($e, $request);
        } catch (\Exception $e) {
            return $this->handleError($e, $request);
        }
    }

    // SHOW - Display single item
    public function showAction($id)
    {
        $item = $this->crudService->find($id);
        
        if (!$item) {
            return $this->handleNotFound();
        }
        
        return $this->render("{$this->viewPath}/show", [
            'item' => $item
        ]);
    }

    // EDIT - Show edit form
    public function editAction($id)
    {
        $item = $this->crudService->find($id);
        
        if (!$item) {
            return $this->handleNotFound();
        }
        
        return $this->render("{$this->viewPath}/form", [
            'action' => 'edit',
            'item' => $item
        ]);
    }

    // UPDATE - Handle update
    public function updateAction(Request $request, $id)
    {
        try {
            $item = $this->crudService->update($id, $request->all());
            
            $this->flashSuccess($this->getSuccessMessage('updated'));
            
            if ($request->isAjax()) {
                return Response::json($item);
            }
            
            return $this->redirectToIndex();
            
        } catch (ValidationException $e) {
            return $this->handleValidationError($e, $request);
        } catch (\Exception $e) {
            return $this->handleError($e, $request);
        }
    }

    // DELETE - Handle deletion
    public function deleteAction($id)
    {
        try {
            $this->crudService->delete($id);
            
            $this->flashSuccess($this->getSuccessMessage('deleted'));
            
            if ($this->getRequest()->isAjax()) {
                return Response::json(['success' => true]);
            }
            
            return $this->redirectToIndex();
            
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    // HELPER METHODS
    protected function redirectToIndex(): Response
    {
        return $this->redirect($this->routePrefix);
    }

    protected function handleValidationError(ValidationException $e, Request $request)
    {
        if ($request->isAjax()) {
            return Response::json([
                'errors' => $e->getErrors()
            ], 422);
        }
        
        $this->flashError('Please fix the validation errors');
        
        // Store old input and errors in session for form repopulation
        $this->getDI()->get('session')->flash('old_input', $request->all());
        $this->getDI()->get('session')->flash('errors', $e->getErrors());
        
        return $this->redirect($request->uri());
    }

    protected function handleError(\Exception $e, Request $request = null)
    {
        $message = $e->getMessage();
        
        if ($request && $request->isAjax()) {
            return Response::json(['error' => $message], 500);
        }
        
        $this->flashError($message);
        return $this->redirectToIndex();
    }

    protected function handleNotFound()
    {
        $this->flashError('Record not found');
        return $this->redirectToIndex();
    }

    protected function getSuccessMessage(string $action): string
    {
        $messages = [
            'created' => 'Record created successfully',
            'updated' => 'Record updated successfully',
            'deleted' => 'Record deleted successfully'
        ];
        
        return $messages[$action] ?? 'Operation completed successfully';
    }
}