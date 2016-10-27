<?php


namespace models\addon;


use components\BillingApi;
use exceptions\CException;
use models\addon\resource\Pod;
use models\billing\Invoice;
use models\billing\Service;
use models\Model;

class ItemInvoice extends Model
{
    /**
     *
     */
    const TYPE_ORDER = 'order';
    /**
     *
     */
    const TYPE_EDIT = 'edit';
    /**
     *
     */
    const TYPE_SWITCH = 'switch';

    /**
     * @var bool
     */
    public $timestamps = true;

    /**
     * @var string
     */
    protected $table = 'KuberDock_item_invoices';
    /**
     * @var array
     */
    protected $fillable = ['invoice_id', 'status', 'type', 'params'];

    /**
     * @return array
     */
    public static function getTypes()
    {
        return [
            self::TYPE_ORDER,
            self::TYPE_EDIT,
            self::TYPE_SWITCH,
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function invoice()
    {
        return $this->belongsTo('models\billing\Invoice', 'invoice_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function item()
    {
        return $this->belongsTo('models\addon\Item', 'item_id');
    }

    /**
     * @param $query
     */
    public function scopeUnpaid($query)
    {
        return $query->where('status', Invoice::STATUS_UNPAID)->orderBy('invoice_id', 'desc');
    }

    /**
     * @param $query
     */
    public function scopePaid($query)
    {
        return $query->where('status', Invoice::STATUS_PAID)->orderBy('invoice_id', 'desc');
    }

    /**
     * @param string $value
     * @return mixed
     */
    public function getParamsAttribute($value)
    {
        return json_decode($value);
    }

    /**
     * @param array $value
     */
    public function setParamsAttribute($value)
    {
        $this->attributes['params'] = json_encode($value);
    }

    /**
     * @return Pod
     */
    public function afterPayment()
    {
        $this->status = $this->invoice->status;
        $this->save();

        $billing = $this->item->service->package->getBilling();
        $method = 'after' . ucfirst(strtolower($this->type)) . 'Payment';

        try {
            if (!method_exists($billing, $method)) {
                throw new \Exception('Unknown after payment method');
            }
            return call_user_func([$billing, $method], $this->item);
        } catch (\Exception $e) {
            CException::log($e);
        }
    }
}