<?php

namespace Kuberdock\classes\models;


use Spyc;
use Kuberdock\classes\panels\KuberDock_Panel;
use Kuberdock\classes\Base;
use Kuberdock\classes\exceptions\CException;
use Kuberdock\classes\Tools;

class Template
{
    /**
     * Parsed template
     * @var array
     */
    public $data = array();
    /**
     * @var KuberDock_Panel
     */
    private $panel;
    /**
     * @var int
     */
    private $id;

    /**
     * @param KuberDock_Panel $panel
     */
    public function __construct(KuberDock_Panel $panel)
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
        $template = $this->panel->getAdminApi()->getTemplate($id, true);

        if(!$template) {
            throw new CException('Template not exists');
        }

        $this->data = Spyc::YAMLLoadString($template['template']);
        $this->data['kuberdock']['name'] = $template['name'];
        $this->data['plans'] = $template['plans'];

        $this->setDefaults();

        return $this->data;
    }

    public function fillData($data)
    {
        $filled = $this->panel->getAdminApi()->fillTemplate($data);
        $this->data = Tools::parseYaml($filled);
    }

    /**
    -     * @param $id
    -     * @return array
    -     * @throws CException
    -     */
    public function getByPath($path, $id)
    {
        $this->id = $id;
        $template = Base::model()->getStaticPanel()->getFileManager()->getFileContent($path);

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

        return Spyc::YAMLLoadString(Base::model()->getStaticPanel()->getFileManager()->getFileContent($path));
    }

    /**
     * @return array
     * @throws CException
     */
    public function getClientTemplates()
    {
        $data = array();
        $templates = $this->panel->getAdminApi()->getClientTemplates();

        foreach($templates as &$row) {
            $row['template'] = Spyc::YAMLLoadString($row['template']);

            if($this->isPackageExists() && (!isset($row['kuberdock']) || !$row['kuberdock'])) {
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
     * @return array
     */
    public function getVolumes()
    {
        if (isset($this->data['spec']['template']['spec']['volumes'])) {
            return $this->data['spec']['template']['spec']['volumes'];
        }

        if (isset($this->data['spec']['volumes'])) {
            return $this->data['spec']['volumes'];
        }

        return array();
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

    public function getDomain()
    {
        return $this->panel->domain;
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
        $this->data['kuberdock']['appPackage']['kubeType'] = $id;
    }

    public function setBaseDomain($baseDomain)
    {
        $this->data['kuberdock']['appPackage']['baseDomain'] = $baseDomain;
    }

    /**
     * @param string $name
     */
    public function setPlanName($name)
    {
        $this->data['kuberdock']['appPackage']['name'] = $name;
    }

    /**
     * @param string $description
     */
    public function addPackagePostDescription($description)
    {
        $this->data['kuberdock']['postDescription'] .= "\n" . $description;
    }

    /**
     * @param int $planId
     * @return array
     */
    public function getPodStructureFromPlan($planId)
    {
        $plan = $this->getPlan($planId);
        $attributes = array(
            'plan' => $plan['name'],
        );

        foreach ($plan['pods'] as $row) {
            $attributes['kube_type'] = $row['kubeType'];
            $attributes['containers'] = $row['containers'];
            if (isset($plan['publicIP']) && $plan['publicIP']) {
                $attributes['containers'][0]['ports'][0]['isPublic'] = $plan['publicIP'];
            }

            if (isset($row['persistentDisks'])) {
                foreach ($row['persistentDisks'] as $k => $pd) {
                    $attributes['volumes'][$k]['name'] = $pd['name'];
                    $attributes['volumes'][$k]['persistentDisk']['pdSize'] = $pd['pdSize'];
                }
            }
        }

        return $attributes;
    }

    /**
     *
     */
    private function setDefaults()
    {
        $defaults = $this->panel->billing->getDefaults();

        if (!isset($this->data['kuberdock']['packageID'])) {
            $this->data['kuberdock']['packageID'] = isset($defaults['packageId']) ? $defaults['packageId'] : 0;
        }

        foreach ($this->data['kuberdock']['appPackages'] as &$appPackage) {
            foreach ($appPackage['pods'] as &$pod) {
                if (!isset ($pod['kubeType'])) {
                    $pod['kubeType'] = isset($defaults['kubeType']) ? $defaults['kubeType'] : 0;
                }
            }
        }
    }
}
