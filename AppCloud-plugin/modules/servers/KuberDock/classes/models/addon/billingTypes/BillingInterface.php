<?php


namespace models\addon\billingTypes;


use models\addon\Item;
use models\addon\resourceTypes\Pod;
use models\addon\resourceTypes\ResourceFactory;
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
     * @param Item $item
     * @return Pod
     */
    public function afterOrderPayment(Item $item);

    /**
     * Runs after edit pod payment
     * @param Item $item
     * @return Pod
     */
    public function afterEditPayment(Item $item);

    /**
     * Runs after PA switch package(plan) payment
     * @param Item $item
     * @return Pod
     */
    public function afterSwitchPayment(Item $item);
}