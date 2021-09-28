<?php

namespace BeyondCode\Vouchers\Rules;

use BeyondCode\Vouchers\Exceptions\VoucherConditionFails;
use BeyondCode\Vouchers\Exceptions\VoucherNotStarted;
use BeyondCode\Vouchers\Facades\Vouchers;
use Illuminate\Contracts\Validation\Rule;
use BeyondCode\Vouchers\Exceptions\VoucherExpired;
use BeyondCode\Vouchers\Exceptions\VoucherIsInvalid;
use BeyondCode\Vouchers\Exceptions\VoucherAlreadyRedeemed;
use BeyondCode\Vouchers\Exceptions\VoucherSoldOut;

class Voucher implements Rule
{
    protected $isInvalid = false;
    protected $isExpired = false;
    protected $wasRedeemed = false;
    protected $isSoldOut = false;
    protected $isNotStarted = false;
    protected $customConditionFails = false;

    /**
     * Determine if the validation rule passes.
     *
     * @param  string $attribute
     * @param  mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        try {
            $voucher = Vouchers::checkByCode($value);

            // Check if the voucher was already redeemed
            if (auth()->check() && $voucher->users()->wherePivot('user_id', auth()->id())->exists()) {
                throw VoucherAlreadyRedeemed::create($voucher);
            }
        } catch (VoucherIsInvalid $exception) {
            $this->isInvalid = true;
            return false;
        } catch (VoucherNotStarted $exception) {
            $this->isNotStarted = true;
            return false;
        } catch (VoucherExpired $exception) {
            $this->isExpired = true;
            return false;
        } catch (VoucherAlreadyRedeemed $exception) {
            $this->wasRedeemed = true;
            return false;
        } catch (VoucherSoldOut $exception) {
            $this->isSoldOut = true;
            return false;
        } catch (VoucherConditionFails $exception) {
            $this->customConditionFails = $exception->getMessage();
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        if ($this->wasRedeemed) {
            return trans('vouchers::validation.code_redeemed');
        }
        if ($this->isExpired) {
            return trans('vouchers::validation.code_expired');
        }
        if ($this->isNotStarted) {
            return trans('vouchers::validation.code_not_started');
        }
        if ($this->isSoldOut) {
            return trans('vouchers::validation.code_sold_out');
        }
        if ($this->customConditionFails) {
            return $this->customConditionFails;
        }
        return trans('vouchers::validation.code_invalid');
    }
}
