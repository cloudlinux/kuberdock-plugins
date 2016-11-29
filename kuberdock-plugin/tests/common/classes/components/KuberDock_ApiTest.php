<?php

namespace tests\Kuberdock\classes\components;


use Kuberdock\classes\exceptions\CException;
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

    public function testGetDefaultKube()
    {
        $expected_json = '{ "data": { "available": true, "name": "Standard" }, "status": "OK" }';

        $this->curl_exec->expects($this->once())->willReturn($expected_json);
        $this->curl_getinfo->expects($this->once())->willReturn(['http_code' => 200]);

        $result = $this->api->getDefaultKube();

        $this->assertEquals($result, json_decode($expected_json, true)['data']);
    }

    public function testFillTemplate()
    {
        $expected_json = '{ "data": { "available": true, "name": "Standard" }, "status": "OK" }';

        $this->curl_exec->expects($this->once())->willReturn($expected_json);
        $this->curl_getinfo->expects($this->once())->willReturn(['http_code' => 200]);

        $data = [
            'id' => 12,
            'plan' => 123,
            'other_key' => 'other_value',
        ];
        $result = $this->api->fillTemplate($data);

        $this->assertEquals($result, json_decode($expected_json, true)['data']);
    }

    /**
     * @expectedException \tests\exceptions\UndefinedIndexException
     */
    public function testFillTemplateNoIdKey() {
        set_error_handler([\tests\exceptions\UndefinedIndexException::class, 'handler']);

        try {
            $this->api->fillTemplate(['plan' => 123,'other_key' => 'other_value']);
        } catch(\tests\exceptions\UndefinedIndexException $e) {
            restore_error_handler();
            throw $e;
        }
    }

    /**
     * @expectedException \tests\exceptions\UndefinedIndexException
     */
    public function testFillTemplateNoPlanKey() {
        set_error_handler([\tests\exceptions\UndefinedIndexException::class, 'handler']);

        try {
            $this->api->fillTemplate(['id' => 123,'other_key' => 'other_value']);
        } catch(\tests\exceptions\UndefinedIndexException $e) {
            restore_error_handler();
            throw $e;
        }
    }

    public function testGetDomains()
    {
        $expectedJson = '{ "data": [{ "id": 1, "name": "domain.com" }], "status": "OK" }';

        $this->curl_exec->expects($this->once())->willReturn($expectedJson);
        $this->curl_getinfo->expects($this->once())->willReturn(['http_code' => 200]);

        $result = $this->api->getDomains();

        $this->assertEquals($result, json_decode($expectedJson, true)['data']);
    }
}