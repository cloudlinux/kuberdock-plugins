<?php

namespace api\whmcs;


use components\BillingApi;

class EditUser extends Api
{
    protected function getRequiredParams()
    {
        return [
            'client_id',
            'email',
            'first_name',
            'last_name',
            'suspended',
            'active',
            'rolename',
            'package',
            'package_id',
        ];
    }

    public function answer()
    {
        BillingApi::editUser([
            'clientid' => $this->getParam('client_id'),
            'email' => $this->getParam('email'),
            'firstname' => $this->getParam('first_name'),
            'lastname' => $this->getParam('last_name'),
        ]);

        return 'User updated';
    }
}