<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace exceptions;

use Exception;

class CException extends Exception {

    public function __construct($message, $code = 0, Exception $previous = null) {
        $this->logError($message);

        if(stripos($message, 'exist') !== false) {
            throw new ExistException($message, $code);
        } else {
            throw new Exception($message, $code);
        }
    }

    private function logError($message)
    {
        $filePath = substr($this->getFile(), stripos($this->getFile(), 'KuberDock'));

        if(KUBERDOCK_DEBUG && function_exists('logModuleCall')) {
            logModuleCall(KUBERDOCK_MODULE_NAME, $filePath, sprintf('Line: %d %s', $this->getLine(), $message),
                $this->getTraceAsString());
        }
    }
} 