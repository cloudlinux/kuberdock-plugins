<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace base\models;

use DateTime;
use Exception;
use KuberDock_User;
use base\CL_Model;
use models\billing\Admin;

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
        $admin = Admin::getCurrent();
        $values['serviceid'] = $id;
        $results = localAPI('updateclientproduct', $values, $admin->username);

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
        $admin = Admin::getCurrent();
        $values['password2'] = $password ? $password : $this->password;

        $results = localAPI('decryptpassword', $values, $admin->username);

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
        $admin = Admin::getCurrent();
        $values['password2'] = $password;

        $results = localAPI('encryptpassword', $values, $admin->username);

        if($results['result'] != 'success') {
            throw new Exception($results['message']);
        }

        return $results['password'];
    }

    /**
     * @param $userId
     * @param DateTime $dueDate
     * @param array $items
     * @param bool $autoApply
     * @return null
     */
    public function addInvoice($userId, DateTime $dueDate, $items, $autoApply = true)
    {
        $client = KuberDock_User::model()->getClientDetails($userId);
        $gateway = $client['client']['defaultgateway'] ? $client['client']['defaultgateway'] : $this->paymentmethod;
        $invoice = CL_Invoice::model();

        $result = $invoice->createInvoice($userId, $items, $gateway, $autoApply, $dueDate);

        return $result;
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
        $admin = Admin::getCurrent();
        $values['amount'] = $amount;
        $values['invoiceid'] = $invoiceId;

        $results = localAPI('addinvoicepayment', $values, $admin->username);

        return ($results['result'] == 'success');
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function createModule()
    {
        $admin = Admin::getCurrent();
        $values['accountid'] = $this->id;

        $results = localAPI('modulecreate', $values, $admin->username);

        if ($results['result'] != 'success') {
            throw new Exception($results['message']);
        }

        return true;
    }

    /**
     * @param string $reason
     * @return bool
     * @throws Exception
     */
    public function suspendModule($reason = '')
    {
        $admin = Admin::getCurrent();
        $values['accountid'] = $this->id;
        $values['suspendreason'] = $reason;

        $results = localAPI('modulesuspend', $values, $admin->username);

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
        $admin = Admin::getCurrent();
        $values['accountid'] = $this->id;

        $results = localAPI('moduleunsuspend', $values, $admin->username);

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
        $admin = Admin::getCurrent();
        $values['accountid'] = $this->id;

        $results = localAPI('moduleterminate', $values, $admin->username);

        if($results['result'] != 'success') {
            throw new Exception($results['message']);
        }

        return true;
    }
} 