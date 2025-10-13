<?php
/**
 * FormBuilder Class
 * 
 * Provides a fluent interface for building forms declaratively.
 * Simplifies form creation with method chaining.
 * 
 * @package Core\Forms
 * @since 2.0.0
 */

namespace Core\Forms;

use Core\Forms\Fields\FieldFactory;
use Core\Forms\Fields\FieldInterface;

class FormBuilder
{
    /**
     * @var FormDefinition Form definition being built
     */
    private FormDefinition $formDefinition;

    /**
     * Create a new form builder
     * 
     * @param string $name Form name
     * @param array $config Initial configuration
     */
    private function __construct(string $name, array $config = [])
    {
        $this->formDefinition = new FormDefinition($name, $config);
    }

    /**
     * Create a new form builder instance
     * 
     * @param string $name Form name
     * @param array $config Initial configuration
     * @return self
     */
    public static function create(string $name, array $config = []): self
    {
        return new self($name, $config);
    }

    /**
     * Set form action URL
     * 
     * @param string $action Action URL
     * @return self
     */
    public function setAction(string $action): self
    {
        $this->formDefinition->setAction($action);
        return $this;
    }

    /**
     * Set form method
     * 
     * @param string $method HTTP method (GET, POST, etc.)
     * @return self
     */
    public function setMethod(string $method): self
    {
        $this->formDefinition->setMethod($method);
        return $this;
    }

    /**
     * Add a field to the form
     * 
     * @param FieldInterface $field Field instance
     * @return self
     */
    public function addField(FieldInterface $field): self
    {
        $this->formDefinition->addField($field);
        return $this;
    }

    /**
     * Add a field using field factory
     * 
     * @param string $name Field name
     * @param string $type Field type
     * @param array $config Field configuration
     * @return self
     */
    public function field(string $name, string $type, array $config = []): self
    {
        $field = FieldFactory::create($name, $type, $config);
        $this->addField($field);
        return $this;
    }

    /**
     * Add a text field
     * 
     * @param string $name Field name
     * @param array $config Field configuration
     * @return self
     */
    public function text(string $name, array $config = []): self
    {
        return $this->addField(FieldFactory::text($name, $config));
    }

    /**
     * Add an email field
     * 
     * @param string $name Field name
     * @param array $config Field configuration
     * @return self
     */
    public function email(string $name, array $config = []): self
    {
        return $this->addField(FieldFactory::email($name, $config));
    }

    /**
     * Add a password field
     * 
     * @param string $name Field name
     * @param array $config Field configuration
     * @return self
     */
    public function password(string $name, array $config = []): self
    {
        return $this->addField(FieldFactory::password($name, $config));
    }

    /**
     * Add a number field
     * 
     * @param string $name Field name
     * @param array $config Field configuration
     * @return self
     */
    public function number(string $name, array $config = []): self
    {
        return $this->addField(FieldFactory::number($name, $config));
    }

    /**
     * Add a select field
     * 
     * @param string $name Field name
     * @param array $options Field options
     * @param array $config Field configuration
     * @return self
     */
    public function select(string $name, array $options, array $config = []): self
    {
        return $this->addField(FieldFactory::select($name, $options, $config));
    }

    /**
     * Add a textarea field
     * 
     * @param string $name Field name
     * @param array $config Field configuration
     * @return self
     */
    public function textarea(string $name, array $config = []): self
    {
        return $this->addField(FieldFactory::textarea($name, $config));
    }

    /**
     * Add a hidden field
     * 
     * @param string $name Field name
     * @param mixed $value Field value
     * @return self
     */
    public function hidden(string $name, mixed $value = null): self
    {
        return $this->addField(FieldFactory::hidden($name, $value));
    }

    /**
     * Add a date field
     * 
     * @param string $name Field name
     * @param array $config Field configuration
     * @return self
     */
    public function date(string $name, array $config = []): self
    {
        $config['type'] = 'date';
        return $this->addField(FieldFactory::create($name, 'date', $config));
    }

    /**
     * Add a tel field
     * 
     * @param string $name Field name
     * @param array $config Field configuration
     * @return self
     */
    public function tel(string $name, array $config = []): self
    {
        return $this->addField(FieldFactory::tel($name, $config));
    }

    /**
     * Add multiple fields from configuration array
     * 
     * @param array $fieldsConfig Fields configuration
     * @return self
     */
    public function fields(array $fieldsConfig): self
    {
        foreach ($fieldsConfig as $name => $config) {
            $type = $config['type'] ?? 'text';
            $this->field($name, $type, $config);
        }
        return $this;
    }

    /**
     * Enable or disable CSRF protection
     * 
     * @param bool $enabled CSRF enabled flag
     * @return self
     */
    public function csrf(bool $enabled = true): self
    {
        $this->formDefinition->setCsrfEnabled($enabled);
        return $this;
    }

    /**
     * Add form-level validation rule
     * 
     * @param string $name Rule name
     * @param mixed $config Rule configuration
     * @return self
     */
    public function addValidationRule(string $name, mixed $config): self
    {
        $this->formDefinition->addValidationRule($name, $config);
        return $this;
    }

    /**
     * Set form attribute
     * 
     * @param string $name Attribute name
     * @param mixed $value Attribute value
     * @return self
     */
    public function setAttribute(string $name, mixed $value): self
    {
        $this->formDefinition->setAttribute($name, $value);
        return $this;
    }

    /**
     * Set form attributes
     * 
     * @param array $attributes Attributes array
     * @return self
     */
    public function setAttributes(array $attributes): self
    {
        $this->formDefinition->setAttributes($attributes);
        return $this;
    }

    /**
     * Set form CSS class
     * 
     * @param string $class CSS class
     * @return self
     */
    public function addClass(string $class): self
    {
        $attributes = $this->formDefinition->getAttributes();
        $existingClass = $attributes['class'] ?? '';
        $newClass = trim($existingClass . ' ' . $class);
        $this->formDefinition->setAttribute('class', $newClass);
        return $this;
    }

    /**
     * Set form ID
     * 
     * @param string $id Form ID
     * @return self
     */
    public function setId(string $id): self
    {
        $this->formDefinition->setAttribute('id', $id);
        return $this;
    }

    /**
     * Set security configuration
     * 
     * @param array $config Security config
     * @return self
     */
    public function security(array $config): self
    {
        $this->formDefinition->setSecurityConfig($config);
        return $this;
    }

    /**
     * Set rendering configuration
     * 
     * @param array $config Render config
     * @return self
     */
    public function rendering(array $config): self
    {
        $this->formDefinition->setRenderConfig($config);
        return $this;
    }

    /**
     * Set behavior configuration
     * 
     * @param array $config Behavior config
     * @return self
     */
    public function behavior(array $config): self
    {
        $this->formDefinition->setBehaviorConfig($config);
        return $this;
    }

    /**
     * Enable AJAX submission
     * 
     * @param bool $enabled AJAX enabled flag
     * @return self
     */
    public function ajax(bool $enabled = true): self
    {
        return $this->behavior(['ajax_submit' => $enabled]);
    }

    /**
     * Set form metadata
     * 
     * @param string $key Metadata key
     * @param mixed $value Metadata value
     * @return self
     */
    public function setMetadata(string $key, mixed $value): self
    {
        $this->formDefinition->setMetadata($key, $value);
        return $this;
    }

    /**
     * Build and return the form definition
     * 
     * @return FormDefinition
     */
    public function build(): FormDefinition
    {
        return $this->formDefinition;
    }

    /**
     * Build and return form manager
     * 
     * @return FormManager
     */
    public function buildManager(): FormManager
    {
        return new FormManager($this->formDefinition);
    }

    /**
     * Get the form definition (without building)
     * 
     * @return FormDefinition
     */
    public function getFormDefinition(): FormDefinition
    {
        return $this->formDefinition;
    }

    /**
     * Quick form builder with common patterns
     * 
     * @param string $name Form name
     * @param callable $callback Builder callback
     * @return FormDefinition
     */
    public static function quick(string $name, callable $callback): FormDefinition
    {
        $builder = self::create($name);
        $callback($builder);
        return $builder->build();
    }

    /**
     * Build a login form
     * 
     * @param string $name Form name
     * @param string $action Form action
     * @return FormDefinition
     */
    public static function login(string $name = 'login', string $action = '/login'): FormDefinition
    {
        return self::create($name)
            ->setAction($action)
            ->setMethod('POST')
            ->email('email', [
                'label' => 'Email',
                'required' => true,
                'placeholder' => 'Enter your email'
            ])
            ->password('password', [
                'label' => 'Password',
                'required' => true,
                'placeholder' => 'Enter your password'
            ])
            ->csrf()
            ->build();
    }

    /**
     * Build a registration form
     * 
     * @param string $name Form name
     * @param string $action Form action
     * @return FormDefinition
     */
    public static function registration(string $name = 'registration', string $action = '/register'): FormDefinition
    {
        $builder = self::create($name)
            ->setAction($action)
            ->setMethod('POST')
            ->text('username', [
                'label' => 'Username',
                'required' => true,
                'validationRules' => [
                    'minlength' => 3,
                    'maxlength' => 20,
                    'alphanumeric' => true
                ]
            ])
            ->email('email', [
                'label' => 'Email',
                'required' => true
            ])
            ->password('password', [
                'label' => 'Password',
                'required' => true,
                'validationRules' => ['minlength' => 8]
            ])
            ->password('password_confirm', [
                'label' => 'Confirm Password',
                'required' => true
            ])
            ->csrf()
            ->addValidationRule('fieldsMatch', [
                'fields' => ['password', 'password_confirm'],
                'message' => 'Passwords must match'
            ]);
        
        return $builder->build();
    }

    /**
     * Build a contact form
     * 
     * @param string $name Form name
     * @param string $action Form action
     * @return FormDefinition
     */
    public static function contact(string $name = 'contact', string $action = '/contact'): FormDefinition
    {
        return self::create($name)
            ->setAction($action)
            ->setMethod('POST')
            ->text('name', [
                'label' => 'Name',
                'required' => true
            ])
            ->email('email', [
                'label' => 'Email',
                'required' => true
            ])
            ->text('subject', [
                'label' => 'Subject',
                'required' => true
            ])
            ->textarea('message', [
                'label' => 'Message',
                'required' => true,
                'rows' => 5
            ])
            ->csrf()
            ->build();
    }
}
