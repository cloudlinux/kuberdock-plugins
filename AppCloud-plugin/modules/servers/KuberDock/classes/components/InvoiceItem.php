<?php

namespace components;


use exceptions\CException;
use Illuminate\Database\Eloquent\Collection;
use models\addon\Resources;
use models\billing\Currency;

class InvoiceItem
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
    private $name;
    /**
     * @var bool
     */
    private $tax = false;
    /**
     * @var float
     */
    private $price;
    /**
     * @var int
     */
    private $qty;
    /**
     * @var string Resources::getTypes
     */
    private $type;
    /**
     * @var string
     */
    private $customDescription;

    /**
     * InvoiceItem constructor.
     * @param float $price
     * @param string $description
     * @param string $units
     * @param int $qty
     * @param string $type
     */
    public function __construct($price, $description, $units, $qty, $type)
    {
        $this->customDescription = $description;
        $this->price = $price;
        $this->units = $units;
        $this->qty = $qty;
        $this->setType($type);
    }

    /**
     * @param $price
     * @param string $description
     * @param null $units
     * @param int $qty
     * @param string $type
     * @return InvoiceItem
     */
    public static function create($price, $description, $units = null, $qty = 1, $type = Resources::TYPE_POD)
    {
        $object = new self($price, $description, $units, $qty, $type);

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
        $this->tax = $taxed;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTaxed()
    {
        return $this->tax;
    }

    /**
     * @return mixed
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
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $type
     * @throws CException
     */
    public function setType($type)
    {
        if (!in_array($type, Resources::getTypes())) {
            throw new CException(sprintf('Undefined resource type: %s', $type));
        }

        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        if ($this->customDescription) {
            return $this->customDescription;
        }

        $description = '';

        switch ($this->type) {
            case Resources::TYPE_POD:
                $description = 'Pod: ' . $this->name;
                break;
            case Resources::TYPE_PD:
                $description = 'Storage: ' . $this->name;
                break;
            case Resources::TYPE_IP:
                $description = 'Public IP' . ($this->name ? ': ' . $this->name : '');
                break;
        }

        return $description;
    }

    /**
     * @param $number
     * @return string
     */
    public function getHtml($number)
    {
        $currency = Currency::getDefault();

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