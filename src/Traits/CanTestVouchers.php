<?php

namespace BeyondCode\Vouchers\Traits;

use App\Models\User;
use BeyondCode\Vouchers\Exceptions\VoucherSoldOut;
use BeyondCode\Vouchers\Facades\Vouchers;
use BeyondCode\Vouchers\Models\Voucher;
use BeyondCode\Vouchers\Exceptions\VoucherExpired;
use BeyondCode\Vouchers\Exceptions\VoucherIsInvalid;
use BeyondCode\Vouchers\Exceptions\VoucherAlreadyRedeemed;

trait CanTestVouchers
{
    public function checkByCode(string $code, $user = null,$additionalData = [], $voucherModel = null)
    {
        return Vouchers::checkByCode($code, $user, $additionalData, $voucherModel);
    }

    public function checkForRedeemByCode($user, string $code,$additionalData = [], $voucherModel = null)
    {
        return Vouchers::checkForRedeemByCode($user, $code, $additionalData, $voucherModel);
    }
}
