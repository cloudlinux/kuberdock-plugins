<?php

namespace tests\api\whmcs;


use api\whmcs\GetInfo;
use tests\fixtures\WhmcsApiFixture;
use tests\fixtures\DatabaseFixture;
use tests\TestCase;
use tests\EloquentMock;
use tests\CurlMock;

use models\addon\PackageRelation;

use tests\models\billing\ServerStub as Server;
use tests\models\billing\ServerGroupStub as ServerGroup;
use tests\models\billing\ServerGroupRelationStub as ServerGroupRelation;
use tests\models\billing\ClientStub as Client;
use tests\models\billing\ServiceStub as Service;
use tests\models\billing\PackageStub as Package;
use tests\models\billing\ConfigStub as Config;
use tests\models\billing\AdminStub as Admin;
use tests\models\billing\CustomFieldStub as CustomField;
use tests\models\billing\CustomFieldValueStub as CustomFieldValue;

class GetInfoTest extends TestCase
{
    use CurlMock;
    use EloquentMock;

    private $vars;

    private $server_id = 14;
    private $service_id = 144;
    private $package_id = 12;
    private $SystemURL = 'some_SystemURL';
    private $server_ipaddress = '8.8.8.8';
    private $server_hostname = 'some_hostname';
    private $service_token = 'some_serviceToken';
    private $kuber_product_id = 45;

    public function setUp()
    {
        parent::setUp();

        $this->getFunctionMock('components', "localAPI")
            ->expects($this->any())
            ->willReturn(['result' => 'success', 'password' => 'password']);

        $this->vars = WhmcsApiFixture::getVars(['kdServer' => 'http://8.8.8.8', 'user' => 'some_user', 'userDomains' => 'domain.com']);
    }

    public function mockTables()
    {
        return [
            Admin::class,
            Server::class,
            ServerGroup::class,
            ServerGroupRelation::class,
            Client::class,
            Config::class,
            Service::class,
            Package::class,
            PackageRelation::class,
            CustomField::class,
            CustomFieldValue::class,
        ];
    }

    public function testAnswer_NoClient()
    {
        $result = GetInfo::call($this->vars);

        $this->assertEquals('error', $result['result']);
        $this->assertEquals('User not found. Probably you have no service with your current domain.', $result['message']);
    }

    public function testAnswer_ProductNotFound()
    {
        $this->createAll();
        PackageRelation::find($this->package_id)->delete();

        $result = GetInfo::call($this->vars);

        $this->assertEquals('error', $result['result']);
        $this->assertEquals('KuberDock product for server http://8.8.8.8 not found', $result['message']);
    }

    public function testAnswer_NoService()
    {
        $this->createAll();

        $this->curlOk([
            'getPackageById:63' => 'some_getPackageById',
            'getDefaultKubeType:74' => 'some_getDefaultKubeType',
            'getDefaultPackageId:75' => 'some_getDefaultPackageId',
        ]);

        $expected = array (
            'service' =>
                array (
                    'id' => $this->service_id,
                    'product_id' => $this->package_id,
                    'token' => $this->service_token,
                    'domainstatus' => 'Active',
                    'orderid' => NULL,
                    'kuber_product_id' => $this->kuber_product_id,
                ),
            'package' => 'some_getPackageById',
            'billingUser' =>
                array (
                    'id' => DatabaseFixture::$userId,
                    'defaultgateway' => 'mailin',
                ),
            'billing' => 'WHMCS',
            'billingLink' => $this->SystemURL,
            'default' =>
                array (
                    'kubeType' => 'some_getDefaultKubeType',
                    'packageId' => 'some_getDefaultPackageId',
                ),
        );

        $result = GetInfo::call($this->vars);

        $this->assertEquals('success', $result['result']);
        $this->assertEquals($expected, $result['results']);
    }

    public function testAnswer()
    {
        $this->createAll();

        $this->curlOk([
            'getPackages:63' => 'some_getPackages',
            'getDefaultKubeType:74' => 'some_getDefaultKubeType',
            'getDefaultPackageId:75' => 'some_getDefaultPackageId',
        ]);

        Service::find($this->service_id)->update(['domainstatus' => 'Pending']);

        $result = GetInfo::call($this->vars);

        $expected = array (
            'packages' => 'some_getPackages',
            'billingUser' =>
                array (
                    'id' => DatabaseFixture::$userId,
                    'defaultgateway' => 'mailin',
                ),
            'billing' => 'WHMCS',
            'billingLink' => $this->SystemURL,
            'default' =>
                array (
                    'kubeType' => 'some_getDefaultKubeType',
                    'packageId' => 'some_getDefaultPackageId',
                ),
        );
        $this->assertEquals('success', $result['result']);
        $this->assertEquals($expected, $result['results']);
    }

    private function createAll()
    {
        Client::create(DatabaseFixture::client());

        Server::create([
            'id' => $this->server_id,
            'type' => KUBERDOCK_MODULE_NAME,
            'disabled' => 0,
            'active' => 1,
            'secure' => 'on',
            'hostname' => $this->server_hostname,
            'ipaddress' => $this->server_ipaddress,
            'password' => 'password',
            'accesshash' => 'accesshash',
            'username' => 'username',
        ]);

        ServerGroup::create(['name' => 'KuberDock group', 'filltype' => 1]);

        ServerGroupRelation::create(['groupid' => 1, 'serverid' => $this->server_id]);

        Package::create([
            'id' => $this->package_id,
            'gid' => 1,
            'type' => 'other',
            'name' => 'KuberDock',
            'paytype' => 'onetime',
            'autosetup' => 'order',
            'servertype' => KUBERDOCK_MODULE_NAME,
            'servergroup' => 1,
            'hidden' => 0,
        ]);

        CustomField::create([
            'type' => 'product',
            'relid' => $this->package_id,
            'fieldname' => 'Token',
        ]);
        $service = Service::create([
            'id' => $this->service_id,
            'userid' => DatabaseFixture::$userId,
            'username' => 'some_user',
            'domain' => 'domain.com',
            'domainstatus' => 'Active',
            'server' => $this->server_id,
            'packageid' => $this->package_id,
        ]);
        $service->setToken($this->service_token);

        PackageRelation::create([
            'kuber_product_id' => $this->kuber_product_id,
            'product_id' => $this->package_id,
        ]);

        Admin::create([
            'roleid' => Admin::FULL_ADMINISTRATOR_ROLE_ID,
            'disabled' => 0,
            'username' => 'admin',
        ]);

        Config::create(['setting' => 'SystemURL', 'value' => $this->SystemURL]);
    }
}
