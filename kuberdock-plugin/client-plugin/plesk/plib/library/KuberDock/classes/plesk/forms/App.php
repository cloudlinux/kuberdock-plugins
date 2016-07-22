<?php

namespace Kuberdock\classes\plesk\forms;

class App extends \pm_Form_Simple
{
    public function init()
    {
        $this->setAttrib('id', 'form_app');

        $elem = new \Zend_Form_Element_File('yaml_file');
        $elem->setLabel('Upload YAML');
        $elem->setAttrib('data-name', 'Upload YAML');
        $this->addElement($elem);

        $this->addElement('hidden', 'id');

        $this->addElement('text', 'name', array(
            'label' => 'App name',
            'name' => 'name',
            'data-validation' => 'custom',
            'data-validation-regexp' => '^([a-zA-Z_-\s]+)$',
            'required' => true,
            'validators' => [
                ['NotEmpty', true],
            ],
        ));

        $this->addElement('textarea', 'template', array(
            'label' => 'Yaml',
            'name' => 'template',
            'id' => 'template',
            'class' => 'code-editor',
            'required' => true,
            'validators' => [
                ['NotEmpty', true],
            ],
        ));

        $this->addControlButtons(array(
            'sendTitle' => 'Add application',
            'cancelLink' => \pm_Context::getActionUrl('admin', 'index'),
            'hideLegend' => true,
            'presubmitHandler' => 'return validate_form();',
        ));
    }
}