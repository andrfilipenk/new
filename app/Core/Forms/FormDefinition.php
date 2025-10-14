<?php
namespace Core\Forms;

use Core\Forms\Fields\FieldInterface;
use Core\Forms\Fields\FieldCollection;

class FormDefinition
{
    /**
     * @var string Form name/identifier
     */
    private string $name;

    /**
     * @var string Form method (GET, POST, etc.)
     */
    private string $method = 'POST';

    /**
     * @var string|null Form action URL
     */
    private ?string $action = null;

    /**
     * @var FieldCollection Field collection
     */
    private FieldCollection $fields;

    /**
     * @var array Form-level validation rules
     */
    private array $validationRules = [];

    /**
     * @var array Security configuration
     */
    private array $securityConfig = [
        'csrf_enabled' => false,
        'csrf_field_name' => '_csrf_token',
        'sanitize_input' => true,
    ];

    /**
     * @var array Rendering configuration
     */
    private array $renderConfig = [
        'template' => 'form-bootstrap',
        'rendering' => 'bootstrap',
        'layout' => 'vertical',
        'show_labels' => true,
        'show_errors' => true,
        'show_help_text' => true,
    ];

    /**
     * @var array Form behavior configuration
     */
    private array $behaviorConfig = [
        'ajax_submit' => false,
        'ajax_validation' => false,
        'auto_focus' => true,
        'submit_on_enter' => true,
    ];

    /**
     * @var array Form attributes
     */
    private array $attributes = [];

    /**
     * @var array Form metadata
     */
    private array $metadata = [];

    /**
     * Create a new form definition
     * 
     * @param string $name Form name
     * @param array $config Form configuration
     */
    public function __construct(string $name, array $config = [])
    {
        $this->name = $name;
        $this->fields = new FieldCollection();
        
        // Apply configuration
        if (isset($config['method'])) {
            $this->setMethod($config['method']);
        }
        
        if (isset($config['action'])) {
            $this->setAction($config['action']);
        }
        
        if (isset($config['security'])) {
            $this->securityConfig = array_merge($this->securityConfig, $config['security']);
        }
        
        if (isset($config['rendering'])) {
            $this->renderConfig = array_merge($this->renderConfig, $config['rendering']);
        }
        
        if (isset($config['behavior'])) {
            $this->behaviorConfig = array_merge($this->behaviorConfig, $config['behavior']);
        }
        
        if (isset($config['attributes'])) {
            $this->setAttributes($config['attributes']);
        }
        
        if (isset($config['validationRules'])) {
            $this->validationRules = $config['validationRules'];
        }
        
        if (isset($config['metadata'])) {
            $this->metadata = $config['metadata'];
        }
    }

    /**
     * Get form name
     * 
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get form method
     * 
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Set form method
     * 
     * @param string $method HTTP method
     * @return self
     */
    public function setMethod(string $method): self
    {
        $this->method = strtoupper($method);
        return $this;
    }

    /**
     * Get form action
     * 
     * @return string|null
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * Set form action
     * 
     * @param string|null $action Action URL
     * @return self
     */
    public function setAction(?string $action): self
    {
        $this->action = $action;
        return $this;
    }

    /**
     * Get field collection
     * 
     * @return FieldCollection
     */
    public function getFields(): FieldCollection
    {
        return $this->fields;
    }

    /**
     * Add a field to the form
     * 
     * @param FieldInterface $field The field to add
     * @return self
     */
    public function addField(FieldInterface $field): self
    {
        $this->fields->add($field);
        return $this;
    }

    /**
     * Get a field by name
     * 
     * @param string $name Field name
     * @return FieldInterface|null
     */
    public function getField(string $name): ?FieldInterface
    {
        return $this->fields->get($name);
    }

    /**
     * Check if field exists
     * 
     * @param string $name Field name
     * @return bool
     */
    public function hasField(string $name): bool
    {
        return $this->fields->has($name);
    }

    /**
     * Remove a field
     * 
     * @param string $name Field name
     * @return self
     */
    public function removeField(string $name): self
    {
        $this->fields->remove($name);
        return $this;
    }

    /**
     * Get form-level validation rules
     * 
     * @return array
     */
    public function getValidationRules(): array
    {
        return $this->validationRules;
    }

    /**
     * Add a form-level validation rule
     * 
     * @param string $name Rule name
     * @param mixed $config Rule configuration
     * @return self
     */
    public function addValidationRule(string $name, mixed $config): self
    {
        $this->validationRules[$name] = $config;
        return $this;
    }

    /**
     * Get security configuration
     * 
     * @return array
     */
    public function getSecurityConfig(): array
    {
        return $this->securityConfig;
    }

    /**
     * Set security configuration
     * 
     * @param array $config Security config
     * @return self
     */
    public function setSecurityConfig(array $config): self
    {
        $this->securityConfig = array_merge($this->securityConfig, $config);
        return $this;
    }

    /**
     * Check if CSRF protection is enabled
     * 
     * @return bool
     */
    public function isCsrfEnabled(): bool
    {
        return $this->securityConfig['csrf_enabled'] ?? true;
    }

    /**
     * Enable or disable CSRF protection
     * 
     * @param bool $enabled Whether to enable CSRF
     * @return self
     */
    public function setCsrfEnabled(bool $enabled): self
    {
        $this->securityConfig['csrf_enabled'] = $enabled;
        return $this;
    }

    /**
     * Get rendering configuration
     * 
     * @return array
     */
    public function getRenderConfig(): array
    {
        return $this->renderConfig;
    }

    /**
     * Set rendering configuration
     * 
     * @param array $config Render config
     * @return self
     */
    public function setRenderConfig(array $config): self
    {
        $this->renderConfig = array_merge($this->renderConfig, $config);
        return $this;
    }

    /**
     * Get behavior configuration
     * 
     * @return array
     */
    public function getBehaviorConfig(): array
    {
        return $this->behaviorConfig;
    }

    /**
     * Set behavior configuration
     * 
     * @param array $config Behavior config
     * @return self
     */
    public function setBehaviorConfig(array $config): self
    {
        $this->behaviorConfig = array_merge($this->behaviorConfig, $config);
        return $this;
    }

    /**
     * Get form attributes
     * 
     * @return array
     */
    public function getAttributes(): array
    {
        $attributes = $this->attributes;
        
        // Add method and action
        $attributes['method'] = $this->method;
        if ($this->action) {
            $attributes['action'] = $this->action;
        }
        
        // Add default attributes
        if (!isset($attributes['id'])) {
            $attributes['id'] = 'form_' . $this->name;
        }
        
        if (!isset($attributes['name'])) {
            $attributes['name'] = $this->name;
        }
        
        // Add AJAX class if enabled
        if ($this->behaviorConfig['ajax_submit']) {
            $class = $attributes['class'] ?? '';
            $attributes['class'] = trim($class . ' ajax-form');
        }
        
        return $attributes;
    }

    /**
     * Set form attributes
     * 
     * @param array $attributes Attributes array
     * @return self
     */
    public function setAttributes(array $attributes): self
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * Set a single attribute
     * 
     * @param string $name Attribute name
     * @param mixed $value Attribute value
     * @return self
     */
    public function setAttribute(string $name, mixed $value): self
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * Get metadata
     * 
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Set metadata value
     * 
     * @param string $key Metadata key
     * @param mixed $value Metadata value
     * @return self
     */
    public function setMetadata(string $key, mixed $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * Get metadata value
     * 
     * @param string $key Metadata key
     * @param mixed $default Default value
     * @return mixed
     */
    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Convert to array representation
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'method' => $this->method,
            'action' => $this->action,
            'fields' => $this->fields->toArray(),
            'validationRules' => $this->validationRules,
            'security' => $this->securityConfig,
            'rendering' => $this->renderConfig,
            'behavior' => $this->behaviorConfig,
            'attributes' => $this->attributes,
            'metadata' => $this->metadata,
        ];
    }
}
