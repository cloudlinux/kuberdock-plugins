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
     * @param $price
     * @return string
     */
    public function getFullPrice($price)
    {
        $price = $price * $this->rate;

        if (function_exists('formatCurrency')) {
            return formatCurrency($price);
        } else {
            return sprintf("%s%s %s", $this->prefix, $this->getFormatted($price), $this->suffix);
        }
    }

    /**
     * @param $value
     * @return float|string
     */
    public function getFormatted($value)
    {
        $value = (float) $value * $this->rate;

        switch($this->format) {
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