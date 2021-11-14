<?php

namespace BeyondCode\Vouchers\Traits;

use BeyondCode\Vouchers\Exceptions\VoucherSoldOut;
use BeyondCode\Vouchers\Facades\Vouchers;
use BeyondCode\Vouchers\Models\Voucher;
use BeyondCode\Vouchers\Exceptions\VoucherExpired;
use BeyondCode\Vouchers\Exceptions\VoucherIsInvalid;
use BeyondCode\Vouchers\Exceptions\VoucherAlreadyRedeemed;

trait CanRedeemVouchers
{
    /**
     * @param string $code
     * @throws VoucherExpired
     * @throws VoucherIsInvalid
     * @throws VoucherAlreadyRedeemed
     * @return mixed
     */
    public function redeemCode(string $code, $useTransaction = true, $additionalData = [], $voucherModel = null)
    {
        return Vouchers::redeemCode($this, $code, $useTransaction, $additionalData, $voucherModel);

    }

    /**
     * @param Voucher $voucher
     * @throws VoucherExpired
     * @throws VoucherIsInvalid
     * @throws VoucherAlreadyRedeemed
     * @return mixed
     */
    public function redeemVoucher(Voucher $voucher, $useTransaction = true, $additionalData = [], $voucherModel = null)
    {
        return Vouchers::redeemVoucher($this, $voucher, $useTransaction, $additionalData, $voucherModel);
    }

    /**
     * @return mixed
     */
    public function vouchers()
    {
        return $this->belongsToMany(Voucher::class)->withPivot('redeemed_at');
    }
}
