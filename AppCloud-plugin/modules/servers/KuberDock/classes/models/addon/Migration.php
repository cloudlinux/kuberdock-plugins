<?php


namespace models\addon;


use models\Model;
use Illuminate\Database\Capsule\Manager as Capsule;

class Migration extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'KuberDock_migrations';
    /**
     * @var array
     */
    protected $fillable = ['version'];

    /**
     * @return \Closure
     */
    public function getSchema()
    {
        return function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->integer('version');
            $table->timestamp('timestamp')->default(Capsule::connection()->raw('CURRENT_TIMESTAMP'));

            $table->primary('version');
        };
    }
}