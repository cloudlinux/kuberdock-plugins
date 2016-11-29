<?php

namespace tests\api\whmcs;


use api\whmcs\DeletePod;
use tests\fixtures\WhmcsApiFixture;
use tests\TestCase;
use tests\EloquentMock;

use models\addon\Item;

use tests\models\billing\BillableItemStub as BillableItem;

class DeletePodTest extends TestCase
{
    use EloquentMock;

    public function mockTables()
    {
        return [
            Item::class,
            BillableItem::class,
        ];
    }

    public function testAnswer_NoPod()
    {
        $vars = WhmcsApiFixture::getVars(['pod_id' => 'wrong_id']);

        $result = DeletePod::call($vars);

        $this->assertEquals('error', $result['result']);
        $this->assertEquals('Pod not found', $result['message']);
    }


    /**
     * @group ee
     */
    public function testAnswer()
    {
        $pod_id = 'some_pod_id';
        $vars = WhmcsApiFixture::getVars(['pod_id' => $pod_id]);

        Item::create([
            'id' => 234,
            'user_id' => 345,
            'service_id' => 124,
            'pod_id' => $pod_id,
            'billable_item_id' => 2345,
        ]);

        $result = DeletePod::call($vars);

        $this->assertEquals('success', $result['result']);
        $this->assertEquals('Pod deleted', $result['results']);
    }
}
