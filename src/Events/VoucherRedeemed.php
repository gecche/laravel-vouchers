<?php

namespace BeyondCode\Vouchers\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;

class VoucherRedeemed
{
    use SerializesModels;

    public $user;

    /** @var Model */
    public $voucher;

    public function __construct($user, Model $voucher)
    {
        $this->user = $user;
        $this->voucher = $voucher;
    }
}
