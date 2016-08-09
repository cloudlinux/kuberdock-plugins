<?php

namespace components;

use base\models\CL_Currency;

class KuberDock_InvoiceItem
{
    /** @var bool Short invoices have only description and total. No units, quantity and price */
    private $short = false;

    private $units;
    private $description;
    private $taxed = false;
    private $price;
    private $qty;

    public function __construct($description, $price, $units, $qty)
    {
        $this->description = $description;
        $this->price = $price;
        $this->units = $units;
        $this->qty = $qty;
    }

    public static function create($description, $price, $units = null, $qty = 1)
    {
        $object =  new self($description, $price, $units, $qty);

        if (is_null($units)) {
            $object->setShort();
        }

        return $object;
    }

    public function setShort($short = true)
    {
        $this->short = $short;

        return $this;
    }

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

    public function getTaxed()
    {
        return $this->taxed;
    }

    public function getTotal()
    {
        return $this->price * $this->qty;
    }

    public function getDescription()
    {
        return $this->description;
    }

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
}