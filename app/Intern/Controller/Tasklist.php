<?php
namespace Module\Base\Controller;

class TaskList extends Task {

    protected $_viewMode;
    protected $_viewModes = [
        null        => 'Default',
        'columns'   => 'Columns'
    ];

    protected function getViewModes()
    {
        $modes = [];
        $activeMode = $this->getRequest()->get('view');
        foreach ($this->_viewModes as $key => $label) {
            $modes[] = [
                'active' => ($activeMode === $key),
                'label'  => $label,
                'url'    => $this->url()
            ];
        }
    }
}