<?php


namespace models\billing;


use models\Model;

class Pricing extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;
    /**
     * @var string
     */
    protected $table = 'tblpricing';

    protected $fillable = [
        'type', 'currency', 'msetupfee', 'qsetupfee', 'asetupfee', 'bsetupfee','tsetupfee',
        'monthly', 'quarterly', 'semiannually', 'annually', 'biennially','triennially',
    ];
}