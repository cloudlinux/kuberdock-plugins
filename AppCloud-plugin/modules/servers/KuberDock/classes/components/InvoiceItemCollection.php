<?php


namespace components;


use models\addon\Resources;
use models\addon\resourceTypes\ResourceFactory;
use models\billing\Service;

class InvoiceItemCollection implements \IteratorAggregate
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
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
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
        });
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
            /* @var InvoiceItem $item */
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
}