<?php


namespace models\billing;


use api\Api;
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
     * @param $query
     * @param string $referer
     * @return mixed
     */
    public function scopeByReferer($query, $referer)
    {
        $data = parse_url($referer);
        $url = $data['host'];

        return $query->where(function ($query) use ($url) {
            $query->where('tblservers.ipaddress', 'like', "%$url%")
                ->orWhere('tblservers.hostname', 'like', "%$url%");
        });
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        $scheme = $this->secure == 'on' ? Api::PROTOCOL_HTTPS : Api::PROTOCOL_HTTP;
        $domain = $this->hostname ? $this->hostname : $this->ipaddress;

        return sprintf('%s://%s', $scheme, $domain);
    }

    /**
     * @return Api
     */
    public function getApi()
    {
        $url = $this->getUrl();
        $password = BillingApi::model()->decryptPassword($this->password);

        $api = new Api($this->username, $password, $url);

        if ($this->accesshash) {
            $api->setToken($this->accesshash);
        }

        return $api;
    }
}