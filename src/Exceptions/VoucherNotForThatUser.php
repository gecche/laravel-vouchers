<?php

namespace BeyondCode\Vouchers\Exceptions;

use BeyondCode\Vouchers\Models\Voucher;

class VoucherNotForThatUser extends \Exception
{
    protected $userId;

    protected $message = 'The voucher is not associated with your account.';

    protected $voucher;

    public static function create(Voucher $voucher,$userId)
    {
        return new static($voucher,$userId);
    }

    public function __construct(Voucher $voucher,$userId)
    {
        $this->voucher = $voucher;
        $this->userId = $userId;
    }
}
