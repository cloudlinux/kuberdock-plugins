<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

class CL_Hosting extends CL_Model {

    /**
     *
     */
    public function setTableName()
    {
        $this->tableName = 'tblhosting';
    }

    /**
     * @param $id
     * @param array $values
     * @throws Exception
     */
    public function updateByApi($id, $values = array())
    {
        $admin = CL_User::model()->getCurrentAdmin();
        $values['serviceid'] = $id;
        $results = localAPI('updateclientproduct', $values, $admin['name']);

        if(($results['result'] != 'success')) {
            throw new Exception($results['message']);
        }
    }

    /**
     * @param $username
     * @return string
     */
    public function generateLogin($username)
    {
        $username = strtolower(JTransliteration::transliterate($username));
        return $username;
    }

    /**
     * @param string $password
     * @return string
     * @throws Exception
     */
    public function decryptPassword($password = null)
    {
        $admin = CL_User::model()->getCurrentAdmin();
        $values['password2'] = $password ? $password : $this->password;

        $results = localAPI('decryptpassword', $values, $admin['name']);

        if($results['result'] != 'success') {
            throw new Exception($results['message']);
        }

        return $results['password'] ? $results['password'] : $password;
    }

    /**
     * @param $password
     * @return string
     * @throws Exception
     */
    public function encryptPassword($password)
    {
        $admin = CL_User::model()->getCurrentAdmin();
        $values['password2'] = $password;

        $results = localAPI('encryptpassword', $values, $admin['name']);

        if($results['result'] != 'success') {
            throw new Exception($results['message']);
        }

        return $results['password'];
    }

    /**
     * @param $userId
     * @param DateTime $dueDate
     * @param float $amount
     * @param bool $autoApply
     * @return null
     */
    public function addInvoice($userId, DateTime $dueDate, $amount, $autoApply = true)
    {
        $client = KuberDock_User::model()->getClientDetails($userId);
        $gateway = $client['client']['defaultgateway'] ? $client['client']['defaultgateway'] : $this->paymentmethod;
        $invoice = CL_Invoice::model();

        return $invoice->createInvoice($userId, $amount, $gateway, $autoApply, 'Auto invoice', $dueDate);
    }

    /**
     * @param $invoiceId
     * @return bool
     */
    public function isInvoicePaid($invoiceId)
    {
        $invoice = CL_Invoice::model()->getInvoice($invoiceId);
        return $invoice['status'] == CL_Invoice::STATUS_PAID;
    }
    
    /**
     * @param int $invoiceId
     * @param int $amount
     * @return bool
     */
    public function addPayment($invoiceId, $amount)
    {
        $admin = CL_User::model()->getCurrentAdmin();
        $adminuser = $admin['name'];
        $values['amount'] = $amount;
        $values['invoiceid'] = $invoiceId;

        $results = localAPI('addinvoicepayment', $values, $adminuser);

        return ($results['result'] == 'success');
    }

    /**
     * @param string $reason
     * @return bool
     * @throws Exception
     */
    public function suspendModule($reason = '')
    {
        $admin = CL_User::model()->getCurrentAdmin();
        $values['accountid'] = $this->id;
        $values['suspendreason'] = $reason;

        $results = localAPI('modulesuspend', $values, $admin['name']);

        if($results['result'] != 'success') {
            throw new Exception($results['message']);
        }

        return true;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function unSuspendModule()
    {
        $admin = CL_User::model()->getCurrentAdmin();
        $values['accountid'] = $this->id;

        $results = localAPI('moduleunsuspend', $values, $admin['name']);

        if($results['result'] != 'success') {
            throw new Exception($results['message']);
        }

        return true;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function terminateModule()
    {
        $admin = CL_User::model()->getCurrentAdmin();
        $values['accountid'] = $this->id;

        $results = localAPI('moduleterminate', $values, $admin['name']);

        if($results['result'] != 'success') {
            throw new Exception($results['message']);
        }

        return true;
    }

    /**
     * Class loader
     *
     * @param string $className
     * @return CL_Hosting
     */
    public static function model($className = __CLASS__)
    {
        if(isset(self::$_models[$className])) {
            return self::$_models[$className];
        } else {
            self::$_models[$className] = new $className;
            return self::$_models[$className];
        }
    }
} 