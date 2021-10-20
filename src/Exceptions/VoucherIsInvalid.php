<?php

namespace BeyondCode\Vouchers\Exceptions;

class VoucherIsInvalid extends \Exception
{
    protected $code;

    public static function withCode(string $code)
    {
        if ($code == 'null') {
            $code = null;
        }
        if (blank($code)) {
            return new static(trans('vouchers::validation.code_blank'),$code);
        }
        return new static(trans('vouchers::validation.code_invalid',['code' => ($code ?: ' ')]),$code);
    }

    public function __construct($message, $code)
    {
        $this->message = $message;
        $this->code = $code;
    }

    /**
     * @return mixed
     */
    public function getVoucherCode()
    {
        return $this->code;
    }
}
