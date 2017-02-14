<?php


namespace classes\models\addon;


use tests\EloquentMock;
use tests\fixtures\DatabaseFixture;
use tests\models\billing\CurrencyStub as Currency;
use tests\TestCase;

class CurrencyTest extends TestCase
{
    use EloquentMock;

    public function setUp()
    {
        parent::setUp();

        Currency::insert(DatabaseFixture::currency());
    }

    public function mockTables()
    {
        return [
            Currency::class,
        ];
    }

    public function testRatedPrice()
    {
        // USD
        $currency = Currency::find(1);
        $this->assertEquals(1, $currency->getRatedPrice(1));

        // GBP
        $currency = Currency::find(2);
        $this->assertEquals(1, $currency->getRatedPrice(2));
    }

    public function testGetFullPrice()
    {
        // USD
        $currency = Currency::find(1);
        $this->assertEquals('$1.00 USD', $currency->getFullPrice(1));

        // GBP
        $currency = Currency::find(2);
        $this->assertEquals('£1.00 GBP', $currency->getFullPrice(2));
    }
}