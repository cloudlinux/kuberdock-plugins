<?php


namespace models\billing;


use models\Model;

class ServerGroup extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;
    /**
     * @var string
     */
    protected $table = 'tblservergroups';

    /**
     * @return ServerGroup
     */
    public function servers()
    {
        return $this->hasManyThrough(
            'models\billing\Server', 'models\billing\ServerGroupRelation', 'serverid', 'id'
        );
    }
}