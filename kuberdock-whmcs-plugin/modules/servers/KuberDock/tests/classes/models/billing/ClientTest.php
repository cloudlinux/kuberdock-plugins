<?php


namespace classes\models\addon;


use tests\EloquentMock;
use tests\fixtures\DatabaseFixture;
use tests\models\billing\CurrencyStub as Currency;
use tests\models\billing\ClientStub as Client;
use tests\TestCase;

class ClientTest extends TestCase
{
    use EloquentMock;

    public function setUp()
    {
        parent::setUp();

        Currency::insert(DatabaseFixture::currency());
        Client::create(DatabaseFixture::client());
    }

    public function mockTables()
    {
        return [
            Currency::class,
            Client::class,
        ];
    }

    public function testGetCurrent()
    {
        $client = (new Client())->getCurrent();
        $this->assertNull($client);

        $_SESSION['uid'] = DatabaseFixture::$userId;
        $client = (new Client())->getCurrent();
        $this->assertInstanceOf(Client::class, $client);
    }

    public function testGetSessionCurrency()
    {
        // Default
        $currency = (new Client())->getSessionCurrency();
        $this->assertEquals(1, $currency->id);

        // Logged user
        $_SESSION['uid'] = DatabaseFixture::$userId;
        $currency = (new Client())->getSessionCurrency();
        $this->assertEquals(1, $currency->id);

        // From session
        unset($_SESSION['uid']);
        $_SESSION['currency'] = 2;
        $currency = (new Client())->getSessionCurrency();
        $this->assertEquals(2, $currency->id);
    }
}