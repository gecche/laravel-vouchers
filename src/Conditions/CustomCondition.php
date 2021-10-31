<?php

namespace BeyondCode\Vouchers\Conditions;

use BeyondCode\Vouchers\Contracts\VoucherConditionInterface;
use Illuminate\Support\Arr;

abstract class CustomCondition implements VoucherConditionInterface
{


    protected $message = "Custom condition fails";
    protected $model;
    protected $user;
    protected $additionalData;

    /**
     * ProdottiAmmessi constructor.
     * @param $model
     * @param $user
     * @param $additionalData
     */
    public function __construct($model, $user, $additionalData)
    {
        $this->model = $model;
        $this->user = $user;
        $this->additionalData = $additionalData;
    }


    abstract public function check() :bool;

    public function getMessage() :string {
        $transKey = 'vouchers::validation.'.$this->message;
        $transResult = trans('vouchers::validation.'.$this->message);
        return $transResult === $transKey ? $this->message : $transResult;
    }

}
