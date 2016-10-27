<?php


namespace models\billing;


use api\KuberDock_Api;
use components\BillingApi;
use models\Model;

class Server extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;
    /**
     * @var string
     */
    protected $table = 'tblservers';

    /**
     * @return ServerGroup
     */
    public function groups()
    {
        return $this->hasManyThrough(
            'models\billing\ServerGroup', 'models\billing\ServerGroupRelation', 'serverid', 'id'
        );
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeTypeKuberDock($query)
    {
        return $query->where('type', KUBERDOCK_MODULE_NAME);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeActive($query)
    {
        return $query->where('disabled', 0)->where('active', 1);
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        $scheme = $this->secure == 'on' ? KuberDock_Api::PROTOCOL_HTTPS : KuberDock_Api::PROTOCOL_HTTP;
        $domain = $this->hostname ? $this->hostname : $this->ipaddress;

        return sprintf('%s://%s', $scheme, $domain);
    }

    /**
     * @return KuberDock_Api
     */
    public function getApi()
    {
        $url = $this->getUrl();
        $password = BillingApi::model()->decryptPassword($this->password);;

        $api = new KuberDock_Api($this->username, $password, $url);

        if ($this->accesshash) {
            $api->setToken($this->accesshash);
        }

        return $api;
    }
}