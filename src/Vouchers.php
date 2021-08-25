<?php

namespace BeyondCode\Vouchers;

use BeyondCode\Vouchers\Events\VoucherRedeemed;
use BeyondCode\Vouchers\Exceptions\VoucherAlreadyRedeemed;
use BeyondCode\Vouchers\Exceptions\VoucherExpired;
use BeyondCode\Vouchers\Exceptions\VoucherIsInvalid;
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
    public function create(Model $model, int $amount = 1, array $data = [], $expires_at = null, $quantity = null)
    {
        $vouchers = [];

        foreach ($this->generate($amount) as $voucherCode) {
            $vouchers[] = $this->voucherModel->create([
                'model_id' => $model->getKey(),
                'model_type' => $model->getMorphClass(),
                'code' => $voucherCode,
                'data' => $data,
                'expires_at' => $expires_at,
                'quantity' => $quantity,
                'quantity_left' => $quantity,
            ]);
        }

        return $vouchers;
    }

    /**
     * @param string $code
     * @return Voucher
     * @throws VoucherExpired
     * @throws VoucherIsInvalid
     */
    public function check(Voucher $voucher)
    {
        if ($voucher->isExpired()) {
            throw VoucherExpired::create($voucher);
        }
        if ($voucher->isSoldout()) {
            throw VoucherSoldOut::create($voucher);
        }

        return $voucher;
    }

    public function checkByCode(string $code)
    {
        $voucher = $this->voucherModel->whereCode($code)->first();

        if (is_null($voucher)) {
            throw VoucherIsInvalid::withCode($code);
        }

        return $this->check($voucher);

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


    protected function redeem($user, Voucher $voucher)
    {

        if ($voucher->users()->wherePivot('user_id', $user->id)->exists()) {
            throw VoucherAlreadyRedeemed::create($voucher);
        }

        if (!$voucher->hasLimitedQuantity()) {
            $user->vouchers()->attach($voucher, [
                'redeemed_at' => now()
            ]);
        } else {

            DB::beginTransaction();

            try {
                if ($voucher->isSoldOut()) {
                    throw VoucherSoldOut::create($voucher);
                }
                $user->vouchers()->attach($voucher, [
                    'redeemed_at' => now()
                ]);
                $voucher->update(['quantity_left' => $voucher->quantity_left - 1]);
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

            DB::commit();
        }

        event(new VoucherRedeemed($user, $this));
        return $voucher;

    }

    public function redeemCode($user, string $code)
    {
        $voucher = $this->checkByCode($code);

        return $this->redeem($user, $voucher);
    }

    public function redeemVoucher($user, Voucher $voucher)
    {

        $this->check($voucher);

        return $this->redeem($user, $voucher);

    }
}
