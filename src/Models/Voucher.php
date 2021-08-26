<?php

namespace BeyondCode\Vouchers\Models;

use BeyondCode\Vouchers\Events\VoucherRedeemed;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'model_id',
        'model_type',
        'code',
        'data',
        'expires_at',
        'quantity',
        'type',
        'value',
        'user_id',
        'quantity_per_user',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'expires_at'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'collection'
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('vouchers.table', 'vouchers');
    }

    /**
     * Get the users who redeemed this voucher.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(config('vouchers.user_model'), config('vouchers.relation_table'))
            ->withPivot(['redeemed_at','quantity']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function model()
    {
        return $this->morphTo();
    }

    /**
     * Check if code is expired.
     *
     * @return bool
     */
    public function isExpired()
    {
        return $this->expires_at ? Carbon::now()->gte($this->expires_at) : false;
    }

    /**
     * Check if code is not expired.
     *
     * @return bool
     */
    public function isNotExpired()
    {
        return !$this->isExpired();
    }

    /**
     * Check if code has a limited quantity.
     *
     * @return bool
     */
    public function hasLimitedQuantity()
    {
        return !is_null($this->quantity);
    }

    /**
     * Check if code has been used as many times as the quantity.
     *
     * @return bool
     */
    public function isSoldOut()
    {
        return (!$this->hasLimitedQuantity() || $this->quantity_left > 0) ? false : true;
    }

    public function getAssociatedUserId() {
        return $this->user_id;
    }

    public function getQuantityPerUser() {
        return $this->quantity_per_user;
    }

    public function getDiscount($total, $decimals = 2, $decimalSeparator = '.', $thousandsSeparator = '')
    {
        switch ($this->type) {
            case 'total':
                $discount = $total;
                break;
            case 'percentage':
                $discount = $total * ($this->value / 100);
                break;
            case 'fixed':
                $discount = $total - $this->value;
                break;
            default:
                $discount = 0;
                break;
        }

        return number_format($discount, $decimals, $decimalSeparator, $thousandsSeparator);
    }
}
