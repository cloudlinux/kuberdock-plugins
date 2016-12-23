<?php


namespace classes\models\addon;


use api\Api;
use Carbon\Carbon;
use models\addon\App;
use models\addon\billing\AutomationSettings;
use models\addon\billing\Payg;
use models\addon\Item;
use models\addon\ItemInvoice;
use models\addon\KubePrice;
use models\addon\KubeTemplate;
use models\addon\PackageRelation;
use models\addon\ResourceItems;
use models\addon\ResourcePods;
use models\addon\Resources;
use tests\EloquentMock;
use tests\ExternalApiMock;
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

class PaygTest extends TestCase
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

        Package::insert(DatabaseFixture::package());
        PackageRelation::insert(DatabaseFixture::packageRelation());
        KubeTemplate::insert(DatabaseFixture::kubeTemplates());
        KubePrice::insert(DatabaseFixture::kubePrices());
        Item::create(DatabaseFixture::paygItem());
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

        // Set PAYG package
        $this->service->packageid = DatabaseFixture::$packageIdPayg;
        (new App)->update([
            'product_id' => DatabaseFixture::$packageIdPayg,
        ]);

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

    public function testOrder_PredefinedApp()
    {
        $app = App::find(1);

        $billing = $this->service->package->getBilling();
        $billing->app = $app;
        $invoice = $billing->order($app->getResource(), $this->service);

        $this->assertNull($invoice);
        $this->assertEquals('pod_id', $app->pod_id);

        $itemInvoice = ItemInvoice::where('invoice_id', $invoice->id)->first();
        $this->assertNull($itemInvoice);
    }

    public function testSuspendAll()
    {
        $app = App::find(1);
        $billing = $this->getMockBuilder(Payg::class)->setMethods(['freeAllResources'])->getMock();
        $billing->order($app->getResource(), $this->service);

        $invoice = Invoice::create([
            'userid' => $this->service->userid,
            'duedate' => (new Carbon())->addDays(-5),
            'subtotal' => $app->getResource()->getInvoiceItems()->sum(),
            'paymentmethod' => 'test',
        ]);

        $item = Item::first();

        // Has one paid invoice
        $item->invoices()->create([
            'invoice_id' => -1,
            'status' => Invoice::STATUS_PAID,
            'type' => 'order',
        ]);

        $item->invoices()->create([
            'invoice_id' => $invoice->id,
            'status' => Invoice::STATUS_UNPAID,
            'type' => 'order',
        ]);

        $settings = new AutomationSettings();
        $settings->setAttributes([
            'suspend' => true,
            'suspendDays' => 5,
            'termination' => true,
            'terminationDays' => 10,
            'invoiceReminderDays' => 0,
            'invoiceNoticeDays' => 0,
        ]);

        $method = $this->getMethod('suspendAll', $billing);
        $method->invokeArgs($billing, [$settings]);

        // Test suspend
        $this->assertEquals(1, $this->calledTimes['suspendmodule']);

        // Test termination
        $invoice->duedate = (new Carbon())->addDays(-10);
        $invoice->save();
        $billing->expects($this->once())->method('freeAllResources');

        $method->invokeArgs($billing, [$settings]);
        // Send termination email
        $this->assertEquals(1, $this->calledTimes['sendemail']);

        $item = Item::first();
        $this->assertEquals(Resources::STATUS_DELETED, $item->status);
    }
}