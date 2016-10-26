<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace base\models;

use DateTime;
use Exception;
use exceptions\CException;
use KuberDock_User;
use base\CL_Model;
use components\KuberDock_InvoiceItem;
use models\billing\Admin;

/**
 * Class CL_Invoice
 * @package base\models
 */
class CL_Invoice extends CL_Model {
    /**
     * It seems, it is used if user upgrades or downgrades a product and there is a deposit in new product
     */
    const CUSTOM_INVOICE_DESCRIPTION = 'Custom invoice';
    /**
     *
     */
    const FIRST_DEPOSIT_DESCRIPTION = 'First deposit';

    const STATUS_PAID = 'Paid';
    const STATUS_UNPAID = 'Unpaid';
    const STATUS_DELETED = 'Deleted';
    const STATUS_CANCELLED = 'Cancelled';

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
     * @param KuberDock_InvoiceItem[] $items
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

        $values['userid'] = $userId;
        $values['date'] = date('Ymd', time());
        $values['duedate'] = $dueDate ? $dueDate->format('Ymd') : date('Ymd', time());
        $values['paymentmethod'] = $gateway;
        $values['sendinvoice'] = $sendInvoice;

        $count = 0;
        $values['notes'] = '';
        foreach ($items as $item) {
            if ($item->getTotal()==0) {
                continue;
            }

            $count++;

            $values['itemdescription' . $count] = $item->getDescription();
            $values['itemamount' . $count] = $item->getTotal();

            if ($item->getTaxed()) {
                $values['itemtaxed' . $count] = true;
            }

            if (!$item->isShort() && $template == 'kuberdock') {
                $values['notes'] .= $item->getHtml($count);
            }
        }

        $values['autoapplycredit'] = $autoApply;

        $admin = Admin::getCurrent();
        $results = localAPI('createinvoice', $values, $admin->username);

        if ($results['result'] != 'success') {
            throw new Exception($results['message']);
        }

        return $results['invoiceid'];
    }

    /**
     * @param KuberDock_InvoiceItem[] $items
     * @param DateTime $dueDate
     * @return mixed
     * @throws Exception
     */
    public function updateInvoice($items, DateTime $dueDate = null)
    {
        $template = \base\models\CL_Configuration::model()->get()->Template;

        $values['invoiceid'] = $this->id;
        $values['date'] = date('Ymd', time());
        $values['duedate'] = $dueDate ? $dueDate->format('Ymd') : date('Ymd', time());

        $values['notes'] = '';

        $data = CL_InvoiceItems::model()->loadByAttributes(array(
            'invoiceid' => $this->id,
        ));
        array_map(function ($e) use (&$values) {
            $values['deletelineids'][$e['id']] = $e['id'];
        }, $data);

        foreach ($items as $k => $item) {
            if ($item->getTotal() == 0) {
                continue;
            }

            $values['newitemdescription'][$k] = $item->getDescription();
            $values['newitemamount'][$k] = $item->getTotal();
            $values['newitemtaxed'][$k] = (int) $item->getTaxed();

            if (!$item->isShort() && $template == 'kuberdock') {
                $values['notes'] .= $item->getHtml($k);
            }
        }

        $admin = Admin::getCurrent();
        $results = localAPI('updateinvoice', $values, $admin->username);

        if ($results['result'] != 'success') {
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
        $admin = Admin::getCurrent();

        $values['userid'] = $userId;
        $values['invoiceid'] = $invoiceId;
        $values['description'] = $description;
        $values['amountin'] = $amountIn;
        $values['amountout'] = $amountOut;
        $values['paymentmethod'] = $gateway;
        $values['date'] = $date ? $date->format('d/m/Y') : date('d/m/Y', time());

        $results = localAPI('addtransaction', $values, $admin->username);

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
        $admin = Admin::getCurrent();
        
        $values['clientid'] = $clientId;
        $values['description'] = $description;
        $values['amount'] = $amount;

        $results = localAPI('addcredit', $values, $admin->username);

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
        $admin = Admin::getCurrent();
        
        $values['invoiceid'] = $invoiceId;
        $values['amount'] = $amount;

        $results = localAPI('applycredit', $values, $admin->username);

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
        $admin = Admin::getCurrent();

        $values['invoiceid'] = $invoiceId;

        $results = localAPI('getinvoice', $values, $admin->username);

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
        $admin = Admin::getCurrent();

        $values['clientid'] = $userId;

        $results = localAPI('geninvoices', $values, $admin->username);

        if($results['result'] != 'success') {
            throw new Exception($results['message']);
        }

        return $results;
    }

    /**
     * @param int $orderId
     * @return $this
     * @throws CException
     */
    public function loadByOrderId($orderId)
    {
        $data = $this->_db->query('select i.* from tblinvoices i left join tblorders o on o.invoiceid=i.id where o.id = ?', array(
            $orderId,
        ))->getRows();

        if (!$data) {
            throw new CException('Unable load invoice by orderID = ' . $orderId);
        }

        return $this->loadByParams(current($data));
    }

    /**
     * @return bool
     */
    public function isPayed()
    {
        return ($this->status == self::STATUS_PAID || $this->getSum() == $this->credit);
    }

    /**
     * Sum, which user have to pay for invoice. Consists of subtotal plus all taxes
     *
     * @return float
     */
    public function getSum()
    {
        return $this->subtotal + $this->tax + $this->tax2;
    }

    /**
     * @return bool
     */
    public function isUpdateKubesInvoice()
    {
        // TODO: implement invoice type
        return stripos($this->invoiceitems['description'], \KuberDock_Pod::UPDATE_KUBES_DESCRIPTION) === 0
            || stripos($this->invoiceitems['description'], 'Change kube type') === 0;
    }

    /**
     * @return bool
     */
    public function isBillableItemInvoice()
    {
        return ($this->invoiceitems['type'] == CL_BillableItems::TYPE && $this->invoiceitems['relid'] > 0);
    }

    /**
     * Add first deposit
     * @param bool $remove
     */
    public function addFirstDeposit($remove = false)
    {
        if (!$this->getProduct()) {
            return;
        }

        foreach ($this->getInvoiceItems() as $row) {
            if ($row['description'] == self::FIRST_DEPOSIT_DESCRIPTION && $row['amount']) {
                if ($remove) {
                    $this->addCredit($this->userid, -$row['amount'], 'Remove funds for first deposit');
                } else {
                    $this->addCredit($this->userid, $row['amount'], 'Add funds for first deposit');
                }
            }
        }
    }

    /**
     *
     */
    public function removeFirstDeposit()
    {
        $this->addFirstDeposit(true);
    }

    /**
     * @return array
     */
    public function getInvoiceItems()
    {
        return CL_InvoiceItems::model()->loadByAttributes(array(
            'invoiceid' => $this->id,
        ));
    }

    /**
     * @return \KuberDock_Product|null
     */
    public function getProduct()
    {
        $sql = "SELECT p.* FROM tblhosting h 
            LEFT JOIN tblinvoiceitems it ON it.relid=h.id 
            LEFT JOIN tblproducts p ON p.id=h.packageid 
            WHERE it.type = 'Hosting' AND p.servertype = 'KuberDock' AND it.invoiceid = :invoice_id";

        $data = $this->_db->query($sql, array(
            ':invoice_id' => $this->id,
        ))->getRow();

        if ($data) {
            return \KuberDock_Product::model()->loadByParams($data);
        } else {
            return null;
        }
    }

    public function getProductBySetupInvoice()
    {
        $sql = "
            SELECT p.*, h.id as service_id
            FROM tblinvoiceitems i 
            INNER JOIN tblhosting h ON i.relid = h.id 
            INNER JOIN tblproducts p ON h.packageid = p.id 
            WHERE i.type = 'Setup' 
                AND i.invoiceid = '{$this->id}' 
                AND p.servertype = 'KuberDock';";
        
        return current($this->_db->query($sql)->getRows());
    }

    public function activateProductByInvoice()
    {
        $sql = "
            UPDATE tblhosting h 
            INNER JOIN tblproducts p ON h.packageid = p.id 
            INNER JOIN tblinvoiceitems i ON h.id = i.relid 
            SET h.domainstatus='Active' 
            WHERE (i.type = 'Setup' OR i.type='Hosting')  AND i.invoiceid = '{$this->id}' AND p.servertype='KuberDock';";

        return $this->_db->query($sql);
    }
}
