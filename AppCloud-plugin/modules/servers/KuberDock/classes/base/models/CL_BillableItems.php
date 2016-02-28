<?php

namespace base\models;

use base\CL_Tools;
use Exception;
use base\CL_Model;
use base\models\CL_Invoice;
use base\models\CL_InvoiceItems;

/**
 * Class CL_BillableItems
 * @package base\models
 */
class CL_BillableItems extends CL_Model {
    const TYPE = 'Item';

    const CYCLE_DAY = 'Days';
    const CYCLE_WEEK = 'Weeks';
    const CYCLE_MONTH = 'Months';
    const CYCLE_YEAR = 'Years';

    const CREATE_NO_INVOICE = 'noinvoice';
    const CREATE_INVOICE_NEXT_CRON = 'nextcron';
    const CREATE_NEXT_INVOICE = 'nextinvoice';
    const CREATE_DUE_DATE = 'duedate';
    const CREATE_RECUR = 'recur';

    const CREATE_NO_INVOICE_ID = '0';
    const CREATE_INVOICE_NEXT_CRON_ID = '1';
    const CREATE_NEXT_INVOICE_ID = '2';
    const CREATE_DUE_DATE_ID = '3';
    const CREATE_RECUR_ID = '4';

    /**
     *
     */
    public function setTableName()
    {
        $this->tableName = 'tblbillableitems';
    }

    /**
     * @throws Exception
     */
    public function addItem()
    {
        $admin = \KuberDock_User::model()->getCurrentAdmin();

        unset($this->id);
        $values = $this->getAttributes();

        $results = localAPI('addbillableitem', $values, $admin['username']);

        if($results['result'] != 'success') {
            throw new Exception($results['message']);
        }
    }

    /**
     * @param int $invoiceId
     * @return $this|null
     */
    public function getByInvoice($invoiceId)
    {
        $data = CL_InvoiceItems::model()->loadByAttributes(array(
            'invoiceid' => $invoiceId,
            'type' => self::TYPE,
        ), 'relid > 0');

        $invoice = current($data);

        $model = $this->loadById($invoice['relid']);

        if(!$data) {
            return null;
        }

        $model->invoice = CL_Invoice::model()->loadById($invoiceId);

        return $model;
    }

    /**
     * @return CL_InvoiceItems|bool
     */
    public function getLastInvoice()
    {
        $data = CL_InvoiceItems::model()->loadByAttributes(array(
            'type' => self::TYPE,
            'userid' => $this->userid,
            'relid' => $this->id,
        ), '', array(
            'order' => 'id DESC',
            'limit' => 1,
        ));

        if($data) {
            $data = current($data);
            $model = CL_InvoiceItems::model()->loadByParams($data);
            $model->invoice = CL_Invoice::model()->loadById($data['invoiceid']);

            return $model;
        } else {
            return false;
        }
    }

    public function getProRate()
    {
        $dueDate = CL_Tools::sqlDateToDateTime($this->duedate);
        $now = new \DateTime();
        $daysRemain = $dueDate->diff($now)->format('%a');
        $totalDays = $this->getRecurDays();

        return $daysRemain / $totalDays;
    }

    public function getNextDueDate()
    {
        $current = $this->duedate ? CL_Tools::sqlDateToDateTime($this->duedate) : new \DateTime();
        $nextDate = clone($current);
        $days = $this->getRecurDays();
        $nextDate->modify(sprintf('+%s days', $days));
        $nextDate->setDate($nextDate->format('Y'), $nextDate->format('m'), $current->format('d'));

        return $nextDate;
    }

    public function getRecurDays()
    {
        switch($this->recurcycle) {
            case self::CYCLE_MONTH:
                $totalDays = $this->recur * 30;
                break;
            case self::CYCLE_YEAR:
                $totalDays = $this->recur * 365;
                break;
            default:
                $totalDays = $this->recur * 30;
                break;
        }

        return $totalDays;
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