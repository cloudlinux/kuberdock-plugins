<?php

namespace Kuberdock\classes\api;

use Kuberdock\classes\models\Pod;

class KuberDock extends API
{
    protected function get_pods($args) {
        $pod = new Pod();

        if ($args) {
            $podName = $args[0];
            $pod = $pod->loadByName($podName);

            $data = $pod->asArray();
        } else {
            $pods = $pod->getPods();

            $data = array_map(function($pod) {
                return $pod->asArray();
            }, $pods);
        }

        return $data;
    }

    protected function get_pods_search($args) {

    }

    protected function get_templates($args) {

    }
}