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
    public function redeemCode(string $code)
    {
        return Vouchers::redeemByCode($this,$code);

    }

    /**
     * @param Voucher $voucher
     * @throws VoucherExpired
     * @throws VoucherIsInvalid
     * @throws VoucherAlreadyRedeemed
     * @return mixed
     */
    public function redeemVoucher(Voucher $voucher)
    {
        return Vouchers::redeem($this,$voucher);
    }

    /**
     * @return mixed
     */
    public function vouchers()
    {
        return $this->belongsToMany(Voucher::class)->withPivot('redeemed_at');
    }
}
