<?php
/**
 * @project cpanel-whmcs
 * @author: Ruslan Rakhmanberdiev
 */

namespace Kuberdock\classes\exceptions;

use Kuberdock\classes\Base;
use \Kuberdock\classes\KuberDock_View;

class CException extends \Exception {
    /**
     *
     */
    const LOG_FILE = '/var/log/kuberdock-plugin.log';

    /**
     * @param string $message
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct($message, $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function __toString() {
        $view = new KuberDock_View();

        return $view->render('errors/default', array(
            'message' => $this->getParsedMessage(),
        ), false);
    }

    /**
     * @return string
     * @throws CException
     */
    public function getJSON()
    {
        $view = new KuberDock_View();

        return json_encode(array(
            'error' => true,
            'message' => $view->renderPartial('errors/default', array(
                'message' => $this->getParsedMessage(),
            ), false),
        ));
    }
    /**
     * @return string
     */
    public function getParsedMessage()
    {
        return str_replace(array(
            'Invalid IP',
        ), array(
            'IP is not allowed for WHMCS API connections: '
        ), $this->message);
    }

    /**
     * Not used
     * @param \Exception $exception
     */
    public static function log(\Exception $exception) {
        $filePath = substr($exception->getFile(), stripos($exception->getFile(), 'KuberDock'));
        $date = new \DateTime();
        $trace = $exception->getTrace();
        $parent = '';

        if($trace) {
            $parent = sprintf('%s:%s', $trace[0]['file'], $trace[0]['line']);
        }

        if(LOG_ERRORS) {
            $data = sprintf("%s - %s Line: %d (%s) %s\n",
                $date->format(\DateTime::RFC1036), $filePath, $exception->getLine(), $parent, $exception->getMessage());
            file_put_contents($path, $data, FILE_APPEND);
            chmod($path, 0600);
        }
    }
}