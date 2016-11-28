<?php

namespace tests\models\addon;


use tests\TestCase;
use tests\EloquentMock;
use tests\CurlMock;
use models\addon\Addon;
use tests\fixtures\ApiFixture;

use models\addon\KubePriceChange;
use models\addon\PackageRelation;
use models\addon\KubeTemplate;
use models\addon\KubePrice;
use models\addon\Trial;
use models\addon\State;
use models\addon\App;
use models\addon\Item;
use models\addon\ItemInvoice;
use models\addon\Migration;
use models\addon\Resources;
use models\addon\ResourcePods;

use tests\models\billing\ServerStub as Server;
use tests\models\billing\ServerGroupStub as ServerGroup;
use tests\models\billing\ServerGroupRelationStub as ServerGroupRelation;
use tests\models\billing\AdminStub as Admin;
use tests\models\billing\ConfigStub as Config;
use tests\models\billing\CurrencyStub as Currency;
use tests\models\billing\PricingStub as Pricing;
use tests\models\billing\PackageGroupStub as PackageGroup;
use tests\models\billing\PackageStub as Package;
use tests\models\billing\EmailTemplateStub as EmailTemplate;
use tests\models\billing\CustomFieldStub as CustomField;

class AddonTest extends TestCase
{
    use CurlMock;
    use EloquentMock;

    public function setUp()
    {
        parent::setUp();

        Server::create([
            'id' => 1,
            'type' => KUBERDOCK_MODULE_NAME,
            'disabled' => 0,
            'active' => 1,
            'secure' => 'on',
            'hostname' => 'hostname',
            'ipaddress' => '8.8.8.8',
            'password' => 'password',
            'accesshash' => 'accesshash',
            'username' => 'username',
        ]);


        // create admin for api
        Admin::create([
            'roleid' => Admin::FULL_ADMINISTRATOR_ROLE_ID,
            'disabled' => 0,
            'username' => 'admin',
        ]);

        $this->getFunctionMock('components', "localAPI")
            ->expects($this->any())
            ->willReturn(['result' => 'success', 'password' => 'password']);

        Config::create(['setting' => 'SystemURL', 'value' => 'some_url']);
        Currency::create(['default' => 1]);
    }

    public function mockTables()
    {
        return [
            Server::class,
            ServerGroup::class,
            ServerGroupRelation::class,
            Admin::class,
            Config::class,
            PackageGroup::class,
            Package::class,
            Currency::class,
            KubePriceChange::class, // before creating KubePriceChange always create Currency at first, and vice versa
            EmailTemplate::class,
            Pricing::class,
            CustomField::class,
            Item::class,
        ];
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage KuberDock plugin require PHP version 5.4 or greater.
     */
    public function testActivation_LowPhpVersion()
    {
        $this->getFunctionMock('models\addon', "phpversion")
            ->expects($this->once())
            ->willReturn("5.3.8-0ubuntu0.16.04.3");

        Addon::model()->activate();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage KuberDock plugin require PHP (PDO).
     */
    public function testActivation_NoPdo()
    {
        $this->getFunctionMock('models\addon', "class_exists")
            ->expects($this->once())
            ->willReturn(false);

        Addon::model()->activate();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Add KuberDock server and server group before activating addon.
     */
    public function testActivation_NoServer()
    {
        Server::find(1)->delete();
        Addon::model()->activate();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Cannot connect to KuberDock server. Please check server credentials.
     */
    public function testActivation_CannotConnectToServer()
    {
        $this->curlError();
        Addon::model()->activate();
    }

    /**
     * @expectedException \Exception
     */
    public function testActivation_ServerIpWrong()
    {
        $this->curlOk('ok');
        Server::find(1)->update(['ipaddress' => 'some_wrong_ip']);

        $regexp = '/^KuberDock server IP address is wrong\. Please edit it on .+/';
        $this->setExpectedExceptionRegExp(\Exception::class, $regexp);

        Addon::model()->activate();
    }

    /**
     * @expectedException \Exception
     */
    public function testActivation_ServerHostnameWrong()
    {
        $this->curlOk(true);

        $regexp = '/^KuberDock server hostname is wrong\. Please edit it on .+/';
        $this->setExpectedExceptionRegExp(\Exception::class, $regexp);

        Addon::model()->activate();
    }

    /**
     * @runInSeparateProcess
     */
    public function testActivation()
    {
        $this->curlOk([
            'getPackages:45' => ApiFixture::getPackages(),
            'getPackages:154' => ApiFixture::getPackages(),
            'getKubes:158' => ApiFixture::getKubes(),
        ]);

        $this->getFunctionMock('models\addon', 'gethostbyname')
            ->expects($this->any())
            ->willReturn("8.8.8.8");

        Config::create(['setting' => 'APIAllowedIPs', 'value' => 'a:0:{}']);
        Config::create(['setting' => 'ModuleHooks', 'value' => '']);

        ServerGroup::create(['name' => 'KuberDock group', 'filltype' => 1]);
        ServerGroupRelation::create(['groupid' => 1, 'serverid' => 1]);

        Item::create(['user_id' => 1, 'service_id' => 1]);
        Addon::model()->activate();

        // package group is created
        $packageGroup = PackageGroup::all();
        $this->assertEquals(1, $packageGroup->count());
        $this->assertEquals('KuberDock', $packageGroup->first()->name);

        // all tables are created
        $allTables = [
            PackageRelation::class,
            KubeTemplate::class,
            KubePrice::class,
            Trial::class,
            State::class,
            App::class,
            KubePriceChange::class,
            Item::class,
            ItemInvoice::class,
            Migration::class,
            Resources::class,
            ResourcePods::class,
        ];
        foreach ($allTables as $table) {
            $this->assertTrue($table::tableExists());
        }

        // all information from tables is erased ( for example, tables were dropped and than recreated)
        $this->assertEquals(0, Item::all()->count());

        // all migrations are filled into the table
        $this->assertEquals(5, Migration::all()->count());

        // all (6) email templates are created
        $this->assertEquals(6, EmailTemplate::all()->count());

        // all needed config values are saved
        $expectedConfig = [
            'SystemURL' => 'some_url',
            'APIAllowedIPs' => 'a:1:{i:0;a:2:{s:2:"ip";s:7:"8.8.8.8";s:4:"note";s:9:"KuberDock";}}',
            'ModuleHooks' => 'KuberDock',
        ];
        $this->assertEquals($expectedConfig, Config::all()->lists('value','setting'));

        // 3 kube templates created
        $this->assertEquals(3, KubeTemplate::all()->count());

        // 2 packages created
        $this->assertEquals(2, Package::all()->count());

        // 1 pricing created
        $this->assertEquals(1, Pricing::all()->count());
        $this->assertEquals(1, Pricing::all()->first()->currency);

        $this->assertEquals(2, CustomField::all()->count());
        $this->assertEquals(2, PackageRelation::all()->count());
        $this->assertEquals(4, KubePrice::all()->count());
    }
}
