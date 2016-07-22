<?php

class Modules_KuberDock_CustomButtons extends pm_Hook_CustomButtons
{
    public function getButtons()
    {
        return array(
            array(
                'place' => array(
                    self::PLACE_HOSTING_PANEL_NAVIGATION,
                    self::PLACE_COMMON,
                ),
                'title' => 'KuberDock',
                'description' => 'KuberDock applications',
                'link' => pm_Context::getBaseUrl() . 'index.php/index',
                'icon' => pm_Context::getBaseUrl() . 'assets/images/default_transparent.png',
                'newWindow' => false,
            ),
        );
    }
}