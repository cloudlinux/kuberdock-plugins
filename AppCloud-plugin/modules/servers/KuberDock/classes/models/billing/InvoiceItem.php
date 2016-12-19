<?php


namespace models\billing;


use models\Model;

class InvoiceItem extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;
    /**
     * @var string
     */
    protected $table = 'tblinvoiceitems';
    /**
     * @var array
     */
    protected $fillable = ['type', 'relid'];

    /**
     * @return BillableItem
     */
    public function billableItem()
    {
        return $this->hasOne('models\billing\BillableItem', 'id', 'relid');
    }

    /**
     * @return Invoice
     */
    public function invoice()
    {
        return $this->belongsTo('models\billing\Invoice', 'invoiceid');
    }

    /**
     * @param string $value
     * @return mixed
     */
    public function getNotesAttribute($value)
    {
        return json_decode($value);
    }

    /**
     * @param array $value
     */
    public function setNotesAttribute($value)
    {
        $this->attributes['notes'] = json_encode($value);
    }

    /**
     * @param BillableItem $item
     * @return $this
     */
    public function assignBillableItem(BillableItem $item)
    {
        if ($this->type === 'Hosting') {
            return $this;
        }

        $this->update([
            'type' => BillableItem::TYPE,
            'relid' => $item->id,
        ]);

        return $this;
    }
}