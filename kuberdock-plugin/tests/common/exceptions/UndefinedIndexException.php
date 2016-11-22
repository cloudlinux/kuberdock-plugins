<?php

namespace tests\exceptions;

class UndefinedIndexException extends \Exception
{
    public static function handler($errno, $errstr, $errfile, $errline )
    {
        if ($errstr==='Undefined index: id' || $errstr==='Undefined index: plan') {
            throw new UndefinedIndexException($errstr, 0);
        }
        return false;
    }
}