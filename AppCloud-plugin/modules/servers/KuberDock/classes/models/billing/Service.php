<?php


namespace models\billing;


use api\Api;
use components\BillingApi;
use components\Tools;
use exceptions\NotFoundException;
use models\addon\Item;
use models\addon\Resources;
use models\addon\Trial;
use models\Model;

class Service extends Model
{
    /**
     * Is after module create
     * @var bool
     */
    public $moduleCreate = false;
    /**
     * @var string
     */
    protected $table = 'tblhosting';
    /**
     * @var array
     */
    protected $dates = ['regdate', 'nextinvoicedate', 'nextduedate', 'overideautosuspend'];
    /**
     * @var array
     */
    protected $fillable = ['status'];

    /**
     * @return Package
     */
    public function package()
    {
        return $this->belongsTo('models\billing\Package', 'packageid');
    }

    /**
     * @return Client
     */
    public function client()
    {
        return $this->belongsTo('models\billing\Client', 'userid');
    }

    /**
     * @return Server
     */
    public function serverModel()
    {
        return $this->hasOne('models\billing\Server', 'id', 'server');
    }

    /**
     * @return CustomFieldValue
     */
    public function customFieldValues()
    {
        return $this->hasMany('models\billing\CustomFieldValue', 'relid');
    }

    /**
     * @return InvoiceItem
     */
    public function invoiceItem()
    {
        return $this->hasMany('models\billing\InvoiceItem', 'relid')->where('type', 'Hosting');
    }

    /**
     * @return Item
     */
    public function item()
    {
        return $this->hasOne('models\addon\Item', 'service_id')->where('status', '!=', Resources::STATUS_DELETED);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeTypeKuberDock($query)
    {
        return $query->whereHas('package', function ($query) {
            $query->where('servertype', KUBERDOCK_MODULE_NAME);
        });
    }

    /**
     * @return Api
     * @throws \Exception
     */
    public function getApi()
    {
        $password = BillingApi::model()->decryptPassword($this->password);
        $debug = $this->package->getDebug();
        $api = new Api($this->username, $password, $this->serverModel->getUrl(), $debug);

        if ($token = $this->getToken()) {
            $api->setToken($token);
        }

        return $api;
    }

    /**
     * @return Api
     */
    public function getAdminApi()
    {
        return $this->serverModel->getApi();
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function terminate()
    {
        $this->getAdminApi()->updateUser([
            'active' => false,
            'suspended' => false
        ], $this->username);

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function suspend()
    {
        $this->getAdminApi()->updateUser([
            'suspended' => true
        ], $this->username);

        $this->item()->where('status', '!=', Resources::STATUS_DELETED)->update([
            'status' => Resources::STATUS_SUSPENDED,
        ]);

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function unSuspend()
    {
        $this->getAdminApi()->updateUser([
            'suspended' => false
        ], $this->username);

        $this->item()->where('status', '!=', Resources::STATUS_DELETED)->update([
            'status' => Resources::STATUS_ACTIVE,
        ]);

        $api = $this->getApi();
        foreach ($api->getPods()->getData() as $pod) {
            try {
                $api->startPod($pod['id']);
            } catch (\Exception $e) {
                // pass
            }
        }

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function createUser()
    {
        $password = Tools::generateRandomString(25, '0123456789!@#$%^&*()');
        $this->username = $this->username ? $this->username : $this->client->email;
        $this->password = BillingApi::model()->encryptPassword($password);

        $api = $this->getAdminApi();

        $data = [
            'clientid' => (int) $this->client->id,
            'first_name' => $this->client->firstname,
            'last_name' => $this->client->lastname,
            'username' => $this->username,
            'password' => $password,
            'active' => true,
            'suspended' => false,
            'email' => $this->client->email,
            'rolename' => $this->package->getRole(),
            'package' => $this->package->name,
        ];

        try {
            $api->unDeleteUser($this->client->email);
            $api->updateUser($data, $this->username);
        } catch (NotFoundException $e) {
            $api->createUser($data);
        }

        $token = $this->getApi()->getToken();

        if ($token) {
            $this->setToken($token);
        }

        $this->domainstatus = 'Active';
        $this->save();

        if ($this->package->getEnableTrial()) {
            Trial::firstOrCreate([
                'user_id' => $this->userid,
                'service_id' => $this->id,
            ]);
        }

        // Send module create email
        BillingApi::model()->sendPreDefinedEmail($this->id, EmailTemplate::MODULE_CREATE_NAME, [
            'kuberdock_link' => $this->serverModel->getUrl(),
        ]);
    }

    /**
     * @param string $token
     */
    public function setToken($token)
    {
        $customField = CustomField::where('type', 'product')->where('relid', $this->package->id)->first();

        CustomFieldValue::firstOrCreate([
            'relid' => $this->id,
            'fieldid' => $customField->id,
        ]);

        CustomFieldValue::where('fieldid', $customField->id)
            ->where('relid', $this->id)
            ->update([
                'value' => $token,
            ]);
    }

    /**
     * @return string
     */
    public function getToken()
    {
        $data = $this->select('tblcustomfieldsvalues.*')
            ->join('tblcustomfieldsvalues', 'tblhosting.id', '=', 'tblcustomfieldsvalues.relid')
            ->join('tblcustomfields', 'tblcustomfields.id', '=', 'tblcustomfieldsvalues.fieldid')
            ->where('tblcustomfields.fieldname', 'Token')
            ->where('tblhosting.id', $this->id)
            ->first();

        return $data->value;
    }

    /**
     * @return string
     */
    public function getLoginLink()
    {
        $url = $this->serverModel->getUrl();
        $token = $this->getApi()->getJWTToken(array(), true);

        return sprintf('%s/?token2=%s', $url, $token);
    }

    /**
     * @return bool
     */
    public function isTrialExpired()
    {
        $now = new \DateTime();
        $now->setTime(0, 0, 0);
        $expireDate = clone $this->regdate;
        $expireDate->addDays($this->package->getTrialTime());

        return $this->package->getEnableTrial() && $now >= $expireDate;
    }
}