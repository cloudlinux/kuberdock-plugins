<?php


namespace models\billing;


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
}