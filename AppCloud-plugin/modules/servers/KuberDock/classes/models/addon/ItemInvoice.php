<?php


namespace models\addon;


use components\Tools;
use exceptions\CException;
use models\addon\billing\BillingInterface;
use models\addon\resource\Pod;
use models\billing\Invoice;
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
     * @return \Closure
     */
    public function getSchema()
    {
        return function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->integer('item_id', false, true);
            $table->integer('invoice_id');
            $table->string('status', 16);
            $table->enum('type', [
                ItemInvoice::TYPE_ORDER,
                ItemInvoice::TYPE_EDIT,
                ItemInvoice::TYPE_SWITCH,
            ])->default(ItemInvoice::TYPE_ORDER);
            $table->text('params')->nullable();
            $table->timestamps();

            $table->index('invoice_id');
            $table->index('item_id');

            $table->foreign('item_id')->references('id')->on('KuberDock_items')->onDelete('cascade');
        };
    }

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

    public function scopeType($query, $type)
    {
        if (!in_array($type, self::getTypes())) {
            throw new \Exception('Wrong ItemInvoice type: ' . $type);
        }

        return $query->where('type', $type);
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

        /** @var BillingInterface $billing */
        $billing = $this->item->service->package->getBilling();

        $billing->beforePayment($this);

        $method = 'after' . ucfirst(strtolower($this->type)) . 'Payment';

        try {
            if (!method_exists($billing, $method)) {
                throw new \Exception('Unknown after payment method');
            }
            return call_user_func([$billing, $method], $this);
        } catch (\Exception $e) {
            CException::log($e);
        }
    }

    /**
     * If user have unpaid invoices for same action (switch same pod) - delete them
     */
    public function deleteUnpaidSiblings()
    {
        $siblings = ItemInvoice::type($this->type)
            ->unpaid()
            ->where('item_id', $this->item_id)
            ->get();

        $invoiceIds = [];
        foreach ($siblings as $sibling) {
            $invoiceIds[] = $sibling->invoice_id;
            $sibling->delete();
        }

        Invoice::whereIn('id', $invoiceIds)->delete();
    }
}