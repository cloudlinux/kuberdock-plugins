<?php

namespace components;

use base\models\CL_Invoice;
use base\models\CL_InvoiceItems;

class Deposit extends \base\CL_Component
{
    /**
     *
     */
    const INVOICE_ITEM_TYPE = 'Deposit';

    /**
     * Adds Deposit value to setup fee if needed
     *
     * @param $params
     * @return array|null
     * @throws \Exception
     */
    public function pricingOverride($params)
    {
        $pid = $params['pid'];
        $product = \KuberDock_Product::model()->loadById($pid);

        if (!$product->isKuberProduct()) {
            return null;
        }

        try {
            $deposit = $this->getDeposit($product);
        } catch (\Exception $e) {
            $deposit = 0;
        }

        $pricing = $product->getPricing();
        $setupFee = ($pricing['setup'] > 0) ? $pricing['setup'] : 0;

        $recurring = 0;
        $predefinedApp = \KuberDock_Addon_PredefinedApp::model()->loadBySessionId();
        // Price for yaml PA
        if ($predefinedApp && !$predefinedApp->getPod() && $product->isFixedPrice()) {
            $recurring = $predefinedApp->getTotalPrice(true);
        }

        $recurring = ($pricing['recurring'] > 0) ? $pricing['recurring'] + $recurring : $recurring;

        return array(
            'setup' => $deposit + $setupFee,
            'recurring' => $recurring,
        );
    }

    /**
     * @param $invoiceId
     */
    public function createInvoiceItem($invoiceId)
    {
        try {
            $invoice = $this->getInvoice($invoiceId);
            $product = $this->getProduct($invoice);
            $deposit = $this->getDeposit($product);
        } catch (\Exception $e) {
            return;
        }

        $invoiceItemsModel = CL_InvoiceItems::model();
        $invoiceItems = $invoiceItemsModel->loadByAttributes(array(
            'invoiceid' => $invoiceId,
            'type' => 'Setup'
        ));
        $setupItem = $invoiceItemsModel->loadByParams(current($invoiceItems));
        $setupItem->amount -= $deposit;
        $setupItem->save();

        $depositItem = new CL_InvoiceItems;
        $depositItem->setAttributes($setupItem->getAttributes());
        unset($depositItem->id);
        $depositItem->description = $product->getName() . ' First deposit';
        $depositItem->amount = $deposit;
        $depositItem->type = self::INVOICE_ITEM_TYPE;
        $depositItem->save();
    }

    /**
     * @param int $invoiceId
     */
    public function addToBalance($invoiceId)
    {
        $this->changeBalance($invoiceId, 'Add');
    }

    /**
     * @param int $invoiceId
     */
    public function removeFromBalance($invoiceId)
    {
        $this->changeBalance($invoiceId, 'Remove');
    }

    /**
     * @param int $invoiceId
     * @param string $where
     */
    private function changeBalance($invoiceId, $where)
    {
        try {
            $invoice = $this->getInvoice($invoiceId);
            $product = $this->getProduct($invoice);
            $deposit = $this->getDeposit($product);
        } catch (\Exception $e) {
            return;
        }

        try {
            if ($where == 'Remove') {
                $deposit = -$deposit;
            }
            $description = $where . ' first deposit funds. Invoice: ' . $invoice->id;
            CL_Invoice::model()->addCredit($invoice->userid, $deposit, $description);
        } catch(\Exception $e) {
            \exceptions\CException::log($e);
        }
    }

    /**
     * @param \KuberDock_Product $product
     * @return float
     * @throws \Exception
     */
    private function getDeposit(\KuberDock_Product $product)
    {
        $deposit = $product->getConfigOption('firstDeposit')
            ? $product->getConfigOption('firstDeposit')
            : 0;

        if ($deposit == 0) {
            throw new \Exception;
        }

        return $deposit;
    }

    /**
     * @param $invoice
     * @return $this
     * @throws \Exception
     */
    private function getProduct($invoice)
    {
        $product = $invoice->getProductBySetupInvoice();
        if (!$product) {
            throw new \Exception;
        }

        $product = \KuberDock_Product::model()->loadByParams($product);
        if (!$product->isKuberProduct()) {
            throw new \Exception;
        }

        // Only PayG can take deposit
        if ($product->isFixedPrice()) {
            throw new \Exception;
        }

        return $product;
    }

    /**
     * @param $invoiceId
     * @return $this
     * @throws \Exception
     */
    private function getInvoice($invoiceId)
    {
        $invoice = CL_Invoice::model()->loadById($invoiceId);
        if (!$invoice) {
            throw new \Exception;
        }

        return $invoice;
    }


}