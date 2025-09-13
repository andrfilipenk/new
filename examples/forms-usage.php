<?php


// Basic Usage:

// Create form using builder
$form = (new Core\Forms\Builder())
    ->addTextField('name', 'Full Name', ['placeholder' => 'Enter your name'])
    ->addEmailField('email', 'Email Address', ['required' => true])
    ->addSelect('country', ['us' => 'USA', 'ca' => 'Canada', 'uk' => 'UK'], 'Country')
    ->addCheckbox('newsletter', 'Subscribe to newsletter')
    ->setValues(['name' => 'John Doe', 'newsletter' => true])
    ->build();

// Render the form
echo $form->render();






// Advanced Usage with Custom Templates:

// Create form with custom templates
$form = (new Core\Forms\Builder())
    ->addTextField('username', 'Username')
    ->addPasswordField('password', 'Password')
    ->setTemplate('bootstrap') // Use bootstrap template
    ->setFieldTemplate('bootstrap') // Use bootstrap field template
    ->build();

echo $form->render();





// Manual Form Creation:

// Create form manually
$form = new Core\Forms\Form();
$form->addField('name', 'text', [
    'label' => 'Full Name',
    'attributes' => [
        'placeholder' => 'Enter your name',
        'class' => 'form-control'
    ]
]);
$form->addField('email', 'email', [
    'label' => 'Email Address',
    'required' => true,
    'attributes' => [
        'class' => 'form-control'
    ]
]);

// Set values from request or database
$form->setValues([
    'name' => 'Jane Smith',
    'email' => 'jane@example.com'
]);

// Render individual fields
echo $form->renderField('name');
echo $form->renderField('email');

// Or render entire form
echo $form->render();





// Integration with DI Container:

// In your bootstrap file
$di->set('formBuilder', function() {
    return new Core\Forms\Builder();
});

// In your controller
$form = $this->di->get('formBuilder')
    ->addTextField('title', 'Article Title')
    ->addTextarea('content', 'Article Content')
    ->build();