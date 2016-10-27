<?php


namespace components;


use models\addon\Resources;
use models\addon\State;
use models\billing\Service;

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
     * @param Service $service
     */
    public function filterPaidResources(Service $service)
    {
        // Walk through items and divide PD\IP resources which already used
        $this->data = array_filter($this->data, function ($item) use ($service) {
            /* @var InvoiceItem $item
             * @var Resources $resource
             */
            switch ($item->getType()) {
                case Resources::TYPE_PD:
                    $resource = Resources::notDeleted($service->userid)->where('name', $item->getName())
                        ->typePd()->first();

                    if (!$resource) {
                        return true;
                    }

                    if ($resource) {
                        $resource->divide($item);
                    }

                    return false;
                case Resources::TYPE_IP:
                    $ipStat = $service->getApi()->getIpPoolStat()->getData();
                    $resource = Resources::notDeleted($service->userid)->where('name', count($ipStat) + 1)
                        ->typeIp()->first();

                    if (!$resource) {
                        return true;
                    }

                    if ($resource->isActive()) {
                        $resource->divide($item);
                    }

                    return false;
            }

            return true;
        });
    }

    /**
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
}