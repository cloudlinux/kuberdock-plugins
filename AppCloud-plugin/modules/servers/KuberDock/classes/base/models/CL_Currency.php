<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

class CL_Currency extends CL_Model {
    const FORMAT_1 = 1;      // 1234.56
    const FORMAT_2 = 2;      // 1,234.56
    const FORMAT_3 = 3;      // 1.234,56
    const FORMAT_4 = 4;      // 1,234

    /**
     *
     */
    public function setTableName()
    {
        $this->tableName = 'tblcurrencies';
    }

    /**
     * @return $this
     */
    public function getDefaultCurrency()
    {
        $values = array('default' => 1);
        if(isset($_GET['currency'])) {
            $values = array('id' => (int) $_GET['currency']);
        }
        $this->setAttributes(current($this->loadByAttributes($values)));

        return $this;
    }

    /**
     * @param $price
     * @return string
     */
    public function getFullPrice($price)
    {
        $price = $price * $this->rate;

        if(function_exists('formatCurrency')) {
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
     * @return array
     * @throws Exception
     */
    public function getPaymentGateways()
    {
        $admin = KuberDock_User::model()->getCurrentAdmin();

        $results = localAPI('getpaymentmethods', array(), $admin['name']);

        if($results['result'] != 'success') {
            throw new Exception($results['message']);
        }

        return $results['paymentmethods']['paymentmethod'];
    }

    /**
     * Class loader
     *
     * @param string $className
     * @return CL_Currency
     */
    public static function model($className = __CLASS__)
    {
        if(isset(self::$_models[$className])) {
            return self::$_models[$className];
        } else {
            self::$_models[$className] = new $className;
            return self::$_models[$className];
        }
    }
} 