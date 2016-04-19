<?php

namespace Kuberdock\classes\models;

use Kuberdock\classes\panels\KuberDock_cPanel;
use Kuberdock\classes\exceptions\CException;
use Kuberdock\classes\extensions\yaml\Spyc;
use Kuberdock\classes\KuberDock_View;

/**
 * Created by PhpStorm.
 * User: user
 * Date: 12/27/15
 * Time: 9:47 AM
 */
class Template
{
    /**
     * Parsed template
     * @var array
     */
    public $data = array();
    /**
     * @var KuberDock_cPanel
     */
    private $panel;
    /**
     * @var int
     */
    private $id;

    /**
     * @param KuberDock_cPanel $panel
     */
    public function __construct(KuberDock_cPanel $panel)
    {
        $this->panel = $panel;
    }

    /**
     * @param $id
     * @return array
     * @throws CException
     */
    public function getById($id)
    {
        $this->id = $id;
        $template = $this->panel->getAdminApi()->getTemplate($id);

        if(!$template) {
            throw new CException('Template not exists');
        }

        $this->data = Spyc::YAMLLoadString($template['template']);

        return $this->data;
    }

    /**
     * @param $id
     * @return array
     * @throws CException
     */
    public function getByPath($path, $id)
    {
        $this->id = $id;
        $template = file_get_contents($path);

        if(!$template) {
            throw new CException('Template not exists');
        }

        $this->data = Spyc::YAMLLoadString($template);

        return $this->data;
    }

    /**
     * @param $path
     * @return array|bool
     */
    public static function getTemplateByPath($path)
    {
        if(!file_exists($path)) {
            return false;
        }

        return Spyc::YAMLLoadString(file_get_contents($path));
    }

    /**
     * @return array
     * @throws CException
     */
    public function getAll()
    {
        $data = array();
        $templates = $this->panel->getAdminApi()->getTemplates('cpanel');

        foreach($templates as &$row) {
            $row['template'] = Spyc::YAMLLoadString($row['template']);

            if($this->isPackageExists($row['template']) && (!isset($row['kuberdock']) || !$row['kuberdock'])) {
                $data[] = $row;
            }
        }

        return $data;
    }


    /**
     * @return mixed
     * @throws CException
     */
    public function getKDSection()
    {
        if(isset($this->data['kuberdock']) && is_array($this->data['kuberdock'])) {
            return $this->data['kuberdock'];
        } else {
            throw new CException('"kuberdock" section not exist in the template');
        }
    }

    /**
     * @return bool
     */
    public function getPackageId()
    {
        $defaults = $this->panel->billing->getDefaults();
        $defaultPackageId = isset($defaults['packageId']) ? $defaults['packageId'] : 0;

        return isset($this->data['kuberdock']['packageID']) ?
            $this->data['kuberdock']['packageID'] : $defaultPackageId;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return isset($this->data['kuberdock']['name']) ?
            $this->data['kuberdock']['name'] : 'Undefined';
    }

    /**
     * @return string
     */
    public function getPodName()
    {
        return isset($this->data['metadata']['name']) ?
            $this->data['metadata']['name'] : 'Undefined';
    }

    /**
     * @return array
     */
    public function getProxy()
    {
        return isset($this->data['kuberdock']['proxy']) ?
            $this->data['kuberdock']['proxy'] : array();
    }

    /**
     * @return mixed
     */
    public function getContainers()
    {
        return isset($this->data['spec']['template']['spec']['containers']) ?
            $this->data['spec']['template']['spec']['containers'] : $this->data['spec']['containers'];
    }

    /**
     * @return mixed
     */
    public function getVolumes()
    {
        return isset($this->data['spec']['template']['spec']['volumes']) ?
            $this->data['spec']['template']['spec']['volumes'] : $this->data['spec']['volumes'];
    }

    /**
     * @param $planId
     * @return mixed
     * @throws CException
     */
    public function getKubeTypeId($planId)
    {
        $defaults = $this->panel->billing->getDefaults();
        $plan = $this->getPlan($planId);
        // TODO: Add few pod support
        $pod = $plan['pods'][0];

        return (int) preg_match('/default:(\d+)/', $pod['kubeType'] , $match) ? $match[1] :
            (isset($pod['kubeType']) ? $pod['kubeType'] : $defaults['kubeType']);
    }

    /**
     * @return mixed
     */
    public function getPlans()
    {
        $kdSection = $this->getKDSection();
        if(isset($kdSection['appPackages']) && is_array($kdSection['appPackages'])) {
            return $kdSection['appPackages'];
        } else {
            return array();
        }
    }

    /**
     * @param $id
     * @return mixed
     * @throws CException
     */
    public function getPlan($id)
    {
        $plans = $this->getPlans();

        if(!isset($plans[$id])) {
            throw new CException(sprintf('Plan with key "%s" does not exist', $id));
        }

        return $plans[$id];
    }

    /**
     * @return bool
     */
    public function isPackageExists()
    {
        $templatePackageId = $this->getPackageId();

        if(!$templatePackageId) {
            return true;
        }

        $service = $this->panel->billing->getService();

        return !($service && $templatePackageId != $service['kuber_product_id']);
    }

    /**
     * @param $containers
     * @return $this
     */
    public function setContainers($containers)
    {
        if(isset($this->data['spec']['template']['spec']['containers'])) {
            $this->data['spec']['template']['spec']['containers'] = $containers;
        } else {
            $this->data['spec']['containers'] = $containers;
        }

        return $this;
    }

    /**
     * @param $volumes
     * @return $this
     */
    public function setVolumes($volumes)
    {
        if(isset($this->data['spec']['template']['spec']['volumes'])) {
            $this->data['spec']['template']['spec']['volumes'] = $volumes;
        } else {
            $this->data['spec']['volumes'] = $volumes;
        }

        return $this;
    }

    /**
     * @param int $id
     */
    public function setKubeType($id)
    {
        $this->data['kuberdock']['kube_type'] = $id;
    }

    /**
     * @param int|null $id
     * @throws CException
     */
    public function setPackageId($id = null)
    {
        $defaults = $this->panel->billing->getDefaults();

        if($id) {
            $this->data['kuberdock']['packageID'] = $id;
        } elseif(!isset($this->data['kuberdock']['packageID'])) {
            $defaultPackageId = isset($defaults['packageId']) ? $defaults['packageId'] : 0;
            $this->data['kuberdock']['packageID'] = $defaultPackageId;
        }
    }

    /**
     * @param string $description
     */
    public function addPackagePostDescription($description)
    {
        $this->data['kuberdock']['postDescription'] .= "\n" . $description;
    }
}
