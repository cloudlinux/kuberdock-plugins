<?php

namespace Kuberdock\classes\plesk\forms;

class KubeCli extends \pm_Form_Simple
{
    public function init()
    {
        $this->setAttrib('id', 'form_kubecli');
        $this->addElement('text', 'url', array(
            'label' => 'Kuberdock master url',
        ));

        $this->addElement('text', 'user', array(
            'label' => 'User',
        ));

        $this->addElement('password', 'password', array(
            'label' => 'Password',
            'renderPassword' => true,
            'autocomplete' => 'off',
        ));

        $this->addElement('text', 'registry', array(
            'label' => 'Registry',
        ));

        $this->addControlButtons(array(
            'sendTitle' => 'Save',
            'cancelHidden' => true,
            'hideLegend' => true,
        ));
    }
}