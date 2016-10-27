<?php


namespace models\billing;


use models\Model;

class CustomField extends Model
{
    /**
     * @var string
     */
    protected $table = 'tblcustomfields';
    /**
     * @var array
     */
    protected $fillable = ['type', 'relid', 'fieldname', 'adminonly'];
}