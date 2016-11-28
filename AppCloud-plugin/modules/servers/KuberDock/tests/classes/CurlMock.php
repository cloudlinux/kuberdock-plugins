<?php

namespace tests;


use phpmock\phpunit\PHPMock;


trait CurlMock
{
    use PHPMock;

    protected $curl_exec;
    protected $curl_init;
    protected $curl_setopt;
    protected $curl_getinfo;
    protected $curl_error;
    protected $curl_close;

    public function setUpCurl()
    {
        $namespace = 'api';

        $this->curl_exec = $this->getFunctionMock($namespace, "curl_exec");
        $this->curl_init = $this->getFunctionMock($namespace, "curl_init");
        $this->curl_setopt = $this->getFunctionMock($namespace, "curl_setopt");
        $this->curl_getinfo = $this->getFunctionMock($namespace, "curl_getinfo");
        $this->curl_error = $this->getFunctionMock($namespace, "curl_error");
        $this->curl_close = $this->getFunctionMock($namespace, "curl_close");

    }

    /**
     * Api will return asked data several times with http_code=200
     * @param array|mixed $data
     *
     * data format:
     *     [
     *          'name of 1. function (just to not forget it)' => 'data to be recived from 1. function',
     *          'name of 1. function'                         => 'data to be recived from 2. function',
     *          ...
     *     ]
     */
    public function curlOk($data)
    {
        if (!is_array($data)) {
            $data = (array ($data));
        }

        $data = array_map(function($item) {
            return '{ "data": ' . json_encode($item) . ', "status": "OK" }';
        }, $data);

        $calls = new \PHPUnit_Framework_MockObject_Stub_ConsecutiveCalls(array_values($data));
        $this->curl_exec->expects($this->any())->will($calls);
        $this->curl_getinfo->expects($this->any())->willReturn(['http_code' => 200]);
    }

    public function curlError()
    {
        $this->curl_exec->expects($this->any())->willReturn('');
        $this->curl_getinfo->expects($this->any())->willReturn(['http_code' => 500]);
    }
}