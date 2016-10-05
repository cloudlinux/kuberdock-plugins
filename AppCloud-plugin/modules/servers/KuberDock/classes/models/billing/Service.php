<?php


namespace models\billing;


use api\KuberDock_Api;
use components\BillingApi;
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
    protected $dates = ['nextinvoicedate', 'nextduedate'];

    /**
     *
     */
    protected function bootIfNotBooted()
    {
        parent::bootIfNotBooted();
    }

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
     * @return KuberDock_Api
     */
    public function getApi()
    {
        $password = BillingApi::model()->decryptPassword($this->password);
        $debug = $this->package->getDebug();
        $api = new KuberDock_Api($this->username, $password, $this->serverModel->getUrl(), $debug);

        if ($token = $this->getToken()) {
            $api->setToken($token);
        }

        return $api;
    }

    /**
     * @return KuberDock_Api
     */
    public function getAdminApi()
    {
        return $this->serverModel->getApi();
    }

    /**
     * @return string
     */
    public function getToken()
    {
        // TODO: implement CustomField\CustomFieldValue
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
}