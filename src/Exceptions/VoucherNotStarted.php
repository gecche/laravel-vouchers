<?php

namespace BeyondCode\Vouchers\Exceptions;

use Illuminate\Database\Eloquent\Model;

class VoucherNotStarted extends \Exception
{
    protected $message = 'The voucher is not started.';

    protected $voucher;

    public static function create(Model $voucher)
    {
        return new static($voucher);
    }

    public function __construct(Model $voucher)
    {
        $this->voucher = $voucher;
        $this->message =  trans('vouchers::validation.code_not_started');
    }
}
