<?php

namespace components;

use Exception;

class Csrf extends Component
{
    /**
     * Don't use name 'token' to avoid conflict with built-in whmsc token
     */
    const TOKEN_NAME = 'csrf_token';

    /**
     * @return string
     */
    private static function get()
    {
        if (!isset($_SESSION[self::TOKEN_NAME])) {
            $_SESSION[self::TOKEN_NAME] = md5(uniqid(rand(). time() . 'some_salt'));
        }

        return $_SESSION[self::TOKEN_NAME];
    }

    /**
     * @return string
     */
    public static function render()
    {
        return '<input type="hidden" name="' . self::TOKEN_NAME . '" value="' . self::get() . '">';
    }

    /**
     * @throws Exception
     */
    public static function check()
    {
        if ($_POST[self::TOKEN_NAME] != self::get()) {
            throw new Exception('Invalid token');
        }
    }

}