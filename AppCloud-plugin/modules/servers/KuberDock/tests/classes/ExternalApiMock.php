<?php

namespace tests;


use api\Api;
use api\ApiResponse;
use Carbon\Carbon;
use tests\fixtures\ApiFixture;

class ExternalApiMock extends TestCase
{
    /**
     * Mocked api
     * @var Api
     */
    protected $api;

    /**
     * @return Api|\PHPUnit_Framework_MockObject_MockObject
     */
    public function externalApiMock()
    {
        $this->api = $this->getMockBuilder(Api::class)->setMethods([
            'createPodFromYaml',
            'getIpPoolStat',
            'getPod',
            'updatePod',
            'startPod',
            'applyEdit',
            'getUsage',
        ])->getMock();

        $apiResponse = new ApiResponse();
        $apiResponse->parsed = ApiFixture::createPodFromYaml();
        $this->api->expects($this->any())->method('createPodFromYaml')->willReturn($apiResponse);

        $apiResponse = new ApiResponse();
        $apiResponse->parsed = ApiFixture::getIpPoolStatWithIp();
        $this->api->expects($this->any())->method('getIpPoolStat')->willReturn($apiResponse);

        $this->api->expects($this->any())->method('getPod')->willReturnCallback(function () {
            $args = func_get_args();
            return ApiFixture::getPodWithResources($args[0]);
        });

        $this->api->expects($this->any())->method('updatePod')->willReturnCallback(function () {
            $args = func_get_args();
            return $this->updatePod($args[0], $args[1]);
        });

        $this->api->expects($this->any())->method('startPod')->willReturnCallback(function () {
            $args = func_get_args();
            return $this->startPod($args[0]);
        });

        $this->api->expects($this->any())->method('applyEdit')->willReturnCallback(function () {
            $args = func_get_args();
            return $this->applyEdit($args[0]);
        });

        $this->api->expects($this->any())->method('getUsage')->willReturnCallback(function () {
            $args = func_get_args();
            return $this->getUsage($args[0], $args[1], $args[2]);
        });

        return $this->api;
    }

    /**
     * @param string $podId
     * @param array $data
     * @return array
     */
    public function updatePod($podId, $data)
    {
        return array_merge(ApiFixture::getPodWithResources($podId), $data);
    }

    /**
     * @param string $podId
     * @return ApiResponse
     */
    public function startPod($podId)
    {
        $response = new ApiResponse();
        $response->parsed = [
            'status' => 'OK',
            'data' => array_merge(ApiFixture::getPodWithResources($podId), ['status' => 'running']),
        ];

        return $response;
    }

    public function applyEdit($podId)
    {
        return [
            'edited_config' => '',
        ];
    }

    public function getUsage($username, Carbon $dateStart, Carbon $dateEnd)
    {
        $response = new ApiResponse();
        $response->parsed = ApiFixture::getUsage();

        return $response;
    }
}