<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

class KuberDock_AutoLoader {
    /**
     * @var array
     */
    private static $_dirMapping = array();


    /**
     *
     */
    public function __construct()
    {
        spl_autoload_register(array($this, 'loader'), true, true);
    }

    /**
     * Default class loader
     *
     * @param $className
     */
    private function loader($className)
    {
        $className = ltrim($className, '\\');

        if($pos = strrpos($className, '\\')) {
            $nameSpace = str_replace('\\', DS, substr($className, 0, $pos));
            $className = substr($className, $pos+1);

            $filePath = KUBERDOCK_CLASS_DIR . DS . $nameSpace . DS . $className . '.php';
        } elseif($dirs = self::loadDirList()) {
            foreach($dirs as $dir) {
                $filePath = $dir. DS . $className . '.php';
                if(file_exists($filePath)) {
                    break;
                }
            }
        } else {
            $filePath = KUBERDOCK_CLASS_DIR . DS . $className . '.php';
        }

        if(file_exists($filePath)) {
            include_once $filePath;
        }
    }

    /**
     * @param null $dir
     * @return array
     */
    public static function getDirList($dir = null)
    {
        if(empty($dir)) {
            $result[] = KUBERDOCK_CLASS_DIR;
        } else {
            $result = glob($dir . DS . '*', GLOB_ONLYDIR);
        }

        foreach($result as $v) {
            if($dir = self::getDirList($v)) {
                $result = array_merge($result, $dir);
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public static function loadDirList()
    {
        if(empty(self::$_dirMapping)) {
            self::$_dirMapping = self::getDirList();
            return self::$_dirMapping;
        } else {
            return self::$_dirMapping;
        }
    }
} 