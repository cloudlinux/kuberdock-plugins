<?php

namespace tests\Kuberdock\classes\components;


use tests\TestCase;
use tests\CurlMock;

class KuberDock_ApiTest extends TestCase
{
    use CurlMock;

    public function setUp()
    {
        parent::setUp();

        $this->setUpApi();
    }

    /**
     * @group ee
     */
    public function testGetDefaultKube()
    {
        $expected_json = '{ "data": { "available": true, "name": "Standard" }, "status": "OK" }';

        $this->curl_exec->expects($this->once())->willReturn($expected_json);
        $this->curl_getinfo->expects($this->once())->willReturn(['http_code' => 200]);

        $result = $this->api->getDefaultKube();

        $this->assertEquals($result, json_decode($expected_json, true)['data']);
    }

}