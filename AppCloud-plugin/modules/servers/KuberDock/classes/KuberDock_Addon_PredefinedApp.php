<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

class KuberDock_Addon_PredefinedApp extends CL_Model {
    /**
     *
     */
    const KUBERDOCK_PRODUCT_ID_FIELD = 'kd_pid';
    /**
     *
     */
    const KUBERDOCK_YAML_FIELD = 'yaml';

    /**
     *
     */
    public function setTableName()
    {
        $this->tableName = 'KuberDock_preapps';
    }

    /**
     * @return $this|bool
     */
    public function loadBySessionId()
    {
        $data = $this->loadByAttributes(array(
            'session_id' => session_id(),
        ));

        if(!$data) {
            return false;
        }

        return $this->setAttributes(current($data));
    }

    /**
     * @param int $serviceId
     * @return array
     * @throws Exception
     */
    public function create($serviceId)
    {
        $api = KuberDock_Hosting::model()->loadById($serviceId)->getApi();
        $response = $api->createPodFromYaml(unserialize($this->data));

        return $response->getData();
    }

    /**
     * @param string $podId
     * @param int $serviceId
     * @throws Exception
     */
    public function start($podId, $serviceId)
    {
        $api = KuberDock_Hosting::model()->loadById($serviceId)->getApi();
        $api->startPod($podId);
    }

    /**
     * Class loader
     *
     * @param string $className
     * @return $this
     */
    public static function model($className = __CLASS__)
    {
        if(isset(self::$_models[$className])) {
            return self::$_models[$className];
        } else {
            self::$_models[$className] = new $className;
            return self::$_models[$className];
        }
    }
} 