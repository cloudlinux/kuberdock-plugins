<?php


namespace models\billing;


use components\Units;
use models\Model;

class Package extends Model
{
    /**
     * @var string
     */
    protected $table = 'tblproducts';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function services()
    {
        return $this->hasMany('models\billing\Service');
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        $psUnit = Units::getPSUnits();
        //$trafficUnit = Units::getTrafficUnits(); // AC-3783

        $config = array(
            'enableTrial' => array(
                'Number' => 1,
                /** not used. Just to remember, that list order must not be changed,
                 * in db this options are stored in tblproducts.configoption{Number}
                 */
                'FriendlyName' => 'Trial package',
                'Type' => 'yesno',
                'Description' => '&nbsp;',
            ),
            'trialTime' => array(
                'Number' => 2,
                'FriendlyName' => 'User Free Trial period',
                'Type' => 'text',
                'Size' => '10',
                'Default' => '0',
                'Description' => 'Days',
            ),
            'paymentType' => array(
                'Number' => 3,
                'FriendlyName' => 'Service payment type',
                'Type' => 'dropdown',
                'Options' => implode(',', self::getPaymentTypes()),
                'Default' => 'monthly',
                'Description' => '',
            ),
            'debug' => array(
                'Number' => 4,
                'FriendlyName' => 'Debug Mode',
                'Type' => 'yesno',
                'Default' => 'yes',
                'Description' => 'Logs on "Module Log"',
            ),
            'priceIP' => array(
                'Number' => 5,
                'FriendlyName' => 'Price for IP',
                'Type' => 'text',
                'Size' => '10',
                'Default' => '0',
                'Description' => '<span>per IP/hour</span>',
            ),
            'pricePersistentStorage' => array(
                'Number' => 6,
                'FriendlyName' => 'Price for persistent storage',
                'Type' => 'text',
                'Size' => '10',
                'Default' => '0',
                'Description' => '<span data-unit="' . $psUnit . '">per ' . $psUnit . '/hour</span>',
            ),
            'priceOverTraffic' => array(
                'Number' => 7,
                'FriendlyName' => ' ',
                /*AC-3783
                'FriendlyName' => 'Price for additional traffic',
                'Type' => 'text',
                'Size' => '10',
                'Default' => '0',
                'Description' => '<span data-unit="' . $trafficUnit . '">per ' . $trafficUnit . '/hour</span>',*/
            ),
            'firstDeposit' => array(
                'Number' => 8,
                'FriendlyName' => 'First Deposit',
                'Type' => 'text',
                'Size' => '10',
                'Default' => '0',
                'Description' => '',
            ),
            'billingType' => array(
                'Number' => 9,
                'FriendlyName' => 'Billing type',
                'Type' => 'radio',
                'Options' => 'PAYG,Fixed price',
                'Default' => 'Fixed price',
                'Description' => '',
            ),
            'restrictedUser' => array(
                'Number' => 10,
                'FriendlyName' => 'Restricted users',
                'Type' => 'yesno',
                'Description' => '',
            ),
            'trialNoticeEvery' => array(
                'Number' => 11,
                'FriendlyName' => 'Trial period ending notice repeat',
                'Type' => 'text',
                'Size' => '10',
                'Default' => '0',
                'Description' => 'days (0 - don\'t send)',
            ),
            'sendTrialExpire' => array(
                'Number' => 12,
                'FriendlyName' => 'Trial period expired notice',
                'Type' => 'yesno',
                'Size' => '10',
                'data-type' => 'trial',
                'Default' => '0',
            ),
        );

        return $config;
    }

    /**
     * Is trial package
     * @return bool
     */
    public function getEnableTrial()
    {
        return (bool) $this->getConfigOption('enableTrial');
    }

    /**
     * Trial days
     * @return int
     */
    public function getTrialTime()
    {
        return (int) $this->getConfigOption('trialTime');
    }

    /**
     * @return string
     */
    public function getPaymentType()
    {
        return (string) $this->getConfigOption('paymentType');
    }

    /**
     * Enable API debug
     * @return bool
     */
    public function getDebug()
    {
        return (bool) $this->getConfigOption('debug');
    }

    /**
     * @return bool
     */
    public function getPriceIP()
    {
        return (bool) $this->getConfigOption('priceIP');
    }

    /**
     * @return float
     */
    public function getPricePersistentStorage()
    {
        return (float) $this->getConfigOption('pricePersistentStorage');
    }

    /**
     * @return float
     */
    public function getPriceOverTraffic()
    {
        return (float) $this->getConfigOption('priceOverTraffic');
    }

    /**
     * First deposit amount
     * @return float
     */
    public function getFirstDeposit()
    {
        return (float) $this->getConfigOption('firstDeposit');
    }

    /**
     * @return string
     */
    public function getBillingType()
    {
        return (string) $this->getConfigOption('billingType');
    }

    /**
     * Create user with LimitedUser role
     * @return bool
     */
    public function getRestrictedUser()
    {
        return (bool) $this->getConfigOption('restrictedUser');
    }

    /**
     * Send trial notice every X days
     * @return float
     */
    public function getTrialNoticeEvery()
    {
        return (float) $this->getConfigOption('trialNoticeEvery');
    }

    /**
     * Send trial expired letter
     * @return float
     */
    public function getSendTrialExpire()
    {
        return (float) $this->getConfigOption('sendTrialExpire');
    }

    /**
     * @param string $name
     * @return mixed
     * @throws \Exception
     */
    public function getConfigOption($name)
    {
        if (($key = array_search($name, array_keys($this->getConfig()))) !== false) {
            $key += 1;
            if (isset($this->{'configoption' . $key})) {
                return $this->{'configoption' . $key};
            } else {
                throw new \Exception('Undefined option: ' . $name);
            }
        } else {
            throw new \Exception('Undefined option name: ' . $name);
        }
    }

    /**
     * @return array
     */
    public static function getPaymentTypes()
    {
        return array(
            'annually',
            'quarterly',
            'monthly',
            'hourly',
        );
    }
}