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

class CL_Invoice extends CL_Model {
    const CUSTOM_INVOICE_DESCRIPTION = 'Custom invoice';

    const STATUS_PAID = 'Paid';
    const STATUS_UNPAID = 'Unpaid';

    /**
     *
     */
    public function setTableName()
    {
        $this->tableName = 'tblinvoices';
    }

    /**
     * @return array
     */
    public function relations()
    {
        return array(
            'invoiceitems' => array('\base\models\CL_InvoiceItems', 'invoiceid', array()),
        );
    }

    /**
     * @param int $userId
     * @param array $items
     * @param string $gateway
     * @param bool $autoApply
     * @param DateTime $dueDate
     * @param bool $sendInvoice
     * @return mixed
     * @throws Exception
     */
    public function createInvoice($userId, $items, $gateway, $autoApply = true, DateTime $dueDate = null, $sendInvoice = true)
    {
        $template = \base\models\CL_Configuration::model()->get()->Template;
        $currency = CL_Currency::model()->getDefaultCurrency();
        $admin = KuberDock_User::model()->getCurrentAdmin();

        $values['userid'] = $userId;
        $values['date'] = date('Ymd', time());
        $values['duedate'] = $dueDate ? $dueDate->format('Ymd') : date('Ymd', time());
        $values['paymentmethod'] = $gateway;
        $values['sendinvoice'] = $sendInvoice;

        $count = 0;
        $values['notes'] = '';
        foreach ($items as $item) {
            $count++;
            if (isset($item['description'])) {
                $title = $item['description'];
            } elseif ($item['type']=='IP' && $item['title']=='') {
                $title = 'Public IP';
            } else {
                $title = $item['type'] . ': ' . $item['title'];
            }
            $values['itemdescription' . $count] = $title;
            $values['itemamount' . $count] = $item['total'];

            if(isset($item['notes'])) {
                $values['itemnotes' . $count] = $item['notes'];
            }

            if ($template == 'kuberdock') {
                $values['notes'] .= '
                    <tr bgcolor="#fff">
                        <td align="center">' . $count . '</td>
                        <td align="left">'   . $title . '</td>
                        <td align="center">' . $item['qty'] . '</td>
                        <td align="center">' . $item['units'] . '</td>
                        <td align="center">' . $currency->getFullPrice($item['price']) . '</td>
                        <td align="center">' . $currency->getFullPrice($item['total']) . '</td>
                    </tr>
                ';
            }
        }

        $values['autoapplycredit'] = $autoApply;

        $results = localAPI('createinvoice', $values, $admin['username']);

        if($results['result'] != 'success') {
            throw new Exception($results['message']);
        }

        return $results['invoiceid'];
    }

    /**
     * @param int $userId
     * @param int $invoiceId
     * @param float $amountIn
     * @param float $amountOut
     * @param string $gateway
     * @param DateTime $date
     * @param string $description
     * @throws Exception
     */
    public function createTransaction($userId, $invoiceId, $amountIn, $amountOut, $gateway, DateTime $date = null, $description)
    {
        $admin = KuberDock_User::model()->getCurrentAdmin();

        $values['userid'] = $userId;
        $values['invoiceid'] = $invoiceId;
        $values['description'] = $description;
        $values['amountin'] = $amountIn;
        $values['amountout'] = $amountOut;
        $values['paymentmethod'] = $gateway;
        $values['date'] = $date ? $date->format('d/m/Y') : date('d/m/Y', time());

        $results = localAPI('addtransaction', $values, $admin['username']);

        if($results['result'] != 'success') {
            throw new Exception($results['message']);
        }
    }

    /**
     * @param int $clientId
     * @param float $amount
     * @param string $description
     * @return float
     * @throws Exception
     */
    public function addCredit($clientId, $amount, $description = '')
    {
        $admin = KuberDock_User::model()->getCurrentAdmin();
        
        $values['clientid'] = $clientId;
        $values['description'] = $description;
        $values['amount'] = $amount;

        $results = localAPI('addcredit', $values, $admin['username']);

        if($results['result'] != 'success') {
            throw new Exception($results['message']);
        }

        return $results['newbalance'];
    }

    /**
     * @param int $invoiceId
     * @param float $amount
     * @return mixed
     * @throws Exception
     */
    public function applyCredit($invoiceId, $amount)
    {
        $admin = KuberDock_User::model()->getCurrentAdmin();
        
        $values['invoiceid'] = $invoiceId;
        $values['amount'] = $amount;

        $results = localAPI('applycredit', $values, $admin['username']);

        if($results['result'] != 'success') {
            throw new Exception($results['message']);
        }

        return $results;
    }

    /**
     * @param int $invoiceId
     * @return mixed
     * @throws Exception
     */
    public function getInvoice($invoiceId)
    {
        $admin = KuberDock_User::model()->getCurrentAdmin();

        $values['invoiceid'] = $invoiceId;

        $results = localAPI('getinvoice', $values, $admin['username']);

        if($results['result'] != 'success') {
            throw new Exception($results['message']);
        }

        return $results;
    }

    /**
     * @param int $userId
     * @return mixed
     * @throws Exception
     */
    public function generateInvoices($userId)
    {
        $admin = KuberDock_User::model()->getCurrentAdmin();

        $values['clientid'] = $userId;

        $results = localAPI('geninvoices', $values, $admin['username']);

        if($results['result'] != 'success') {
            throw new Exception($results['message']);
        }

        return $results;
    }

    /**
     * @return bool
     */
    public function isPayed()
    {
        return ($this->status == self::STATUS_PAID || $this->subtotal == $this->credit);
    }

    /**
     * @return bool
     */
    public function isCustomInvoice()
    {
        return $this->invoiceitems['description'] == self::CUSTOM_INVOICE_DESCRIPTION;
    }

    /**
     * @return bool
     */
    public function isSetupInvoice()
    {
        return stripos($this->invoiceitems['description'], 'setup fee') !== false || $this->invoiceitems['type'] == 'Upgrade';
    }

    /**
     * @return bool
     */
    public function isUpdateKubesInvoice()
    {
        return stripos($this->invoiceitems['description'], \KuberDock_Pod::UPDATE_KUBES_DESCRIPTION) !== false;
    }

    /**
     * @return bool
     */
    public function isBillableItemInvoice()
    {
        return ($this->invoiceitems['type'] == CL_BillableItems::TYPE && $this->invoiceitems['relid'] > 0);
    }

    /**
     * Class loader
     *
     * @param string $className
     * @return $this
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
