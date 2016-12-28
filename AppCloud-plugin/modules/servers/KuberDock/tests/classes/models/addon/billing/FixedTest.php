<?php


namespace classes\models\addon;


use api\Api;
use api\ApiResponse;
use Carbon\Carbon;
use models\addon\App;
use models\addon\billing\Fixed;
use models\addon\Item;
use models\addon\ItemInvoice;
use models\addon\KubePrice;
use models\addon\KubeTemplate;
use models\addon\PackageRelation;
use models\addon\resource\Pod;
use models\addon\ResourceItems;
use models\addon\ResourcePods;
use models\addon\Resources;
use tests\EloquentMock;
use tests\ExternalApiMock;
use tests\fixtures\ApiFixture;
use tests\fixtures\DatabaseFixture;
use tests\InternalApiMock;
use tests\MakePublicTrait;
use tests\models\billing\AdminStub as Admin;
use tests\models\billing\BillableItemStub as BillableItem;
use tests\models\billing\InvoiceStub as Invoice;
use tests\models\billing\InvoiceItemStub as InvoiceItem;
use tests\models\billing\PackageStub as Package;
use tests\models\billing\ServiceStub as Service;
use tests\models\billing\ClientStub as Client;
use tests\TestCase;

class FixedTest extends TestCase
{
    use InternalApiMock;
    use EloquentMock;
    use MakePublicTrait;

    /**
     * Mocked service
     * @var Service
     */
    protected $service;
    /** Mocked
     * @var Api
     */
    protected $api;

    public function setUp()
    {
        parent::setUp();

        Package::create(DatabaseFixture::package());
        PackageRelation::create(DatabaseFixture::packageRelation());
        KubeTemplate::insert(DatabaseFixture::kubeTemplates());
        KubePrice::insert(DatabaseFixture::kubePrices());
        Item::create(DatabaseFixture::fixedItem());
        Service::create(DatabaseFixture::service());
        Admin::create(DatabaseFixture::admin());
        App::insert(DatabaseFixture::apps());
        Client::create(DatabaseFixture::client());

        // Mock KD API
        $this->api = (new ExternalApiMock())->externalApiMock();
        // Mock local WHMCS API
        $this->internalApiMock();

        $service = $this->getMockBuilder(Service::class)->setMethods(['getApi', 'getAdminApi'])->getMock();
        $this->service = $service::find(DatabaseFixture::$serviceId);
        $this->service->expects($this->any())->method('getApi')->willReturn($this->api);
        $this->service->expects($this->any())->method('getAdminApi')->willReturn($this->api);
    }

    public function mockTables()
    {
        return [
            PackageRelation::class,
            KubeTemplate::class,
            KubePrice::class,
            Item::class,
            ItemInvoice::class,
            BillableItem::class,
            Service::class,
            Package::class,
            Admin::class,
            App::class,
            Resources::class,
            ResourcePods::class,
            ResourceItems::class,
            Client::class,
            Invoice::class,
            InvoiceItem::class,
        ];
    }

    /**
     * Order predefined app
     */
    public function testOrder_PredefinedApp()
    {
        $app = App::find(1);
        $invoice = $this->service->package->getBilling()->order($app->getResource(), $this->service);
        $this->assertEquals('Unpaid', $invoice->status);
        $this->assertEquals(2.2, $invoice->subtotal);

        $itemInvoice = ItemInvoice::where('invoice_id', $invoice->id)->first();
        $this->assertInstanceOf(ItemInvoice::class, $itemInvoice);
        $this->assertInstanceOf(Item::class, $itemInvoice->item);

        $expected = [
            'id' => 1,
            'userid' => 13,
            'description' => 'KuberDock - Pod redis',
            'recur' => 1,
            'recurcycle' => 'Months',
            'recurfor' => 0,
            'invoiceaction' => 4,
            'amount' => 2.2,
            'invoicecount' => 1,
            'duedate' => (new Carbon())->addMonth()->toDateTimeString(),
        ];
        $this->assertNotNull($itemInvoice->item->billableItem);
        $this->assertEquals($expected, $itemInvoice->item->billableItem->getAttributes());
    }

    /**
     * Order pod
     */
    public function testOrder_Pod()
    {
        $app = App::find(2);

        $invoice = $this->service->package->getBilling()->order($app->getResource(), $this->service);
        $this->assertEquals('Unpaid', $invoice->status);
        $this->assertEquals(3.2, $invoice->subtotal);

        $itemInvoice = ItemInvoice::where('invoice_id', $invoice->id)->first();
        $this->assertInstanceOf(ItemInvoice::class, $itemInvoice);
        $this->assertInstanceOf(Item::class, $itemInvoice->item);
        $this->assertNotNull($itemInvoice->item->billableItem);

        $expected = [
            'id' => 1,
            'userid' => 13,
            'description' => 'KuberDock - Pod New Pod #1',
            'recur' => 1,
            'recurcycle' => 'Months',
            'recurfor' => 0,
            'invoiceaction' => 4,
            'amount' => 3.2,
            'invoicecount' => 1,
            'duedate' => (new Carbon())->addMonth()->toDateTimeString(),    // TODO: can be not equal if machine is slow
        ];
        $this->assertEquals($expected, $itemInvoice->item->billableItem->getAttributes());
    }

    public function testAfterOrderPayment()
    {
        $app = App::find(2);
        $invoice = $this->service->package->getBilling()->order($app->getResource(), $this->service);
        $itemInvoice = ItemInvoice::where('invoice_id', $invoice->id)->first();
        $itemInvoice->item->setRelation('service', $this->service);
        $pod = $this->service->package->getBilling()->afterOrderPayment($itemInvoice);

        $this->assertEquals('running', $pod->status);

        // Test added resources
        $exprected = [
            [
                'id' => 1,
                'user_id' => 13,
                'name' => 'nginx_test',
                'type' => 'Storage',
                'status' => 'Active',
            ],
            [
                'id' => 2,
                'user_id' => 13,
                'name' => 1,
                'type' => 'IP',
                'status' => 'Active',
            ]
        ];

        $this->assertEquals($exprected, Resources::all()->toArray());

        // Test resource pods
        $expected = [
            'id' => 2,
            'pod_id' => 'pod_id#2',
            'resource_id' => 2,
        ];
        $actual = Resources::where('type', Resources::TYPE_IP)->first()->resourcePods->first()->toArray();
        $this->assertEquals($expected, $actual);

        // Test resource items
        $expected = [
            [
                'id' => 1,
                'pod_id' => 'pod_id#2',
                'resource_id' => 1,
                'pivot' => [
                    'item_id' => 24,
                    'resource_pod_id' => 1,
                ],
            ],
            [
                'id' => 2,
                'pod_id' => 'pod_id#2',
                'resource_id' => 2,
                'pivot' => [
                    'item_id' => 24,
                    'resource_pod_id' => 2,
                ],
            ],
        ];
        $actual = $itemInvoice->item->resourcePods->toArray();
        $this->assertEquals($expected, $actual);
    }

    public function testCreateEditInvoice_AddResources()
    {
        $app = App::find(3);
        $billing = $this->service->package->getBilling();

        $invoice = $billing->order($app->getResource(), $this->service);
        $itemInvoice = ItemInvoice::where('invoice_id', $invoice->id)->first();
        $itemInvoice->item->setRelation('service', $this->service);

        $method = $this->getMethod('createEditInvoice', $billing);

        $pod = new Pod($this->service->package);
        $pod->setAttributes([
            'id' => 'pod_id#2',
            'name' => 'New Pod #1',
            'volumes' => [
            ],
            'kube_type' => 1,
            'containers' => [
                [
                    'kubes' => 1,
                    'name' => 'kniyq4s',
                    'image' => 'nginx',
                    'volumeMounts' => [
                        [
                            'mountPath' => '/var/log',
                            'name' => 'tp5547kq4d'
                        ]
                    ],
                    'ports' => [
                        [
                            'isPublic' => false,
                            'protocol' => 'tcp',
                            'containerPort' => 443,
                            'hostPort' => 443
                        ],
                        [
                            'isPublic' => false,
                            'protocol' => 'tcp',
                            'containerPort' => 80,
                            'hostPort' => 80
                        ],
                    ],
                ],
            ]
        ]);
        $pod->edited_config = ApiFixture::getPodWithResources('pod_id#2');

        $itemInvoice->status = Invoice::STATUS_PAID;
        $itemInvoice->save();

        $invoice = $method->invokeArgs($billing, [$pod, $itemInvoice->item, $this->service]);
        $this->assertEquals(3, $invoice->subtotal);
    }
}