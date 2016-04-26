<?php

namespace Kuberdock\classes\plesk\forms;

class Defaults extends \pm_Form_Simple
{
    public function init()
    {
        $this->setAttrib('id', 'form_defaults');
        $this->addElement('select', 'packageId', array(
            'label' => 'Default package',
        ));

        $this->addElement('select', 'kubeType', array(
            'label' => 'Default Kube Type',
        ));

        $this->addControlButtons(array(
            'sendTitle' => 'Save',
            'cancelHidden' => true,
            'hideLegend' => true,
        ));
    }
}