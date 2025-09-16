<?php
use Core\Forms\Builder;

// Example 1: Basic form with different field types
$basicForm = (new Builder())
    ->addTextField('username', 'Username', ['required' => true])
    ->addEmailField('email', 'Email Address', ['placeholder' => 'user@example.com'])
    ->addPasswordField('password', 'Password', ['minlength' => 8])
    ->addNumberField('age', 'Age', ['min' => 18, 'max' => 99])
    ->addCheckboxField('remember', 'Remember me')
    ->build()
    ->render();

// Example 2: Form with date range (beginDate & endDate)
$dateForm = (new Builder())
    ->addDateField('beginDate', 'Start Date', [
        'required' => true,
        'min' => date('Y-m-d') // Today's date
    ])
    ->addDateField('endDate', 'End Date', [
        'required' => true,
        'min' => date('Y-m-d', strtotime('+1 day'))
    ])
    ->addSelectField('reportType', [
        'daily' => 'Daily Report',
        'weekly' => 'Weekly Report',
        'monthly' => 'Monthly Report'
    ], 'Report Type', ['required' => true])
    ->setValues([
        'beginDate' => date('Y-m-d'),
        'endDate' => date('Y-m-d', strtotime('+7 days'))
    ])
    ->build()
    ->render();

// Example 3: Form with radio buttons and textarea
$detailedForm = (new Builder())
    ->addTextField('name', 'Full Name')
    ->addRadioField('gender', [
        'male' => 'Male',
        'female' => 'Female',
        'other' => 'Other'
    ], 'Gender', ['required' => true])
    ->addTextareaField('bio', 'Biography', [
        'rows' => 4,
        'cols' => 50,
        'placeholder' => 'Tell us about yourself...'
    ])
    ->build()
    ->render();

// Example 4: Form with custom template
$customForm = (new Builder())
    ->addEmailField('email', 'Email')
    ->addPasswordField('password', 'Password')
    ->setTemplate('
        <form class="custom-form" method="post">
            {fields}
            <div class="actions">
                <button type="submit">Login</button>
                <button type="reset">Clear</button>
            </div>
        </form>
    ')
    ->setFieldTemplate('
        <div class="input-group">
            {label}
            {field}
            <span class="helper-text"></span>
        </div>
    ')
    ->build()
    ->render();

// Example 5: Form with datetime fields
$eventForm = (new Builder())
    ->addTextField('eventName', 'Event Name')
    ->addDatetimeField('startTime', 'Start Time')
    ->addDatetimeField('endTime', 'End Time')
    ->setValues([
        'startTime' => date('Y-m-d\TH:i', strtotime('+1 day')),
        'endTime' => date('Y-m-d\TH:i', strtotime('+2 days'))
    ])
    ->build()
    ->render();







    // Example 1: Basic form with custom field positions

    $form = (new Builder())
    ->addTextField('username', 'Username')
    ->addEmailField('email', 'Email')
    ->addDateField('beginDate', 'Start Date')
    ->addDateField('endDate', 'End Date')
    ->setTemplate('
        <form method="post" class="custom-form">
            <div class="row">
                <div class="col-md-6">
                    {field_username}
                </div>
                <div class="col-md-6">
                    {field_email}
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    {field_beginDate}
                </div>
                <div class="col-md-6">
                    {field_endDate}
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </form>
    ')
    ->build();

echo $form->render();



// Example 2: Complex form layout with mixed placement
$form = (new Builder())
    ->addTextField('firstName', 'First Name')
    ->addTextField('lastName', 'Last Name')
    ->addDateField('beginDate', 'Start Date')
    ->addDateField('endDate', 'End Date')
    ->addSelectField('department', [
        'hr' => 'Human Resources',
        'it' => 'Information Technology',
        'sales' => 'Sales'
    ], 'Department')
    ->addTextareaField('comments', 'Comments')
    ->setTemplate('
        <form method="post" class="complex-form">
            <h3>Personal Information</h3>
            <div class="personal-info">
                {field_firstName}
                {field_lastName}
            </div>
            
            <h3>Date Range</h3>
            <div class="date-range">
                <div class="date-from">{field_beginDate}</div>
                <div class="date-to">{field_endDate}</div>
            </div>
            
            <h3>Additional Information</h3>
            <div class="additional-info">
                {field_department}
                {field_comments}
            </div>
            
            <div class="form-footer">
                <button type="submit">Save</button>
                <button type="reset">Cancel</button>
            </div>
        </form>
    ')
    ->build();

echo $form->render();



// Example 3: Table-based form layout
$form = (new Builder())
    ->addTextField('productName', 'Product Name')
    ->addNumberField('quantity', 'Quantity')
    ->addDateField('beginDate', 'Start Date')
    ->addDateField('endDate', 'End Date')
    ->addSelectField('status', [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'pending' => 'Pending'
    ], 'Status')
    ->setTemplate('
        <form method="post">
            <table class="form-table">
                <tr>
                    <td>Product Information:</td>
                    <td>{field_productName}</td>
                </tr>
                <tr>
                    <td>Quantity:</td>
                    <td>{field_quantity}</td>
                </tr>
                <tr>
                    <td>Date Range:</td>
                    <td>
                        From: {field_beginDate} To: {field_endDate}
                    </td>
                </tr>
                <tr>
                    <td>Status:</td>
                    <td>{field_status}</td>
                </tr>
            </table>
            <div class="form-actions">
                {fields} <!-- This will catch any fields not explicitly placed -->
                <button type="submit">Save Product</button>
            </div>
        </form>
    ')
    ->build();

echo $form->render();




// Example 4: Mixed approach with some fields in specific positions and others grouped
$form = (new Builder())
    ->addTextField('title', 'Title')
    ->addTextareaField('description', 'Description')
    ->addDateField('beginDate', 'Start Date')
    ->addDateField('endDate', 'End Date')
    ->addCheckboxField('active', 'Active')
    ->addSelectField('category', [
        'news' => 'News',
        'event' => 'Event',
        'update' => 'Update'
    ], 'Category')
    ->setTemplate('
        <form method="post">
            <div class="header-fields">
                {field_title}
                {field_category}
            </div>
            
            <div class="date-range-section">
                <h3>Date Range</h3>
                <div class="dates">
                    {field_beginDate}
                    {field_endDate}
                </div>
            </div>
            
            <div class="content-section">
                {field_description}
            </div>
            
            <div class="footer-fields">
                {fields} <!-- This will render the active checkbox -->
                <button type="submit">Publish</button>
            </div>
        </form>
    ')
    ->build();

echo $form->render();
