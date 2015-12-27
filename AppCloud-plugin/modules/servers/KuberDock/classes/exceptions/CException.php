<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace exceptions;

use Exception;

/**
 * Class CException
 * @package exceptions
 */
class CException extends Exception {

    public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->logError();

        if(stripos($message, 'exist') !== false) {
            throw new ExistException($message, $code);
        } else {
            throw new Exception($message, $code);
        }
    }

    /**
     * @param Exception $exception
     */
    public static function log(Exception $exception)
    {
        $filePath = substr($exception->getFile(), stripos($exception->getFile(), 'KuberDock'));

        if(KUBERDOCK_DEBUG && function_exists('logModuleCall')) {
            logModuleCall(KUBERDOCK_MODULE_NAME, $filePath, sprintf('Line: %d %s', $exception->getLine(),
                $exception->getMessage()), $exception->getTraceAsString());
        }
    }

    /**
     * @param Exception $exception
     */
    public static function displayError(Exception $exception)
    {
        $_SESSION['kdError' . session_id()] = $exception->getMessage();
        header('Location: kdpage.php');
    }

    /**
     *
     */
    private function logError()
    {
        self::log($this);
    }
} 