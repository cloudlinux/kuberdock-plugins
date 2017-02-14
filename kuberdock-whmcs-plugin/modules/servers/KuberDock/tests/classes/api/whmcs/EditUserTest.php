<?php

namespace tests\api\whmcs;


use api\whmcs\EditUser;
use tests\fixtures\WhmcsApiFixture;
use tests\fixtures\DatabaseFixture;
use tests\TestCase;
use tests\EloquentMock;
use tests\InternalApiMock;
use tests\models\billing\ClientStub as Client;
use tests\models\billing\AdminStub as Admin;

class EditUserTest extends TestCase
{
    use EloquentMock;
    use InternalApiMock;

    private $vars;

    public function setUp()
    {
        parent::setUp();

        Client::create(DatabaseFixture::client());
        Admin::create(DatabaseFixture::admin());

        $this->vars = WhmcsApiFixture::getVars([
            'client_id' => 34,
            'email' => 'new@mail.com',
            'first_name' => "newfirstname",
            'last_name' => "newlastname",
            'active' => "True",
            'suspended' => "False",
            'rolename' => "User",
            'package' => "Standard package",
            'package_id' => 0,
        ]);

        // Mock local WHMCS API
        $this->internalApiMock();
    }

    public function mockTables()
    {
        return [
            Client::class,
            Admin::class,
        ];
    }

    public function testAnswer()
    {
        $result = EditUser::call($this->vars);

        $this->assertEquals('success', $result['result']);
        $this->assertEquals('User updated', $result['results']);

        $this->assertEquals(1, $this->calledTimes['updateclient']);
    }
}
