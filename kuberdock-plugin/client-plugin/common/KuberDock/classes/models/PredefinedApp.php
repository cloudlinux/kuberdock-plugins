<?php
/**
 * @project cpanel-whmcs
 * @author: Ruslan Rakhmanberdiev
 */

namespace Kuberdock\classes\models;

use Kuberdock\classes\Base;
use Kuberdock\classes\panels\KuberDock_cPanel;
use Kuberdock\classes\KcliCommand;
use Kuberdock\classes\exceptions\CException;
use Kuberdock\classes\extensions\yaml\Spyc;
use Kuberdock\classes\KuberDock_Controller;
use Kuberdock\classes\Tools;

class PredefinedApp {
    /**
     *
     */
    const TEMPLATE_REGEXP = '/\$(?<variable>\w+)\|default:(?<default>[[:alnum:]\w\W\s]+)\|(?<description>[[:alnum:]()-_\s]+)\$/';
    /**
     *
     */
    const VARIABLE_REGEXP = '/\$(?<variable>\w+)\$/';
    /**
     *
     */
    const SPECIAL_VARIABLE_REGEXP = '/\%(?<variable>\w+)\%/';

    /**
     * @var int
     */
    private $templateId;
    /**
     * @var Template
     */
    private $template;
    /**
     * @var array
     */
    private $variables = array();

    /**
     * @var KuberDock_CPanel
     */
    private $panel;
    /**
     * @var KcliCommand
     */
    private $command;
    /**
     * @var Pod
     */
    private $pod;
    /**
     * @var array
     */
    private $_data = array(
        'containers' => array(),
    );

    /**
     * @param int $templateId
     */
    public function __construct($templateId = null)
    {
        $this->templateId = $templateId;
        $this->init();
    }

    public static function byId($templateId)
    {
        return new PredefinedApp($templateId);
    }

    /**
     * @param $name
     * @return \ArrayObject|mixed
     */
    public function __get($name)
    {
        $methodName = 'get'.ucfirst($name);

        if(method_exists($this, $methodName)) {
            $rm = new \ReflectionMethod($this, $methodName);
            return $rm->invoke($this);
        } elseif(isset($this->_data[$name])) {
            if(is_array($this->_data[$name])) {
                return new \ArrayObject($this->_data[$name]);
            } else {
                return $this->_data[$name];
            }
        }
    }

    /**
     * @param $name
     * @param $value
     * @return mixed
     */
    public function __set($name, $value)
    {
        $methodName = 'set'.ucfirst($name);

        if(method_exists($this, $methodName)) {
            $rm = new \ReflectionMethod($this, $methodName);
            return $rm->invoke($this, $value);
        } else {
            $this->_data[$name] = $value;
        }
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->_data[$name]);
    }

    /**
     *
     */
    public function init()
    {
        $this->pod = new Pod();
        $this->panel = $this->pod->getPanel();
        $this->command = $this->panel->getCommand();
        if($this->templateId) {
            $this->template = new Template($this->panel);
            try {
                $this->template->getById($this->templateId);
            } catch (\Exception $e) {
                // if global template is deleted, take local (user's)
                $path = PredefinedApp::getAppPathByTemplateId($this->templateId);
                $this->template->getByPath($path, $this->templateId);
            }
        }
    }

    /**
     * @return KuberDock_CPanel
     */
    public function getPanel()
    {
        return $this->panel;
    }

    /**
     * @return Pod
     */
    public function getPod()
    {
        return $this->pod;
    }

    /**
     * @return array
     */
    public function getVariables()
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($this->template->data));

        foreach($iterator as $row) {
            $path = array();
            foreach(range(0, $iterator->getDepth()) as $depth) {
                $path[] = $iterator->getSubIterator($depth)->key();
            }

            $sPath = implode('.', $path);
            $variable = $this->parseTemplateString($row, $sPath);
            $this->variables = array_merge($this->variables, $variable);

            // Numeric env value to string
            if(stripos($sPath, '.env') !== false && is_numeric($row)) {
                $r = &$this->template->data;
                foreach(explode('.', $sPath) as $v) {
                    $r = &$r[$v];
                }
                $r = "{$row}";
            }
        }

        return $this->variables;
    }

    /**
     * @param string $value
     * @param string $path
     * @return array
     */
    public function parseTemplateString($value, $path = '')
    {
        $data = array();

        if(preg_match_all(self::TEMPLATE_REGEXP, $value, $match)) {
            foreach($match['variable'] as $k => $row) {
                $type = $this->getType($row, $match['default'][$k]);
                $data[$row] = array(
                    'replace' => $match[0][$k],
                    'defaultValue' => $this->getDefault($match['default'][$k]),
                    'type' => $type,
                    'description' => $this->getDescription($match['description'][$k]),
                    'path' => $path,
                    'data' => $this->getVariableData($type),
                );
            }
        }

        return $data;
    }

    /**
     * @param array $data
     * @return array
     * @throws CException
     */
    public function createApp($data = array())
    {
        $this->template->fillData($data);

        // Create order with kuberdock product
        $this->pod->packageId = $this->template->getPackageId();
        $pod = $this->pod->createProduct();
        $this->command = $pod->getCommand();

        $fileManager = Base::model()->getStaticPanel()->getFileManager();
        $fileManager->putFileContent($this->getAppPath(), Spyc::YAMLDump($this->template->data));

        try{
            $response = $this->command->createPodFromYaml($this->getAppPath());
        } catch (CException $e) {
            throw new CException(preg_replace('/^kube_type:\s/i', '', $e->getMessage())); // AC-3003
        }

        $fileManager->putFileContent($this->getAppPath($this->template->getPodName()), Spyc::YAMLDump($this->template->data));

        $fileManager->chmod($this->getAppPath(), 0640);
        $fileManager->chmod($this->getAppPath($this->template->getPodName()), 0640);

        return $response;
    }

    public function getPostInstallPostDescription($data)
    {
        if (!isset($data['postDescription'])) {
            return '';
        }

        $this->template->data['kuberdock']['postDescription'] = $data['postDescription'];

        $this->setPostInstallVariables($data);

        return $this->template->data['kuberdock']['postDescription'];
    }

    /**
     * @param $variable string
     * @param $controller KuberDock_Controller
     * @throws CException
     */
    public function renderVariable($variable, $controller)
    {
        $allowedTypes = array(
            'autogen',
            'kube_type',
            'kube_count',
            'input',
            'user_domain_list'
        );
        $data = $this->variables[$variable];

        if(!in_array($data['type'], $allowedTypes)) {
            throw new CException('Undefined variable type: ' . $data['type']);
        }

        $controller->renderPartial('fields/' . $data['type'], array(
            'variable' => $variable,
            'data' => $data
        ));
    }

    /**
     * @return string
     * @throws CException
     */
    public function getPackageId()
    {
        $package = Base::model()->getPanel()->billing->getPackage();

        if($package) {
            return $package['id'];
        }

        return $this->template->getPackageId();
    }

    /**
     * @return array
     */
    public function getPods()
    {
        if (!$this->panel->billing->getService()) {
            return array();
        }

        $pods = $this->command->getPods();

        $existingPods = array_map(function($e) {
            $pod = $this->command->describePod($e['name']);
            if (isset($pod['template_id']) && $pod['template_id'] == $this->templateId) {
                return $pod;
            }
        }, $pods);

        return array_values(array_filter($existingPods));
    }

    /**
     * @return int
     */
    public function getTemplateId()
    {
        return $this->templateId;
    }

    /**
     * @return Template
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param string $podName
     * @param string $containerName
     * @return mixed
     * @throws CException
     */
    public function getContainerData($podName, $containerName)
    {
        $pod = $this->pod->loadByName($podName);

        return $pod->getContainerByName($containerName);
    }

    /**
     * @param $value
     * @return string
     */
    private function getDefault($value)
    {
        $value = strtolower($value);

        switch($value) {
            case 'autogen':
                return Tools::generatePassword();
            default:
                return $value;
        }
    }

    /**
     * @param string $var
     * @param string|int $default
     * @return string
     */
    private function getType($var, $default)
    {
        if($default == 'autogen') {
            return $default;
        } elseif(stripos($var, 'KUBE_COUNT') !== false || stripos($var, 'KUBES') !== false) {
            return 'kube_count';
        } elseif(in_array($var, array('KUBE_TYPE', 'KUBETYPE'))) {
            return 'kube_type';
        } elseif(stripos($default, 'USER_DOMAIN_LIST') !== false) {
            return 'user_domain_list';
        } else {
            return 'input';
        }
    }

    /**
     * @param $value
     * @return mixed
     */
    private function getDescription($value)
    {
        return $value;
    }

    /**
     * @param string $type
     * @return array|string
     */
    private function getVariableData($type)
    {
        switch($type) {
            case 'user_domain_list':
                return $this->panel->getUserDomains();
            default:
                return '';
        }
    }

    /**
     * @param array $data
     * @param string $path
     * @param string $replace
     * @param string $value
     * @return mixed
     */
    private function setByPath(&$data, $path, $replace, $value)
    {
        $temp = &$data;

        foreach(explode('.', $path) as $key) {
            $temp = &$temp[$key];
        }

        $temp = str_replace($replace, $value, $temp);

        if(is_numeric($temp))
            $temp = (int) $temp;

        return $data;
    }

    /**
     * @param string|null $name
     * @return string
     */
    private function getAppPath($name = null)
    {
        return $this->getAppPathByTemplateId($this->templateId, $name);
    }

    /**
     * @param $templateId
     * @param string|null $name
     * @return string
     */
    private static function getAppPathByTemplateId($templateId, $name = null)
    {
        $path = array('.kuberdock_pre_apps', 'kuberdock_'. $templateId);
        $panel = \Kuberdock\classes\Base::model()->getStaticPanel();
        $appDir = $panel->getHomeDir();

        foreach($path as $row) {
            $appDir .= DS . $row;

            if (!file_exists($appDir)) {
                $panel->getFileManager()->mkdir($appDir, 0770);
            }
        }

        return $appDir. DS . ($name ? $name : 'app.yaml');
    }

    /**
     * @param string $string
     * @param array $data
     * @param string $pattern
     * @return mixed
     */
    private function replaceTemplateVariable($string, $data, $pattern = self::VARIABLE_REGEXP)
    {
        $variables = $this->variables;
        return preg_replace_callback($pattern, function($matches) use ($variables, $data) {
            if(isset($variables[$matches['variable']])) {
                if(isset($data[$matches['variable']])) {
                    return $data[$matches['variable']];
                } elseif(isset($variables[$matches['variable']]['value'])) {
                    return $variables[$matches['variable']]['value'];
                }

            } else {
                return $matches[0];
            }
        }, $string);
    }

    /**
     * @param array $data
     */
    private function setPostInstallVariables($data)
    {
        $variables = array();

        $publicIp = $this->getIpForPostDescription($data);

        $variables['PUBLIC_ADDRESS'] = $publicIp;
        $this->variables['PUBLIC_ADDRESS'] = $publicIp;

        array_walk_recursive($this->template->data, function(&$e) use ($variables) {
            // bug with returned type of value (preg_replace_callback)
            if(preg_match(self::SPECIAL_VARIABLE_REGEXP, $e)) {
                $e = $this->replaceTemplateVariable($e, $variables, self::SPECIAL_VARIABLE_REGEXP);
            }
        });
    }

    private function getIpForPostDescription($data)
    {
        if (isset($data['public_ip']) && $data['public_ip']!=='true') {
            return $data['public_ip'];
        }

        if (isset($data['public_aws']) && $data['public_aws']!=='Unknown') {
            return $data['public_aws'];
        }

        if (isset($data['domain'])) {
            return $data['domain'];
        }

        return '"Public IP address not set"';
    }
}
