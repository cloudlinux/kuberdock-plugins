<?php


namespace models\billing;


use components\InvoiceItem as ComponentsInvoiceItem;
use components\Units;
use exceptions\CException;
use models\addon\KubePrice;
use models\addon\Resources;
use models\addon\billingTypes\BillingInterface;
use models\addon\billingTypes\Fixed;
use models\addon\billingTypes\Payg;
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
     * @return Pricing
     */
    public function pricing()
    {
        return $this->hasMany('models\billing\Pricing', 'relid');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function relatedKuberDock()
    {
        return $this->hasOne('models\addon\PackageRelation', 'product_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function kubePrice()
    {
        return $this->hasMany('models\addon\KubePrice', 'product_id');
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeTypeKuberDock($query)
    {
        return $query->where('servertype', KUBERDOCK_MODULE_NAME);
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
                'Options' => implode(',', array_keys(self::getPaymentTypes())),
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
                'Options' => implode(',', array_keys(self::getBillingTypes())),
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
     * @return string
     */
    public function getReadablePaymentType()
    {
        $types = Package::getPaymentTypes();

        return $types[$this->getPaymentType()];
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
     * @return float
     */
    public function getPriceIP()
    {
        return (float) $this->getConfigOption('priceIP');
    }

    /**
     * @return float
     */
    public function getPricePS()
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
     * @return array
     */
    public function getKubes()
    {
        return KubePrice::with('template')->where('product_id', $this->id)
            ->get()->keyBy('template.kuber_kube_id')->toArray();
    }

    /**
     * @return BillingInterface
     * @throws CException
     */
    public function getBilling()
    {
        if ($this->isBillingFixed()) {
            return new Fixed();
        } else if ($this->isBillingPayg()) {
            return new Payg();
        }

        throw new CException('Unknown billing type');
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
     * @param string $name
     * @param string $value
     * @throws \Exception
     */
    public function setConfigOption($name, $value)
    {
        if (($key = array_search($name, array_keys($this->getConfig()))) !== false) {
            $key += 1;
            $this->{'configoption' . $key} = $value;
        } else {
            throw new \Exception('Undefined option name: ' . $name);
        }
    }

    /**
     * @param array $attributes
     * @throws \Exception
     */
    public function setConfigOptions($attributes = [])
    {
        foreach ($attributes as $attribute => $value) {
            try {
                $this->setConfigOption($attribute, $value);
            } catch (\Exception $e) {
                CException::log($e);
            }
        }
    }

    /**
     * @return array
     */
    public function getSortedActivePackages()
    {
        $paymentTypes = array_flip(Package::getPaymentTypes());
        $packages = Package::join('KuberDock_products', 'tblproducts.id', '=', 'KuberDock_products.product_id')
            ->typeKuberDock()->orderBy('name')->get();

        uasort($packages, function ($a, $b) use ($paymentTypes) {
            if ($a->getPaymentType() == $b->getPaymentType()) {
                return ($a->id > $b->id) ? 1 : -1;
            }
            return ($paymentTypes[$a->getPaymentType()] < $paymentTypes[$b->getPaymentType()]) ? 1 : -1;
        });

        return $packages;
    }


    /**
     * @param float $price
     * @param string $description
     * @param string|null $units
     * @param int $qty
     * @param string $type
     * @return ComponentsInvoiceItem
     */
    public function createInvoiceItem($price, $description, $units = null, $qty = 1, $type = Resources::TYPE_POD)
    {
        $invoice = ComponentsInvoiceItem::create($price, $description, $units, $qty, $type);

        if ($this->taxable) {
            $invoice->setTaxed(true);
        }

        return $invoice;
    }

    /**
     * @return bool
     */
    public function isKuberDock()
    {
        return $this->servertype == KUBERDOCK_MODULE_NAME;
    }

    /**
     * @return bool
     */
    public function isBillingFixed()
    {
        return $this->getBillingType() == 'Fixed price';
    }

    /**
     * @return bool
     */
    public function isBillingPayg()
    {
        return $this->getBillingType() == 'PAYG';
    }

    /**
     * Billing value => KD value
     * @return array
     */
    public static function getPaymentTypes()
    {
        return [
            'annually' => 'annual',
            'quarterly' => 'quarter',
            'monthly' => 'month',
            'hourly' => 'hour',
        ];
    }

    /**
     * Billing value => KD value
     * @return array
     */
    public static function getBillingTypes()
    {
        return [
            'PAYG' => 'payg',
            'Fixed price' => 'fixed',
        ];
    }
}