<?php

namespace Kuberdock\classes\plesk\lists;


use Kuberdock\classes\DI;

class App extends \pm_View_List_Simple
{
    protected $_pageable = false;
    protected $_defaultSortDirection = self::SORT_DIR_DOWN;
    protected $_defaultSortField = 'id';

    public function __construct($view, $request, $options = [])
    {
        parent::__construct($view, $request, $options);

        $this->setColumns([
            'id' => array(
                'title' => '#',
                'sortable' => false,
            ),
            'name' => array(
                'title' => 'Plesk app name',
                'sortable' => false,
            ),
            'actions' => array(
                'title' => 'Actions',
                'sortable' => false,
                'noEscape' => true,
            ),
        ]);

        /** @var \Kuberdock\classes\models\App $model */
        $model = DI::get('\Kuberdock\classes\models\App')->setPanel('Plesk');

        $this->setData($model->getAll());
    }
}