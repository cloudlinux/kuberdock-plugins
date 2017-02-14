<?php


namespace models\billing;


use models\Model;

class CustomFieldValue extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'tblcustomfieldsvalues';
    /**
     * @var array
     */
    protected $fillable = ['fieldid', 'relid', 'value'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customField()
    {
        return $this->belongsTo('models\billing\CustomField', 'fieldid');
    }
}