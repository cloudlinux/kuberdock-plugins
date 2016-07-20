<?php


namespace models\billing;


use models\Model;

class CustomFieldValue extends Model
{
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'tblcustomfieldsvalues';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customField()
    {
        return self::belongsTo('models\billing\CustomField', 'fieldid');
    }
}