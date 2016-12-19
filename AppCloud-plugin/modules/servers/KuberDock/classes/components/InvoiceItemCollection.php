<?php


namespace components;


use models\addon\Item;
use models\addon\resource\Pod;
use models\addon\resource\ResourceFactory;
use models\addon\Resources;
use models\addon\State;
use models\billing\Service;
use models\Model;

class InvoiceItemCollection implements \IteratorAggregate, \JsonSerializable
{
    /**
     * @var array
     */
    protected $data = [];
    /**
     * @var string
     */
    protected $description;
    /**
     * @var Item
     */
    protected $item;
    /**
     * @var ResourceFactory $resource
     */
    protected $resource;

    /**
     *
     */
    public function __clone()
    {
        array_walk($this->data, function (&$item) {
            $item = clone $item;
        });
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->data;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * @param InvoiceItem $item
     * @return $this
     */
    public function add(InvoiceItem $item)
    {
        $this->data[] = $item;

        return $this;
    }

    /**
     * @param Item $item
     */
    public function setItem(Item $item)
    {
        $this->item = $item;
    }

    /**
     * @param ResourceFactory $resource
     */
    public function setResource(ResourceFactory $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @return float
     */
    public function sum()
    {
        return array_reduce($this->data, function ($carry, $item) {
            /* @var InvoiceItem $item */
            $carry += $item->getTotal();
            return $carry;
        }, 0);
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Walk through items and divide PD\IP resources which already used (Fixed)
     *
     * @param Service $service
     */
    public function filterPaidResources(Service $service)
    {
        $this->data = array_filter($this->data, function ($invoiceItem) use ($service) {
            /**
             * @var InvoiceItem $invoiceItem
             * @var Resources $resource
             * @var Item $item
             */
            switch ($invoiceItem->getType()) {
                case Resources::TYPE_PD:
                    return $this->filterPaidPD($invoiceItem, $service);
                case Resources::TYPE_IP:
                    return $this->filterPaidIP($invoiceItem, $service);
            }

            return true;
        });
    }

    /**
     * @param InvoiceItem $invoiceItem
     * @param Service $service
     * @return bool
     */
    public function filterPaidPD(InvoiceItem $invoiceItem, Service $service)
    {
        /* @var Resources $resource */
        $resource = Resources::notDeleted($service->userid)->where('name', $invoiceItem->getName())
            ->typePd()->first();

        if (!$resource || !$resource->isActive()) {
            return true;
        }

        return $this->processFilterPaidResources($invoiceItem, $resource);
    }

    /**
     * @param InvoiceItem $invoiceItem
     * @param Service $service
     * @return bool
     */
    public function filterPaidIP(InvoiceItem $invoiceItem, Service $service)
    {
        /* @var Resources $resource */
        $ipStat = $service->getApi()->getIpPoolStat()->getData();
        $total = count($ipStat);

        // Order new pod with public IP
        if (!$this->item) {
            $total++;
        }

        $resource = Resources::notDeleted($service->userid)->where('name', $total)
            ->typeIp()->first();

        if (!$resource || !$resource->isActive()) {
            return true;
        }

        return $this->processFilterPaidResources($invoiceItem, $resource);
    }

    /**
     * Filter paid resources (PAYG)
     * @param State $state
     */
    public function filterPaidState(State $state)
    {
        $this->data = array_filter($this->data, function ($item) use ($state) {
            foreach ($state->details as $paidItem) {
                /* @var InvoiceItem $item */
                $sameName = $item->getName() == $paidItem['name'];
                $sameUnits = $item->getUnits() == $paidItem['units'];
                $sameDescription = $item->getCustomDescription() == $paidItem['customDescription'];

                if ($sameName && $sameUnits && $sameDescription) {
                    if ($item->getQty() <= $paidItem['qty']) {
                        return false;
                    } else {
                        $item->setQty($item->getQty() - $paidItem['qty']);
                        return $item;
                    }
                }
            }

            return true;
        });
    }

    /**
     * @param InvoiceItem $invoiceItem
     * @param Resources $resource
     * @return bool
     */
    protected function processFilterPaidResources(InvoiceItem $invoiceItem, Resources $resource)
    {
        $paidDeleted = $resource->resourcePods()->paidDeletedItems()->first();

        if ($paidDeleted) {
            $prorate = $paidDeleted->items->first()->getProrate();

            // current day
            if ($prorate == 0) {
                return false;
            }

            $invoiceItem->proratePrice($prorate);

            return true;
        }

        if (!$this->item || ($this->item && $this->item->pod_id !== $this->resource->id)) {
            $resource->divide($invoiceItem);
            return false;
        }

        return true;
    }
}