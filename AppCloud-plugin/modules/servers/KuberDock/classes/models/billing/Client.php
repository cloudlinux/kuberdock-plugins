<?php


namespace models\billing;


use components\Tools;
use models\Model;

class Client extends Model
{
    /**
     * @var string
     */
    protected $table = 'tblclients';

    /**
     * @return Currency
     */
    public function currencyModel()
    {
        return $this->belongsTo('models\billing\Currency', 'currency');
    }

    /**
     * @return Currency
     */
    public function getSessionCurrency()
    {
        return isset($_SESSION['currency'])
            ? Currency::find($_SESSION['currency']) : Currency::where('default', 1)->first();
    }

    /**
     * @return string
     */
    public function getGateway()
    {
        if ($this->defaultgateway) {
            return $this->defaultgateway;
        } else {
            return PaymentGateway::orderBy('order', 'desc')->groupBy('gateway')->first()->gateway;
        }
    }

    /**
     * @param $value
     * @return mixed|string
     */
    public function getEmailAttribute($value)
    {
        $email = trim($value, '.');
        $email = preg_replace ('/\.+/i', '.', $email);

        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        if ($email === false || $email === '') {
            $email = \JTransliteration::transliterate($this->lastname) . mt_rand(1, 1000) . '@kd.com';

        }
        $email = substr_replace($email, '', 50);

        return $email;
    }

    /**
     * @param string $value
     * @return mixed
     */
    public function getFirstnameAttribute($value)
    {
        return $this->prepareName($value);
    }

    /**
     * @param string $value
     * @return mixed
     */
    public function getLastnameAttribute($value)
    {
        return $this->prepareName($value);
    }

    /**
     * @param string $value
     * @return mixed
     */
    private function prepareName($value)
    {
        $name = preg_replace ('/[^[:alpha:]?!]/iu', '', $value);
        $name = substr_replace($name, '', 25);

        if ($name === '') {
            $name = Tools::generateRandomString();
        }

        return $name;
    }
}