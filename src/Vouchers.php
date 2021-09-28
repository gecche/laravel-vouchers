<?php

namespace BeyondCode\Vouchers;

use BeyondCode\Vouchers\Events\VoucherRedeemed;
use BeyondCode\Vouchers\Exceptions\VoucherAlreadyRedeemed;
use BeyondCode\Vouchers\Exceptions\VoucherConditionFails;
use BeyondCode\Vouchers\Exceptions\VoucherExpired;
use BeyondCode\Vouchers\Exceptions\VoucherIsInvalid;
use BeyondCode\Vouchers\Exceptions\VoucherNotForThatUser;
use BeyondCode\Vouchers\Exceptions\VoucherSoldOut;
use BeyondCode\Vouchers\Models\Voucher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Vouchers
{
    /** @var VoucherGenerator */
    private $generator;
    /** @var \BeyondCode\Vouchers\Models\Voucher */
    private $voucherModel;

    public function __construct(VoucherGenerator $generator)
    {
        $this->generator = $generator;
        $this->voucherModel = app(config('vouchers.model', Voucher::class));
    }

    /**
     * Generate the specified amount of codes and return
     * an array with all the generated codes.
     *
     * @param int $amount
     * @return array
     */
    public function generate(int $amount = 1): array
    {
        $codes = [];

        for ($i = 1; $i <= $amount; $i++) {
            $codes[] = $this->getUniqueVoucher();
        }

        return $codes;
    }

    /**
     * @param Model $model
     * @param int $amount
     * @param array $data
     * @param null $expires_at
     * @return array
     */
    public function create(Model $model = null, int $amount = 1, array $data = [], $expires_at = null, $quantity = null,
                                 $type = 'total', $value = null, $user_id = null, $quantity_per_user = 1, $starts_at = null,
                                $conditions = null)
    {
        $vouchers = [];

        foreach ($this->generate($amount) as $voucherCode) {
            $vouchers[] = $this->voucherModel->create([
                'model_id' => $model ? $model->getKey() : null,
                'model_type' => $model ? $model->getMorphClass() : null,
                'code' => $voucherCode,
                'data' => $data,
                'starts_at' => $starts_at,
                'expires_at' => $expires_at,
                'quantity' => $quantity,
                'quantity_left' => $quantity,
                'type' => $type,
                'value' => $value,
                'user_id' => $user_id,
                'quantity_per_user' => $quantity_per_user,
                'conditions' => $conditions,
            ]);
        }

        return $vouchers;
    }

    /**
     * @param string $code
     * @return Model
     * @throws VoucherExpired
     * @throws VoucherIsInvalid
     */
    public function check(Model $voucher, $user = null)
    {
        if ($voucher->isNotStarted()) {
            throw VoucherExpired::create($voucher);
        }
        if ($voucher->isExpired()) {
            throw VoucherExpired::create($voucher);
        }
        if ($voucher->isSoldout()) {
            throw VoucherSoldOut::create($voucher);
        }
        //THROWS VoucherConditionFails exception
        $voucher->checkConditions($user);
//        $customConditionsErrors = $voucher->checkConditions($user);
//        if (count($customConditionsErrors) > 0) {
//            throw VoucherConditionFails::create($voucher,);
//        }

        return $voucher;
    }

    public function checkByCode(string $code, $user = null)
    {
        $voucher = $this->voucherModel->whereCode($code)->first();

        if (is_null($voucher)) {
            throw VoucherIsInvalid::withCode($code);
        }

        return $this->check($voucher, $user);

    }

    public function checkForRedeemByCode($user, string $code)
    {

        $voucher = $this->voucherModel->whereCode($code)->first();

        if (is_null($voucher)) {
            throw VoucherIsInvalid::withCode($code);
        }

        return $this->checkForRedeem($user, $voucher);

    }

    public function checkForRedeem($user, Model $voucher) {

        $voucher = $this->check($voucher,$user);

        $associatedUserId = $voucher->getAssociatedUserId();
        if ($associatedUserId && $user->id != $associatedUserId) {
            throw VoucherNotForThatUser::create($voucher, $associatedUserId);
        }

        $quantityPerUser = $voucher->getQuantityPerUser();
        if (!is_null($quantityPerUser) && $voucher->users()
                ->wherePivot('user_id', $user->id)->count() >= $quantityPerUser) {
            throw VoucherAlreadyRedeemed::create($voucher);
        }
        return $voucher;
    }

    /**
     * @return string
     */
    protected function getUniqueVoucher(): string
    {
        $voucher = $this->generator->generateUnique();

        while ($this->voucherModel->whereCode($voucher)->count() > 0) {
            $voucher = $this->generator->generateUnique();
        }

        return $voucher;
    }


    protected function redeem($user, Model $voucher, $useTransaction = true)
    {

        $voucher = $this->checkForRedeem($user,$voucher);
        $quantityPerUser = $voucher->getQuantityPerUser();

        if (!$voucher->hasLimitedQuantity() && is_null($quantityPerUser)) {
            $user->vouchers()->attach($voucher, [
                'redeemed_at' => now()
            ]);
        } else {

            if ($useTransaction) {
                DB::beginTransaction();
                try {
                    $this->redeemWithQuantity($user, $voucher, $quantityPerUser);
                } catch (\Exception $e) {
                    DB::rollback();
                    throw $e;
                }

                DB::commit();
            } else {
                $this->redeemWithQuantity($user, $voucher, $quantityPerUser);
            }
        }

        event(new VoucherRedeemed($user, $voucher));
        return $voucher;

    }

    protected function redeemWithQuantity($user, $voucher, $quantityPerUser)
    {

        if ($voucher->hasLimitedQuantity() && $voucher->isSoldOut()) {
            throw VoucherSoldOut::create($voucher);
        }
        if ($quantityPerUser && $voucher->users()
                ->wherePivot('user_id', $user->id)->count() >= $quantityPerUser) {
            throw VoucherAlreadyRedeemed::create($voucher);
        }

        $user->vouchers()->attach($voucher, [
            'redeemed_at' => now()
        ]);
        if ($voucher->hasLimitedQuantity()) {
            $voucher->update(['quantity_left' => $voucher->quantity_left - 1]);
        }

        return $voucher;
    }

    public function redeemCode($user, string $code, $useTransaction = true)
    {
        $voucher = $this->checkByCode($code);

        return $this->redeem($user, $voucher, $useTransaction);
    }

    public function redeemVoucher($user, Model $voucher, $useTransaction = true)
    {

        $this->check($voucher,$user);

        return $this->redeem($user, $voucher, $useTransaction);

    }
}
