<?php


namespace models\billing;


use models\Model;

class ServerGroupRelation extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;
    /**
     * @var string
     */
    protected $primaryKey = 'serverid';
    /**
     * @var string
     */
    protected $table = 'tblservergroupsrel';
}