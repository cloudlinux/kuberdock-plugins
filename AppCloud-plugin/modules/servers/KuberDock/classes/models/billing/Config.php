<?php


namespace models\billing;


use components\Component;
use models\Model;

class Config extends Model
{
    /**
     * @var string
     */
    protected $table = 'tblconfiguration';

    /**
     * @var Component
     */
    protected $component;

    /**
     * Get config collection
     * @return Component
     */
    public function get()
    {
        global $CONFIG;

        if (!$this->component) {
            $this->component = new Component();
            if (isset($CONFIG) && $CONFIG) {
                $this->component->setAttributes($CONFIG);
            } else {
                $this->component->setAttributes(Config::all()->toArray());
            }
        }

        return $this->component;
    }

}