<?php


namespace models\billing;


use api\KuberDock_Api;
use components\BillingApi;
use components\Pod;
use models\Model;

class Service extends Model
{
    /**
     * @var string
     */
    protected $table = 'tblhosting';

    /**
     * @var Pod
     */
    protected $pod;

    /**
     *
     */
    protected function bootIfNotBooted()
    {
        parent::bootIfNotBooted();

        $this->pod = new Pod($this);
    }

    /**
     * @return Package
     */
    public function package()
    {
        return $this->belongsTo('models\billing\Package', 'packageid');
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
     * @return Pod
     */
    public function getPod()
    {
        return $this->pod;
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