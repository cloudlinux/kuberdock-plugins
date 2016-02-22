<?php

use base\CL_Model;
use base\models\CL_Invoice;
use base\models\CL_BillableItems;
use exceptions\CException;

/**
 * Class KuberDock_Addon_Items
 */
class KuberDock_Addon_Items extends CL_Model {
    const STATUS_DELETED = 'Deleted';

    /**
     *
     */
    public function setTableName()
    {
        $this->tableName = 'KuberDock_items';
    }

    /**
     * @param int $invoiceId
     * @return $this|null
     */
    public function loadByInvoice($invoiceId)
    {
        $data = $this->loadByAttributes(array(
            'invoice_id' => $invoiceId
        ));

        if(!$data) {
            return null;
        }

        return $this->loadByParams(current($data));
    }

    /**
     * @throws Exception
     */
    public function checkStatus()
    {
        $config = \base\models\CL_Configuration::model()->get();
        $billableItem = CL_BillableItems::model()->loadById($this->billable_item_id);
        $invoice = CL_Invoice::model()->loadById($this->invoice_id);
        $dueDate = new DateTime($invoice->duedate);
        $currentDate = new DateTime();

        if($billableItem && $currentDate->diff($dueDate)->format('%a') >= $config->AutoSuspensionDays) {
            $service = KuberDock_Hosting::model()->loadById($this->service_id);
            if($this->pod_id) {
                try {
                    $service->getApi()->updatePod($this->pod_id, array(
                        'command' => 'stop',
                        'status' => 'unpaid',
                    ));
                } catch(\Exception $e) {
                    //
                }
            }
            $billableItem->invoiceaction = $billableItem::CREATE_NO_INVOICE_ID;
            $billableItem->save();
            $this->status = self::STATUS_DELETED;
            $this->save();
        }
    }

    /**
     * @return bool
     */
    public function isPayed()
    {
        return $this->status == CL_Invoice::STATUS_PAID;
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