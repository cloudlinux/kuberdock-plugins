<?php


namespace classes\models\addon;


use tests\EloquentMock;
use tests\fixtures\DatabaseFixture;
use tests\models\billing\CurrencyStub as Currency;
use tests\models\billing\PricingStub as Pricing;
use tests\models\billing\PackageStub as Package;
use tests\TestCase;

class PricingTest extends TestCase
{
    use EloquentMock;

    public function setUp()
    {
        parent::setUp();

        Currency::insert(DatabaseFixture::currency());
        Package::insert(DatabaseFixture::package());
        Pricing::insert(DatabaseFixture::pricing());
    }

    public function mockTables()
    {
        return [
            Package::class,
            Currency::class,
            Pricing::class,
        ];
    }

    public function testGetReadable_FreePackage()
    {
        $package = Package::find(DatabaseFixture::$packageIdFixed);
        $package->update([
            'paytype' => 'free',
        ]);
        $expected = $package->pricing()->withCurrency(1)->first()->getReadable();
        $this->assertEquals([
            'cycle' => 'free',
            'recurring' => -1,
            'setup' => -1,
        ], $expected);
    }

    public function testGetReadable_MonthlyPackage()
    {
        $package = Package::find(DatabaseFixture::$packageIdFixed);
        $expected = $package->pricing()->withCurrency(1)->first()->getReadable();
        $this->assertEquals([
            'cycle' => 'monthly',
            'recurring' => 0,
            'setup' => 0,
        ], $expected);
    }

    public function testGetReadable_MonthlyPackage_GBPCurrency()
    {
        $package = Package::find(DatabaseFixture::$packageIdFixed);
        $expected = $package->pricing()->withCurrency(2)->first()->getReadable();
        $this->assertEquals([
            'cycle' => 'monthly',
            'recurring' => 10,
            'setup' => 5,
        ], $expected);
    }

    public function testGetReadable_QuarterlyPackage_GBPCurrency()
    {
        $package = Package::find(DatabaseFixture::$packageIdFixed);
        $pricing = $package->pricing()->withCurrency(2)->first();
        $pricing->update([
            'monthly' => -1,
            'quarterly' => 20,
        ]);

        $expected = $pricing->getReadable();
        $this->assertEquals([
            'cycle' => 'quarterly',
            'recurring' => 20,
            'setup' => 10,
        ], $expected);
    }

    public function testGetReadable_AnnuallyPackage_GBPCurrency()
    {
        $package = Package::find(DatabaseFixture::$packageIdFixed);
        $pricing = $package->pricing()->withCurrency(2)->first();
        $pricing->update([
            'monthly' => -1,
            'annually' => 30,
        ]);

        $expected = $pricing->getReadable();
        $this->assertEquals([
            'cycle' => 'annually',
            'recurring' => 30,
            'setup' => 20,
        ], $expected);
    }
}