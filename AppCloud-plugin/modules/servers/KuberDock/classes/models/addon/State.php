<?php


namespace models\addon;


use models\billing\Package;
use models\Model;

class State extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;
    /**
     * @var string
     */
    protected $table = 'KuberDock_states';
    /**
     * @var array
     */
    protected $fillable = [];
    /**
     * @var array
     */
    protected $dates = ['checkin_date'];
    /**
     * @var array
     */
    protected $casts = [
        'details' => 'array',
    ];

    /**
     * @return Package
     */
    public function package()
    {
        return $this->belongsTo('models\billing\Package', 'product_id');
    }
}