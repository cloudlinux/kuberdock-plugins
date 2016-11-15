<?php


namespace models\addon\billing;


use components\InvoiceItemCollection;
use models\addon\Item;
use models\addon\ItemInvoice;
use models\addon\resource\Pod;
use models\addon\resource\ResourceFactory;
use models\billing\Invoice;
use models\billing\Service;

interface BillingInterface
{
    /**
     * Order predefined app
     * @param ResourceFactory $resource
     * @param Service $service
     * @return Invoice
     */
    public function order(ResourceFactory $resource, Service $service);

    /**
     * @param Pod $pod
     * @param Service $service
     * @param string $type (ItemInvoice::getTypes())
     * @return Invoice
     */
    public function processApiOrder(Pod $pod, Service $service, $type);

    /**
     * Runs after order pod\PA payment
     * @param ItemInvoice $itemInvoice
     * @return Pod
     */
    public function afterOrderPayment(ItemInvoice $itemInvoice);

    /**
     * Runs after edit pod payment
     * @param ItemInvoice $itemInvoice
     * @return Pod
     */
    public function afterEditPayment(ItemInvoice $itemInvoice);

    /**
     * Runs after PA switch package(plan) payment
     * @param ItemInvoice $itemInvoice
     * @return Pod
     */
    public function afterSwitchPayment(ItemInvoice $itemInvoice);

    /**
     * @param Service $service
     */
    public function afterModuleCreate(Service $service);

    /**
     * @param Service $service
     * @return InvoiceItemCollection
     */
    public function firstInvoiceCorrection(Service $service);

    /**
     * @param ItemInvoice $itemInvoice
     * @return void
     */
    public function beforePayment(ItemInvoice $itemInvoice);
}