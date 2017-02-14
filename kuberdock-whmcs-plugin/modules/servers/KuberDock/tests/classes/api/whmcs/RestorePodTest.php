<?php

namespace tests\api\whmcs;

use api\whmcs\RestorePod;
use models\addon\Item;

use tests\fixtures\WhmcsApiFixture;
use tests\TestCase;
use tests\EloquentMock;
use tests\models\billing\BillableItemStub as BillableItem;
use tests\models\billing\ServiceStub as Service;
use tests\models\billing\PackageStub as Package;
use tests\models\billing\AdminStub as Admin;


class RestorePodTest extends TestCase
{
    use EloquentMock;

    public function mockTables()
    {
        return [
            Item::class,
            BillableItem::class,
            Service::class,
            Package::class,
            Admin::class,
        ];
    }

    public function testAnswer_NoPod()
    {
        $vars = WhmcsApiFixture::getVars(['pod_id' => 'wrong_id']);

        $result = RestorePod::call($vars);

        $this->assertEquals('error', $result['result']);
        $this->assertEquals('Pod not found', $result['message']);
    }

    public function testAnswer_Overdue()
    {
        $pod_id = 'some_pod_id';
        $vars = WhmcsApiFixture::getVars(['pod_id' => $pod_id]);

        Item::create([
            'id' => 234,
            'user_id' => 345,
            'service_id' => 124,
            'pod_id' => $pod_id,
            'billable_item_id' => 2345,
            'due_date' => '2016-12-01',
        ]);

        $result = RestorePod::call($vars);

        $this->assertEquals('error', $result['result']);
        $this->assertEquals('Overdue, can\'t restore pod', $result['message']);
    }
}
