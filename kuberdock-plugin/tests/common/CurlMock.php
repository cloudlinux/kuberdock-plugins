<?php

namespace tests;


use phpmock\phpunit\PHPMock;
use Kuberdock\classes\components\KuberDock_Api;

trait CurlMock
{
    use PHPMock;

    /** @var  KuberDock_Api $api */
    protected $api;

    protected $curl_exec;
    protected $curl_init;
    protected $curl_setopt;
    protected $curl_getinfo;
    protected $curl_error;
    protected $curl_close;

    public function setUpApi()
    {
        $namespace = 'Kuberdock\classes\components';

        $this->curl_exec = $this->getFunctionMock($namespace, "curl_exec");
        $this->curl_init = $this->getFunctionMock($namespace, "curl_init");
        $this->curl_setopt = $this->getFunctionMock($namespace, "curl_setopt");
        $this->curl_getinfo = $this->getFunctionMock($namespace, "curl_getinfo");
        $this->curl_error = $this->getFunctionMock($namespace, "curl_error");
        $this->curl_close = $this->getFunctionMock($namespace, "curl_close");

        $this->api = KuberDock_Api::create([
            'user' => 'user',
            'password' => 'pass',
            'url' => 'url',
        ]);
    }

}