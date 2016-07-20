<?php

namespace components;

use base\models\CL_Currency;
use exceptions\CException;
use models\addon\Resources;

class KuberDock_InvoiceItem
{
    /** @var bool Short invoices have only description and total. No units, quantity and price */
    private $short = false;

    /**
     * @var string
     */
    private $units;
    /**
     * @var string
     */
    private $description;
    /**
     * @var bool
     */
    private $taxed = false;
    /**
     * @var float
     */
    private $price;
    /**
     * @var string
     */
    private $name;
    /**
     * @var int
     */
    private $qty;
    /**
     * @var string Resources::getTypes
     */
    private $type;

    /**
     * @param string $description
     * @param float $price
     * @param string $units
     * @param int $qty
     */
    public function __construct($description, $price, $units, $qty)
    {
        $this->description = $this->formatDescription($description);
        $this->price = $price;
        $this->units = $units;
        $this->qty = $qty;
    }

    /**
     * @param string $description
     * @param float $price
     * @param null $units
     * @param int $qty
     * @param string $type
     * @return KuberDock_InvoiceItem
     */
    public static function create($description, $price, $units = null, $qty = 1, $type = Resources::TYPE_POD)
    {
        $object = new self($description, $price, $units, $qty);
        $object->setType($type);

        if (is_null($units)) {
            $object->setShort();
        }

        return $object;
    }

    /**
     * @param bool|true $short
     * @return $this
     */
    public function setShort($short = true)
    {
        $this->short = $short;

        return $this;
    }

    /**
     * @return bool
     */
    public function isShort()
    {
        return $this->short;
    }

    /**
     * @param $taxed bool
     * @return $this
     */
    public function setTaxed($taxed)
    {
        $this->taxed = $taxed;

        return $this;
    }

    /**
     * @return bool
     */
    public function getTaxed()
    {
        return $this->taxed;
    }

    /**
     * @return float
     */
    public function getTotal()
    {
        return $this->price * $this->qty;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param $number
     * @return string
     */
    public function getHtml($number)
    {
        $currency = CL_Currency::model()->getDefaultCurrency();

        return '
            <tr bgcolor="#fff">
                <td align="center">' . $number . '</td>
                <td align="left">'   . $this->getDescription() . '</td>
                <td align="center">' . $this->qty . '</td>
                <td align="center">' . $this->units . '</td>
                <td align="center">' . $currency->getFullPrice($this->price) . '</td>
                <td align="center">' . $currency->getFullPrice($this->getTotal()) . '</td>
            </tr>
        ';
    }

    /**
     * @param string $type
     * @throws CException
     */
    private function setType($type)
    {
        if (!in_array($type, Resources::getTypes())) {
            throw new CException(sprintf('Undefined resource type: %s', $type));
        }

        $this->type = $type;
    }

    /**
     * @param string $description
     * @return string
     */
    private function formatDescription($description)
    {
        if (preg_match('/^(Storage|IP):\s?(.*)$/', $description, $match)) {
            $this->name = $match[2];
        }

        return $description;
    }
}