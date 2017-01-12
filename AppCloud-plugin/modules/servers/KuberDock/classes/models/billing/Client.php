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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function services()
    {
        return $this->hasMany('models\billing\Service', 'userid');
    }

    /**
     * @return Currency
     */
    public function currencyModel()
    {
        return $this->belongsTo('models\billing\Currency', 'currency');
    }

    /**
     * @param $query
     * @param string $user
     * @param array $domain
     * @return mixed
     */
    public function scopeByDomain($query, $user, $domain)
    {
        return $query->whereHas('services', function ($query) use ($user, $domain) {
            $query->where('username', $user)
                ->where('status', 'Active')
                ->whereIn('domain', $domain);
        })->first();
    }

    /**
     * @return $this|null
     */
    public function getCurrent()
    {
        if (isset($_SESSION['uid'])) {
            return self::find($_SESSION['uid']);
        }

        return null;
    }

    /**
     * @return Currency
     */
    public function getSessionCurrency()
    {
        $client = $this->getCurrent();

        if ($client) {
            return $client->currencyModel;
        } else if (isset($_SESSION['currency'])) {
            return Currency::find($_SESSION['currency']);
        }

        return Currency::where('default', 1)->first();
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