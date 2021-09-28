<?php

namespace BeyondCode\Vouchers\Exceptions;

use Illuminate\Database\Eloquent\Model;

class VoucherNotForThatUser extends \Exception
{
    protected $userId;

    protected $message = 'The voucher is not associated with your account.';

    protected $voucher;

    public static function create(Model $voucher,$userId)
    {
        return new static($voucher,$userId);
    }

    public function __construct(Model $voucher,$userId)
    {
        $this->voucher = $voucher;
        $this->userId = $userId;
    }
}
