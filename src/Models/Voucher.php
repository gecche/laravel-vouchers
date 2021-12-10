<?php

namespace BeyondCode\Vouchers\Models;

use BeyondCode\Vouchers\Contracts\VoucherConditionInterface;
use BeyondCode\Vouchers\Events\VoucherRedeemed;
use BeyondCode\Vouchers\Exceptions\VoucherConditionFails;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

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
        'starts_at',
        'expires_at',
        'quantity',
        'type',
        'value',
        'user_id',
        'quantity_per_user',
        'quantity_left',
        'conditions',
        'apply_conditions',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'expires_at',
        'starts_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
        'conditions' => 'array',
        'apply_conditions' => 'array',
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

    public function getRedeemRelation() {
        return 'vouchers';
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
     * Check if code is started.
     *
     * @return bool
     */
    public function isStarted()
    {
        return $this->starts_at ? Carbon::now()->gte($this->starts_at) : true;
    }

    /**
     * Check if code is not started.
     *
     * @return bool
     */
    public function isNotStarted()
    {
        return !$this->isStarted();
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
        return (!$this->hasLimitedQuantity() || $this->users->count() < $this->quantity) ? false : true;
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
                $discount = min($this->value,$total);
                break;
            case 'custom':
                //TODO
                $discount = 0;
                break;
            default:
                $discount = 0;
                break;
        }

        return number_format($discount, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    public function checkConditions($user = null,$additionalData = []) {
        $conditions = $this->conditions;
        $conditionsErrors = [];
        foreach ($conditions as $condition) {

            $conditionClass = Arr::get($condition,'class');
            if (strpos($conditionClass,"\\") === false) {
                $conditionClass = config('voucher.conditions_namespace') . "\\" . $conditionClass;
            }
            $interfaces = class_implements($conditionClass);

            if (!isset($interfaces[VoucherConditionInterface::class])) {
                continue;
            }

            $condition = new $conditionClass($this,$user,$additionalData);
            if (!$condition->check()) {
                throw new VoucherConditionFails($condition);
            }
        }
        return $conditionsErrors;
    }

    public function getConditionsAttribute($value)
    {
        return $this->fromJson($value) ?: [];
    }

}
