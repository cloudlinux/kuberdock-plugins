<?php


namespace classes\models\addon;


use components\ClientArea;
use models\addon\App;
use models\addon\KubePrice;
use models\addon\KubeTemplate;
use models\addon\PackageRelation;
use tests\EloquentMock;
use tests\fixtures\DatabaseFixture;
use tests\models\billing\CurrencyStub as Currency;
use tests\models\billing\PricingStub as Pricing;
use tests\models\billing\PackageStub as Package;
use tests\TestCase;

class ClientAreaTest extends TestCase
{
    use EloquentMock;

    /**
     * @var ClientArea
     */
    private $clientArea;

    public function setUp()
    {
        parent::setUp();

        Currency::insert(DatabaseFixture::currency());
        Package::insert(DatabaseFixture::package());
        Pricing::insert(DatabaseFixture::pricing());
        PackageRelation::insert(DatabaseFixture::packageRelation());
        KubeTemplate::insert(DatabaseFixture::kubeTemplates());
        KubePrice::insert(DatabaseFixture::kubePrices());
        App::insert(DatabaseFixture::apps());

        $this->clientArea = new ClientArea();
    }

    public function mockTables()
    {
        return [
            PackageRelation::class,
            KubeTemplate::class,
            KubePrice::class,
            Package::class,
            Currency::class,
            Pricing::class,
            App::class,
        ];
    }

    public function testProductPricingOverride_NonKuberDock()
    {
        $package = Package::find(DatabaseFixture::$packageIdFixed);
        $package->update([
            'servertype' => 'cpanel',
        ]);

        $this->assertEmpty($this->clientArea->productPricingOverride($package->id));
    }

    public function testProductPricingOverride_FixedPredefinedApp()
    {
        $_SESSION[App::SESSION_FIELD] = 1;
        $package = Package::find(DatabaseFixture::$packageIdFixed);

        $this->assertEquals([
            'setup' => 0,
            'recurring' => 2.20,
        ], $this->clientArea->productPricingOverride($package->id));

        // Test with first deposit
        $package->setConfigOption('firstDeposit', 5);
        $this->assertEquals([
            'setup' => 0,
            'recurring' => 2.20,
        ], $this->clientArea->productPricingOverride($package->id));
    }

    public function testProductPricingOverride_PaygPredefinedApp()
    {
        $_SESSION[App::SESSION_FIELD] = 4;
        $package = Package::find(DatabaseFixture::$packageIdPayg);

        $this->assertEquals([
            'setup' => 5,
            'recurring' => 0,
        ], $this->clientArea->productPricingOverride($package->id));
    }
}