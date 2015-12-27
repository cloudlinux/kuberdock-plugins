<?php

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
    const ROOT_DIR = 'root';

    /**
     * @var WHMCSApi
     */
    private $api;

    /**
     * @param WHMCSApi|null $api
     */
    public function __construct(WHMCSApi $api = null)
    {
        $this->api = $api;
    }

    /**
     * @param string $podName
     * @param string $dir
     * @param string $domain
     * @param int $port
     */
    public function addProxy($podName, $dir, $domain, $port)
    {
        $htaccessPath = $this->getHtaccessPathByDomain($domain);
        $rule = $this->getRewriteRule($dir, '%s', $port);
        $command = sprintf('%s --pod_name="%s" --path=%s --rule=\'%s\' -c 1',
            $this->getProxyCommand(), $podName, $htaccessPath, $rule);
        exec($command);
    }

    /**
     * @param string $path
     * @param string $rule
     */
    public function addRule($path, $rule)
    {
        if(!file_exists($path)) {
            file_put_contents($path, '');
        }

        $htaccess = file_get_contents($path);

        if(!preg_match($this->getHtaccessRegexp(), $htaccess)) {
            $data = array("\n", self::HTACCESS_START_SECTION, $rule, self::HTACCESS_END_SECTION);
            $htaccess .= implode("\n", $data);
        } else {
            $htaccess = preg_replace_callback($this->getHtaccessRegexp(), function($e) use ($rule) {
                $rules = array_filter(explode("\n", $e[1]));
                if(!in_array(trim($rule), $rules)) {
                    $rules[] = $rule;
                }
                return implode("\n", array_merge(array(self::HTACCESS_START_SECTION), $rules, array(self::HTACCESS_END_SECTION)));
            }, $htaccess);
        }

        file_put_contents($path, $htaccess);
    }

    /**
     * @param string $path
     * @param string $rule
     * @return bool
     */
    public function removeRule($path, $rule)
    {
        if(!file_exists($path)) {
            return false;
        }

        $htaccess = file_get_contents($path);

        if(preg_match($this->getHtaccessRegexp(), $htaccess)) {
            $htaccess = preg_replace_callback($this->getHtaccessRegexp(), function($e) use ($rule) {
                $rules = array_filter(explode("\n", $e[1]), function($r) use ($rule) {
                    if(trim($r) != trim($rule)) {
                        return $r;
                    }
                });
                return implode("\n", array_merge(array(self::HTACCESS_START_SECTION), $rules, array(self::HTACCESS_END_SECTION)));
            }, $htaccess);
        }

        file_put_contents($path, $htaccess);
    }

    /**
     * @param $dir
     * @param $domain
     * @return bool
     */
    public function removeRuleByDirName($dir, $domain)
    {
        $path = $this->getHtaccessPathByDomain($domain);
        if(!file_exists($path)) {
            return false;
        }

        $htaccess = file_get_contents($path);

        if(preg_match($this->getHtaccessRegexp(), $htaccess)) {
            $htaccess = preg_replace_callback($this->getHtaccessRegexp(), function($e) use ($dir) {
                $rules = array_filter(explode("\n", $e[1]), function($r) use ($dir) {
                    $dir = ($dir == self::ROOT_DIR) ? '' : $dir . '/';
                    if(strpos($r, $dir . '(.*)') === false) {
                        return $r;
                    }
                });
                return implode("\n", array_merge(array(self::HTACCESS_START_SECTION), $rules, array(self::HTACCESS_END_SECTION)));
            }, $htaccess);
        }

        file_put_contents($path, $htaccess);
    }

    /**
     * @param Pod $pod
     * @throws CException
     */
    public function addRuleToPod(Pod $pod)
    {
        $app = new PredefinedApp($pod->template_id);
        $template = $app->getTemplateByPodName($pod->name);

        if(isset($template['kuberdock']['proxy'])) {
            foreach($template['kuberdock']['proxy'] as $dir => $proxy) {
                if(isset($proxy['domain']) && isset($proxy['container'])) {
                    $container = $app->getContainerData($pod->name, $proxy['container']);
                    if ($ports = $container['ports']) {
                        foreach ($ports as $port) {
                            $port = isset($port['hostPort']) ? $port['hostPort'] : $port['containerPort'];
                            $this->addProxy($pod->name, $dir, $proxy['domain'], $port);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Pod $pod
     * @throws CException
     */
    public function removeRuleFromPod(Pod $pod)
    {
        $app = new PredefinedApp($pod->template_id);
        $template = $app->getTemplateByPodName($pod->name);

        if(isset($template['kuberdock']['proxy'])) {
            foreach($template['kuberdock']['proxy'] as $dir => $proxy) {
                if(isset($proxy['domain']) && isset($proxy['container'])) {
                    $this->removeRuleByDirName($dir, $proxy['domain']);
                }
            }
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
        if(!$this->api) {
            throw new CException('Cannot be used from cli');
        }

        $domains = $this->api->getUserDomains();

        if($domains['main_domain']['domain'] == $domain) {
            return $domains['main_domain']['documentroot'];
        } else {
            foreach($domains['sub_domains'] as $row) {
                if($row['domain'] == $domain) {
                    return $row['documentroot'];
                }
            }
        }

        $home = getenv('HOME') . DS . 'public_html';
        if(file_exists($home)) {
            return $home;
        }

        throw new CException(sprintf('Can not find document root by domain %s', $domain));
    }

    /**
     * @return string
     */
    private function getProxyCommand()
    {
        return KUBERDOCK_BIN_DIR . DS . 'addProxy.php';
    }
}