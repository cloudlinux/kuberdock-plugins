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
            'models\billing\Server', 'models\billing\ServerGroupRelation', 'groupid', 'id'
        );
    }

    public function grouprels()
    {
        return $this->hasMany(
            'models\billing\ServerGroupRelation', 'groupid'
        );
    }

    public function scopeByServerId($query, $server_id)
    {
        return $query->whereHas('grouprels', function ($query) use ($server_id) {
            $query->where('serverid', $server_id);
        })->first();
    }
}