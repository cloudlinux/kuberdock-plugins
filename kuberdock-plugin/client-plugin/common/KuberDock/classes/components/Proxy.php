<?php

namespace Kuberdock\classes\components;

use Kuberdock\classes\models\Pod;
use Kuberdock\classes\exceptions\CException;
use Kuberdock\classes\models\PredefinedApp;
use Kuberdock\classes\Base;

/**
 * Class Proxy
 */
class Proxy {
    /**
     *
     */
    const HTACCESS_START_SECTION = '# DO NOT REMOVE. KUBERDOCK PLUGIN CONFIGURATION BEGIN';
    /**
     *
     */
    const HTACCESS_END_SECTION = '# DO NOT REMOVE. KUBERDOCK PLUGIN CONFIGURATION END';
    /**
     *
     */
    const HTACCESS_REWRITE_RULE_TEMPLATE = 'RewriteRule %s(.*)$ %s://%s:%s/$1 [L,P]';
    /**
     *
     */
    const HTACCESS_REWRITE_REGEXP = '/RewriteEngine (On|Off)/i';
    /**
     *
     */
    const ROOT_DIR = 'root';

    /**
     * @param string $podName
     * @param string $dir
     * @param string $domain
     * @param int $port
     * @throws CException
     */
    public function addProxy($podName, $dir, $domain, $port)
    {
        $htaccessPath = $this->getHtaccessPathByDomain($domain);
        $rule = $this->getRewriteRule($dir, '%s', $port);

        $pod = Base::model()->getPanel()->getCommand()->describePod($podName);

        if (!isset($pod['podIP'])) {
            return;
        }

        $this->addRule($htaccessPath, sprintf($rule, $pod['podIP']));
    }

    /**
     * @param string $path
     * @param string $rule
     */
    public function addRule($path, $rule)
    {
        $fileManager = Base::model()->getStaticPanel()->getFileManager();
        $htaccess = $fileManager->getFileContent($path);

        if (!preg_match($this->getHtaccessRegexp(), $htaccess)) {
            $data = array("\n", self::HTACCESS_START_SECTION, $rule, self::HTACCESS_END_SECTION);
            $htaccess .= implode("\n", $data);
        } else {
            $htaccess = preg_replace_callback($this->getHtaccessRegexp(), function($e) use ($rule) {
                $rules = array_filter(explode("\n", $e[1]));
                if (!in_array(trim($rule), $rules)) {
                    $rules[] = $rule;
                }
                return implode("\n", array_merge(array(self::HTACCESS_START_SECTION), $rules, array(self::HTACCESS_END_SECTION)));
            }, $htaccess);
        }

        if (!preg_match(self::HTACCESS_REWRITE_REGEXP, $htaccess, $match)) {
            $htaccess = "RewriteEngine On\n" . $htaccess;
        } elseif ($match[1] == 'Off') {
            $htaccess = str_ireplace('RewriteEngine Off', 'RewriteEngine On', $htaccess);
        }

        if (!$fileManager->file_exists($path)) {
            $fileManager->putFileContent($path, $htaccess);
            $fileManager->chown($path);
            $fileManager->chmod($path, 0664);
        } else {
            $fileManager->chown($path);
            $fileManager->chmod($path, 0664);
            file_put_contents($path, $htaccess);
        }
    }

    /**
     * @param string $path
     * @param string $rule
     * @return bool
     */
    public function removeRule($path, $rule)
    {
        $fileManager = Base::model()->getStaticPanel()->getFileManager();

        if (!$fileManager->file_exists($path)) {
            return false;
        }

        $htaccess = $fileManager->getFileContent($path);

        if (preg_match($this->getHtaccessRegexp(), $htaccess)) {
            $htaccess = preg_replace_callback($this->getHtaccessRegexp(), function($e) use ($rule) {
                $rules = array_filter(explode("\n", $e[1]), function($r) use ($rule) {
                    if(trim($r) != trim($rule)) {
                        return $r;
                    }
                });
                return implode("\n", array_merge(array(self::HTACCESS_START_SECTION), $rules, array(self::HTACCESS_END_SECTION)));
            }, $htaccess);
        }

        if ($fileManager->file_exists($path)) {
            $fileManager->chown($path);
            $fileManager->chmod($path, 0664);
            file_put_contents($path, $htaccess);
        }
    }

    /**
     * @param Pod $pod
     * @throws CException
     */
    public function addRuleToPod(Pod $pod)
    {
        try {
            $data = $this->getProxyData($pod);
            $this->addProxy($pod->name, $data['directory'], $data['domain'], $data['port']);
        } catch (\Exception $e) {
            // pass
        }
    }

    /**
     * @param Pod $pod
     * @throws CException
     */
    public function removeRuleFromPod(Pod $pod)
    {
        try {
            $data = $this->getProxyData($pod);
            $htaccessPath = $this->getHtaccessPathByDomain($data['domain']);
            $rule = $this->getRewriteRule($data['directory'], '%s', $data['port']);
            $this->removeRule($htaccessPath, sprintf($rule, $pod->podIP));
        } catch (\Exception $e) {
            //pass
        }
    }

    /**
     * @param string $dir
     * @param string $ip
     * @param int $port
     * @param string $scheme
     * @return string
     */
    public function getRewriteRule($dir, $ip, $port, $scheme = 'http')
    {
        $dir = ($dir == self::ROOT_DIR) ? '' : $dir . '/';
        return sprintf(self::HTACCESS_REWRITE_RULE_TEMPLATE, $dir, $scheme, $ip, $port);
    }

    /**
     * @return string
     */
    private function getHtaccessRegexp()
    {
        return '/' . self::HTACCESS_START_SECTION . '([\w\W]+)' . self::HTACCESS_END_SECTION . '/m';
    }

    /**
     * @param string $domain
     * @return string
     * @throws CException
     */
    private function getHtaccessPathByDomain($domain)
    {
        return $this->getDocRootByDomain($domain) . DS . '.htaccess';

    }

    /**
     * @param string $domain
     * @return string
     * @throws CException
     */
    private function getDocRootByDomain($domain)
    {
        $panel = Base::model()->getStaticPanel();
        $docRoot = $panel->getCommonCommand()->getUserDomainDocroot($domain, $panel->getUser());

        if (file_exists($docRoot)) {
            return $docRoot;
        }

        throw new CException(sprintf('Can not find document root by domain %s', $domain));
    }

    /**
     * @param Pod $pod
     * @return array (
            'directory' => proxy dir,
            'domain' => proxy domain,
            'port' => proxy port
     * )
     * @throws CException
     */
    private function getProxyData(Pod $pod)
    {
        $template = PredefinedApp::byId($pod->template_id)->getTemplate();

        foreach ($template->getProxy() as $dir => $proxy) {
            if (isset($proxy['domain'], $proxy['container'])) {
                $container = $pod->getContainerByName($proxy['container']);
                if ($ports = $container['ports']) {
                    foreach ($ports as $port) {
                        $port = isset($port['hostPort']) ? $port['hostPort'] : $port['containerPort'];
                        return [
                            'directory' => $dir,
                            'domain' => $template->getDomain(),
                            'port' => $port
                        ];
                    }
                }
            }
        }

        return ['directory' => '', 'domain' => '', 'port' => ''];
    }
}