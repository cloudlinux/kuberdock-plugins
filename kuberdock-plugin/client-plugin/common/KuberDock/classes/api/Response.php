<?php

namespace Kuberdock\classes\api;

use Kuberdock\classes\Base;

class Response
{
    public static function out($data, $redirect = null)
    {
        $array = array(
            'status' => 'OK',
        );

        if (is_array($data) || is_null($data)) {
            $array['data'] = $data;
        } else {
            $array['message'] = $data;
        }

        self::output($array, 200, $redirect);
    }

    public static function error($message, $code, $redirect = null)
    {
        $array = array(
            'status' => 'ERROR',
            'message' => $message,
        );

        self::output($array, $code, $redirect);
    }

    public static function requestStatus($code)
    {
        $status = array(
            200 => 'OK',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
        );

        return ($status[$code])
            ? $status[$code]
            : $status[500];
    }

    private static function output($array, $code, $redirect)
    {
        Base::model()->getStaticPanel()->renderResponseHeaders($code);

        if ($redirect) {
            $array['redirect'] = $redirect;
        }

        echo json_encode($array);
    }
}