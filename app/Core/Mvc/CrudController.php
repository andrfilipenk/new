<?php
// app/Core/Mvc/CrudController.php
namespace Core\Mvc;

use Core\Http\Response;
use Core\Database\Model;
use Core\Validation\Validator;
use Exception;

/**
 * Advanced CRUD Controller with enterprise-level features
 * Following super-senior PHP practices with strict typing and separation of concerns
 */
abstract class CrudController extends Controller
{
    /** @var string The model class name */
    protected string $modelClass;
    
    /** @var string The base route prefix */
    protected string $routePrefix;
    
    /** @var array Validation rules for create/update */
    protected array $validationRules = [];
    
    /** @var array Fields allowed for mass assignment */
    protected array $fillable = [];
    
    /** @var array Relations to eager load */
    protected array $with = [];
    
    /** @var string The form class for this resource */
    protected string $formClass;
    
    /** @var string The service class for business logic */
    protected ?string $serviceClass = null;
    
    /** @var int Records per page for pagination */
    protected int $perPage = 15;
    
    /** @var bool Whether to support soft deletes */
    protected bool $softDeletes = false;

    public function __construct()
    {
        if (!$this->modelClass) {
            throw new Exception("Model class must be defined in " . static::class);
        }
        
        if (!$this->routePrefix) {
            $this->routePrefix = $this->generateRoutePrefix();
        }
    }

    /**
     * Display a listing of the resource
     */
    public function indexAction(): string|Response
    {
        try {
            $query = $this->getModelQuery();
            
            // Apply eager loading
            if (!empty($this->with)) {
                $query = $this->modelClass::with($this->with);
            }
            
            // Handle pagination
            $page = (int) $this->getRequest()->get('page', 1);
            $records = $this->paginate($query, $page);
            
            // Handle JSON API requests
            if ($this->isJsonRequest()) {
                return $this->jsonResponse([
                    'success' => true,
                    'data' => $records,
                    'meta' => $this->getPaginationMeta($records, $page)
                ]);
            }
            
            return $this->render('index', [
                'records' => $records,
                'pagination' => $this->getPaginationData($records, $page)
            ]);
            
        } catch (Exception $e) {
            return $this->handleError($e, 'Failed to fetch records');
        }
    }

    /**
     * Show the form for creating a new resource
     */
    public function createAction(): string|Response
    {
        if ($this->isJsonRequest()) {
            return $this->jsonResponse([
                'success' => true,
                'data' => [],
                'validation_rules' => $this->validationRules
            ]);
        }
        
        $form = $this->buildForm();
        
        return $this->render('form', [
            'form' => $form->render(),
            'record' => null,
            'action' => 'create'
        ]);
    }

    /**
     * Store a newly created resource
     */
    public function storeAction(): Response
    {
        try {
            $data = $this->getValidatedData();
            
            $record = $this->createRecord($data);
            
            if ($this->isJsonRequest()) {
                return $this->jsonResponse([
                    'success' => true,
                    'data' => $record->getData(),
                    'message' => $this->getSuccessMessage('created')
                ], Response::HTTP_CREATED);
            }
            
            $this->flashSuccess($this->getSuccessMessage('created'));
            return $this->redirect($this->routePrefix);
            
        } catch (Exception $e) {
            return $this->handleValidationError($e, 'create');
        }
    }

    /**
     * Display the specified resource
     */
    public function showAction(): string|Response
    {
        try {
            $id = $this->getRouteParam('id');
            $record = $this->findRecord($id);
            
            if ($this->isJsonRequest()) {
                return $this->jsonResponse([
                    'success' => true,
                    'data' => $record->getData()
                ]);
            }
            
            return $this->render('show', ['record' => $record]);
            
        } catch (Exception $e) {
            return $this->handleError($e, 'Record not found');
        }
    }

    /**
     * Show the form for editing the specified resource
     */
    public function editAction(): string|Response
    {
        try {
            $id = $this->getRouteParam('id');
            $record = $this->findRecord($id);
            
            if ($this->isJsonRequest()) {
                return $this->jsonResponse([
                    'success' => true,
                    'data' => $record->getData(),
                    'validation_rules' => $this->validationRules
                ]);
            }
            
            $form = $this->buildForm($record->getData());
            
            return $this->render('form', [
                'form' => $form->render(),
                'record' => $record,
                'action' => 'edit'
            ]);
            
        } catch (Exception $e) {
            return $this->handleError($e, 'Record not found');
        }
    }

    /**
     * Update the specified resource
     */
    public function updateAction(): Response
    {
        try {
            $id = $this->getRouteParam('id');
            $record = $this->findRecord($id);
            $data = $this->getValidatedData();
            
            $this->updateRecord($record, $data);
            
            if ($this->isJsonRequest()) {
                return $this->jsonResponse([
                    'success' => true,
                    'data' => $record->getData(),
                    'message' => $this->getSuccessMessage('updated')
                ]);
            }
            
            $this->flashSuccess($this->getSuccessMessage('updated'));
            return $this->redirect($this->routePrefix);
            
        } catch (Exception $e) {
            return $this->handleValidationError($e, 'edit');
        }
    }

    /**
     * Remove the specified resource
     */
    public function destroyAction(): Response
    {
        try {
            $id = $this->getRouteParam('id');
            $record = $this->findRecord($id);
            
            $this->deleteRecord($record);
            
            if ($this->isJsonRequest()) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => $this->getSuccessMessage('deleted')
                ]);
            }
            
            $this->flashSuccess($this->getSuccessMessage('deleted'));
            return $this->redirect($this->routePrefix);
            
        } catch (Exception $e) {
            return $this->handleError($e, 'Failed to delete record');
        }
    }

    // Protected helper methods
    
    protected function getModelQuery()
    {
        return $this->modelClass::query();
    }
    
    protected function findRecord($id): Model
    {
        $query = !empty($this->with) ? 
            $this->modelClass::with($this->with) : 
            $this->modelClass::query();
            
        $record = $query->where($this->getModelInstance()->getKeyName(), $id)->first();
        
        if (!$record) {
            throw new Exception("Record not found with ID: {$id}");
        }
        
        return $this->modelClass::newFromBuilder($record);
    }
    
    protected function createRecord(array $data): Model
    {
        if ($this->serviceClass) {
            $service = $this->getDI()->get($this->serviceClass);
            return $service->create($data);
        }
        
        $record = new $this->modelClass($data);
        
        if (!$record->save()) {
            throw new Exception("Failed to create record");
        }
        
        return $record;
    }
    
    protected function updateRecord(Model $record, array $data): Model
    {
        if ($this->serviceClass) {
            $service = $this->getDI()->get($this->serviceClass);
            return $service->update($record, $data);
        }
        
        $record->fill($data);
        
        if (!$record->save()) {
            throw new Exception("Failed to update record");
        }
        
        return $record;
    }
    
    protected function deleteRecord(Model $record): bool
    {
        if ($this->serviceClass) {
            $service = $this->getDI()->get($this->serviceClass);
            return $service->delete($record);
        }
        
        if (method_exists($record, 'delete')) {
            return $record->delete();
        }
        
        throw new Exception("Delete method not implemented");
    }
    
    protected function getValidatedData(): array
    {
        $data = $this->getRequest()->all();
        
        if (!empty($this->validationRules)) {
            $validator = new Validator($data, $this->validationRules);
            
            if (!$validator->passes()) {
                $this->throwValidationException($validator->errors());
            }
        }
        
        // Filter only fillable fields
        if (!empty($this->fillable)) {
            $data = array_intersect_key($data, array_flip($this->fillable));
        }
        
        return $data;
    }
    
    protected function buildForm(array $values = [])
    {
        if (!$this->formClass) {
            throw new Exception("Form class not defined");
        }
        
        return $this->formClass::build($values);
    }
    
    protected function paginate($query, int $page): array
    {
        $offset = ($page - 1) * $this->perPage;
        return $query->limit($this->perPage)->offset($offset)->get();
    }
    
    protected function isJsonRequest(): bool
    {
        return $this->getRequest()->isAjax() || 
               $this->getRequest()->header('Accept') === 'application/json' ||
               $this->getRequest()->get('format') === 'json';
    }
    
    protected function jsonResponse(array $data, int $statusCode = 200): Response
    {
        return (new Response())->setStatusCode($statusCode)->json($data);
    }
    
    protected function getRouteParam(string $key): mixed
    {
        return $this->getDI()->get('dispatcher')->getParam($key);
    }
    
    protected function generateRoutePrefix(): string
    {
        $parts = explode('\\', static::class);
        $controller = end($parts);
        $module = strtolower($parts[1] ?? 'base');
        
        return "/{$module}/" . strtolower(str_replace('Controller', '', $controller));
    }
    
    protected function getModelInstance(): Model
    {
        return new $this->modelClass();
    }
    
    protected function getSuccessMessage(string $action): string
    {
        $resource = $this->getResourceName();
        return ucfirst($resource) . " {$action} successfully.";
    }
    
    protected function getResourceName(): string
    {
        return strtolower(str_replace('Controller', '', 
            array_slice(explode('\\', static::class), -1)[0]
        ));
    }
    
    protected function handleError(Exception $e, string $message): Response
    {
        if ($this->isJsonRequest()) {
            return $this->jsonResponse([
                'success' => false,
                'error' => $message,
                'details' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        }
        
        $this->flashError($message);
        return $this->redirect($this->routePrefix);
    }
    
    protected function handleValidationError(Exception $e, string $action): Response
    {
        if ($this->isJsonRequest()) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        
        $this->flashError('Please correct the errors below.');
        
        // Redirect back to form with errors
        return $action === 'create' ? 
            $this->redirect($this->routePrefix . '/create') :
            $this->redirect($this->routePrefix . '/edit/' . $this->getRouteParam('id'));
    }
    
    protected function throwValidationException(array $errors): void
    {
        throw new Exception('Validation failed: ' . json_encode($errors));
    }
    
    protected function getPaginationMeta(array $records, int $page): array
    {
        return [
            'current_page' => $page,
            'per_page' => $this->perPage,
            'total' => count($records)
        ];
    }
    
    protected function getPaginationData(array $records, int $page): array
    {
        return $this->getPaginationMeta($records, $page);
    }
}