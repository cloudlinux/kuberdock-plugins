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

    /**
     * @var array
     */
    protected $fillable = [
        'type', 'currency', 'msetupfee', 'qsetupfee', 'asetupfee', 'bsetupfee','tsetupfee',
        'monthly', 'quarterly', 'semiannually', 'annually', 'biennially','triennially',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function package()
    {
        return $this->belongsTo('models\billing\Package', 'relid');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currencyModel()
    {
        return $this->belongsTo('models\billing\Currency', 'currency');
    }

    /**
     * @param $query
     * @param int $currency
     * @return mixed
     */
    public function scopeWithCurrency($query, $currency)
    {
        return $query->where('currency', $currency);
    }

    /**
     * Return format
     * array(
     *  'cycle' => string
     *  'recurring' => float
     *  'setup' => float
     * )
     * @return array
     */
    public function getReadable()
    {
        $cycle = $this->package->paytype;
        $recurring = -1;
        $setup = -1;

        if ($cycle == 'free') {
            return [
                'cycle' => $cycle,
                'recurring' => $recurring,
                'setup' => $setup,
            ];
        }

        $periods = [
            'monthly',
            'quarterly',
            'semiannually',
            'annually',
            'biennially',
            'triennially',
        ];

        foreach ($periods as $row) {
            if ($this->getAttribute($row) != -1) {
                $recurring = $this->getAttribute($row);
                $setup = $this->getAttribute(substr($row, 0, 1) . 'setupfee');
                $cycle = $row;
                break;
            }
        }

        return [
            'cycle' => $cycle,
            'recurring' => (float) $recurring,
            'setup' => (float) $setup,
        ];
    }
}