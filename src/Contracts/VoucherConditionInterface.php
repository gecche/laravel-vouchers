<?php

namespace BeyondCode\Vouchers\Contracts;

use BeyondCode\Vouchers\Events\VoucherRedeemed;
use BeyondCode\Vouchers\Exceptions\VoucherAlreadyRedeemed;
use BeyondCode\Vouchers\Exceptions\VoucherExpired;
use BeyondCode\Vouchers\Exceptions\VoucherIsInvalid;
use BeyondCode\Vouchers\Exceptions\VoucherNotForThatUser;
use BeyondCode\Vouchers\Exceptions\VoucherSoldOut;
use BeyondCode\Vouchers\Models\Voucher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

interface VoucherConditionInterface
{

    /**
     * Generate the specified amount of codes and return
     * an array with all the generated codes.
     *
     * @return bool
     */
    public function check(): bool;

    public function getMessage(): string;

}
