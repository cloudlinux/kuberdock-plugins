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
     * @return Package
     */
    public function package()
    {
        return $this->belongsTo('models\billing\Package', 'product_id');
    }

    /**
     * @param string $value
     * @return array
     */
    public function getDetailsAttribute($value)
    {
        return json_decode($value, true);
    }

    /**
     * @param string $value
     */
    public function setDetailsAttribute($value)
    {
        $this->attributes['details'] = json_encode($value);
    }
}