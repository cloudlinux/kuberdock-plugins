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
}