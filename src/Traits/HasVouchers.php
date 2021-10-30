<?php

namespace BeyondCode\Vouchers\Traits;

use BeyondCode\Vouchers\Models\Voucher;
use BeyondCode\Vouchers\Facades\Vouchers;

trait HasVouchers
{
    /**
     * Set the polymorphic relation.
     *
     * @return mixed
     */
    public function vouchers()
    {
        return $this->morphMany(config('vouchers.model', Voucher::class), 'model');
    }

    /**
     * @param int $amount
     * @param array $data
     * @param null $expires_at
     * @param integer|null $quantity
     * @return Voucher[]
     */
    public function createVouchers(int $amount, array $data = [], $expires_at = null, $quantity = null,
                                   $type = 'total', $value = null, $user_id = null, $quantity_per_user = 1, $starts_at = null,
                                    $conditions = null, $voucherModel = null)
    {
        return Vouchers::create($this, $amount, $data, $expires_at,$quantity,$type,$value,$user_id,$quantity_per_user,
                            $starts_at, $conditions, $voucherModel);
    }

    /**
     * @param array $data
     * @param null $expires_at
     * @param integer|null $quantity
     * @return Voucher
     */
    public function createVoucher(array $data = [], $expires_at = null, $quantity = null,
                                  $type = 'total', $value = null, $user_id = null, $quantity_per_user = 1, $starts_at = null,
                                $conditions = null)
    {
        return $this->createVouchers(1, $data, $expires_at,$quantity,$type,$value,$user_id,$quantity_per_user,
                                    $starts_at, $conditions)[0];
    }
}
