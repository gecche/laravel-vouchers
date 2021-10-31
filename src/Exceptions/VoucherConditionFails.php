<?php

namespace BeyondCode\Vouchers\Exceptions;

use BeyondCode\Vouchers\Contracts\VoucherConditionInterface;
use Illuminate\Database\Eloquent\Model;

class VoucherConditionFails extends \Exception
{
    protected $message = 'The voucher does not the respect a custom condition.';

    protected $condition;

    public static function create(VoucherConditionInterface $condition)
    {
        return new static($condition);
    }

    public function __construct(VoucherConditionInterface $condition)
    {
        $this->condition = $condition;
        $this->message = $this->condition->getMessage();
    }

}
