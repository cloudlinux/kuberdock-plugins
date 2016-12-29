<?php


namespace models\billing;


use models\Model;

class Currency extends Model
{
    const FORMAT_1 = 1;      // 1234.56
    const FORMAT_2 = 2;      // 1,234.56
    const FORMAT_3 = 3;      // 1.234,56
    const FORMAT_4 = 4;      // 1,234

    /**
     * @var bool
     */
    public $timestamps = false;
    /**
     * @var string
     */
    protected $table = 'tblcurrencies';

    /**
     * @param double $price
     * @return double
     */
    public function getRatedPrice($price)
    {
        return (double) $price * $this->rate;
    }

    /**
     * @param double $price
     * @return string
     */
    public function getFullPrice($price)
    {
        $price = $this->getRatedPrice($price);

        if (function_exists('formatCurrency')) {
            return formatCurrency($price);
        } else {
            return sprintf('%s%s %s', $this->prefix, $this->getFormatted($price), $this->suffix);
        }
    }

    /**
     * @param double $value
     * @return double
     */
    public function getFormatted($value)
    {
        switch ($this->format) {
            case self::FORMAT_1:
                $value = number_format($value, 2, '.', '');
                break;
            case self::FORMAT_2:
                $value = number_format($value, 2, ',', ',');
                break;
            case self::FORMAT_3:
                $value = number_format($value, 2, '.', ',');
                break;
            case self::FORMAT_4:
                $value = number_format($value, 0, ',', '');
                break;
            default:
                $value = number_format($value, 2, '.', '');
                break;
        }

        return $value;
    }

    /**
     * @return $this
     */
    public static function getDefault()
    {
        return self::where('default', 1)->first();
    }
}