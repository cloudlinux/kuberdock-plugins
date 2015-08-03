<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

class CException extends Exception {

    function __construct($message, $code = 0, Exception $previous = null) {
        if(strpos($message, 'exist') !== false) {
            throw new ExistException($message, $code);
        } else {
            throw new Exception($message, $code);
        }
    }
} 