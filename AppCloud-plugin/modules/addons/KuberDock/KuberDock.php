<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */
require __DIR__ . '/../../servers/KuberDock/init.php';

function KuberDock_config()
{
    return array (
        'name' => 'KuberDock addon',
        'description' => '',
        'version' => '1.1.0',
        'author' => '<a href="http://www.cloudlinux.com" targer="_blank">CloudLinux</a>',
        'fields' => array(),
    );
}

function KuberDock_activate()
{
    try {
        \models\addon\Addon::model()->activate();

        return array(
            'status' =>  'success',
            'description' => 'Addon module activated',
        );
    } catch(Exception $e) {
        return array(
            'status' =>  'error',
            'description' => 'Addon module FAILED to activate: ' . $e->getMessage(),
        );
    }
}

function KuberDock_deactivate()
{
    try {
        \models\addon\Addon::model()->deactivate();

        return array(
            'status' =>  'success',
            'description' => 'Addon module deactivated',
        );
    } catch(Exception $e) {
        return array(
            'status' =>  'error',
            'description' => 'Addon module FAILED to deactivate: ' . $e->getMessage(),
        );
    }
}

function KuberDock_output($vars)
{
    $controller = \components\Controller::model();
    $controller->baseUrl = $vars['modulelink'];
    $controller->defaultController = 'Addon';
    $controller->run();
}