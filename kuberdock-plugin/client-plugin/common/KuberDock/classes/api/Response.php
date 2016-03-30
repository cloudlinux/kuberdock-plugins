<?php

namespace Kuberdock\classes\api;

class Response
{
    public static function out($data, $redirect = null)
    {
        $array = array(
            'status' => 'OK',
        );

        if (is_array($data)) {
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

    private static function output($array, $code, $redirect)
    {
        header("Content-Type: application/json");
        header("HTTP/1.1 " . $code . " " . self::requestStatus($code));

        if ($redirect) {
            $array['redirect'] = $redirect;
        }

        echo json_encode($array);
    }

    private static function requestStatus($code)
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
}