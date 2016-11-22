<?php

namespace tests\Kuberdock\classes\models;


use tests\TestCase;
use tests\CurlMock;
use Kuberdock\classes\models\Template;
use Kuberdock\classes\panels\KuberDock_Panel;

class TemplateTest extends TestCase
{
    use CurlMock;

    public function setUp()
    {
        parent::setUp();

        $this->setUpApi();
    }

    /**
     * group ee
     */
    public function testFillData()
    {
        $expected_json = '{ "data":"apiVersion: v1\nkuberdock:\n  appPackage:\n    goodFor: regular use\n", "status": "OK" }';
        $expected_array = [
            'apiVersion' => 'v1',
            'kuberdock' => [
                'appPackage' => ['goodFor' => 'regular use'],
            ],
        ];

        $this->curl_exec->expects($this->once())->willReturn($expected_json);
        $this->curl_getinfo->expects($this->once())->willReturn(['http_code' => 200]);

        $panel = $this->createMock(KuberDock_Panel::class);
        $panel->expects($this->once())
            ->method('getAdminApi')
            ->will($this->returnValue($this->api));

        $template = new Template($panel);
        $template->fillData(['id' => 12, 'plan' => 123]);

        $this->assertEquals($template->data, $expected_array);
    }
}