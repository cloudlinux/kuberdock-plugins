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
    protected $primaryKey = 'groupid';
    /**
     * @var string
     */
    protected $table = 'tblservergroupsrel';
}