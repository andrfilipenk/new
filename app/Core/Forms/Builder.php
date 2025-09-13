<?php

namespace Core\Forms;

use BadMethodCallException;

class Builder
{
    protected $form;

    public function __construct()
    {
        $this->form = new Form();
    }

    /**
     * Dynamically adds form fields.
     *
     * Catches calls to methods like `addTextField('name', 'Label')` or
     * `addSelect('country', ['us' => 'USA'], 'Country')`.
     *
     * @param string $name The name of the method being called.
     * @param array $arguments An enumerated array containing the parameters passed to the method.
     * @return self
     */
    public function __call(string $name, array $arguments): self
    {
        if (strpos($name, 'add') === 0) {
            $type = strtolower(preg_replace('/(Field|Group)$/', '', substr($name, 3)));
            
            $fieldName = $arguments[0];
            $fieldOptions = [];

            if (in_array($type, ['select', 'radio'])) {
                // Signature: (string $name, array $options, string $label = null, array $attributes = [])
                $fieldOptions['options'] = $arguments[1] ?? [];
                $fieldOptions['label'] = $arguments[2] ?? null;
                $fieldOptions['attributes'] = $arguments[3] ?? [];
            } else {
                // Signature: (string $name, string $label = null, array $attributes = [])
                $fieldOptions['label'] = $arguments[1] ?? null;
                $fieldOptions['attributes'] = $arguments[2] ?? [];
            }

            $this->form->addField($fieldName, $type, $fieldOptions);
            return $this;
        }

        throw new BadMethodCallException(sprintf('Method %s::%s does not exist.', static::class, $name));
    }

    public function setValues(array $values): self
    {
        $this->form->setValues($values);
        return $this;
    }

    public function setTemplate(string $template): self
    {
        $this->form->setTemplate($template);
        return $this;
    }

    public function setFieldTemplate(string $fieldTemplate): self
    {
        $this->form->setFieldTemplate($fieldTemplate);
        return $this;
    }

    public function build(): FormInterface
    {
        return $this->form;
    }

    public function render(): string
    {
        return $this->form->render();
    }
}

/* Example usage:
$form = (new Builder())
    ->addTextField('username', 'Username', ['class' => 'form-control'])
    ->addPasswordField('password', 'Password', ['class' => 'form-control'])
    ->addSelect('country', ['us' => 'USA', 'ca' => 'Canada'], 'Country', ['class' => 'form-select'])
    ->setValues(['username' => 'johndoe', 'country' => 'ca'])
    ->setTemplate('<form>{fields}<button type="submit">Submit</button></form>')
    ->setFieldTemplate('<div class="mb-3">{label}{field}{error}</div>')
    ->build();


echo $form->render();
echo $form;

write example for form with fields: name, beginDate, endDate, users_id
<!--
$form = (new Builder())
    ->addTextField('name', 'Name', ['class' => 'form-control'])
    ->addDateField('beginDate', 'Begin Date', ['class' => 'form-control'])
    ->addDateField('endDate', 'End Date', ['class' => 'form-control'])
    ->addSelect('users_id', [1 => 'User 1', 2 => 'User 2'], 'User', ['class' => 'form-select'])
    ->setValues(['name' => 'Project X', 'beginDate' => '2024-01-01', 'endDate' => '2024-12-31', 'users_id' => 2])
    ->setTemplate('<form>{fields}<button type="submit">Submit</button></form>')
    ->setFieldTemplate('<div class="mb-3">{label}{field}{error}</div>')
    ->build();
echo $form->render();
echo $form;
*/