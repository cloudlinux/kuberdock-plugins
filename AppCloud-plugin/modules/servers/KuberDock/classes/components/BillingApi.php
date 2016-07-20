<?php


namespace components;


use models\billing\Admin;

class BillingApi extends Component
{
    /**
     * @param string $method
     * @param array $values
     * @param string $user
     * @return array
     * @throws \Exception
     */
    public static function request($method, $values, $user)
    {
        $results = localAPI($method, $values, $user);

        if ($results['result'] != 'success') {
            throw new \Exception($results['message']);
        }

        return $results;
    }

    /**
     * @param string $password
     * @return string
     * @throws \Exception
     */
    public function decryptPassword($password)
    {
        $admin = Admin::getCurrent();

        $response = BillingApi::request('decryptpassword', array(
            'password2' => $password,
        ), $admin->username);

        return $response['password'];
    }
}