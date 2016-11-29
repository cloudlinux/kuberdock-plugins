<?php

namespace tests\fixtures;


class WhmcsApiFixture
{
    public static function getVars($vars)
    {
        $post = array_merge([
            'action' => 'some_action',
            'username' => 'some_name',
            'password' => 'some_pasword',
        ], $vars);

        return ['_POST' => $post];
    }
}