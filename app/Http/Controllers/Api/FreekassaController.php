<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FreekassaPaymentNotificationRequest;
use App\Models\{User, Finance};
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class FreekassaController extends Controller
{
    /**
     * Handle an incoming Freekassa payment notification request.
     *
     * @param  FreekassaPaymentNotificationRequest  $request
     * @param  User  $user
     * @return Response
     */
    public function store(FreekassaPaymentNotificationRequest $request, User $user): Response
    {
        $validated = $request->validated();

        DB::transaction(function () use($validated, $user, $request) {
            $user = $user->where('external_id', $validated['MERCHANT_ORDER_ID'])
                ->lockForUpdate()->first();

            $status = '';
            if (in_array($request->ip(), ['54.86.50.139','93.175.195.14'])) {
                $status = 'faked';
            }

            $amount = $validated['AMOUNT'];

            if (!empty($user->referral_owner_id)) {
                if ($referralOwner = User::where('external_id', $user->referral_owner_id)
                    ->lockForUpdate()->first()) {
                    if ($referralOwner->referral_fee > 0 && $referralOwner->referral_fee < 100) {
                        $referralOwnerAmount = round($amount / 100 * $referralOwner->referral_fee, 2);

                        $amount = $amount - $referralOwnerAmount;

                        Finance::create([
                            'type' => 'referral_in',
                            'is_balance_changed' => 1,
                            'amount' => $referralOwnerAmount,
                            'external_order_id' => 'referral-from-' . $validated['intid'],
                            'internal_order_id' => $referralOwner->external_id,
                            'currency_id' => $validated['CUR_ID'] ?? '',
                            'status' => $status,
                            'referral_subscriber_id' => $user->external_id,
                        ]);

                        $referralOwner->balance = $referralOwner->balance + $referralOwnerAmount;
                        $referralOwner->save();
                    }
                }
            }

            Finance::create([
                'type' => 'in',
                'is_balance_changed' => 1,
                'amount' => $amount,
                'external_order_id' => $validated['intid'],
                'internal_order_id' => $validated['MERCHANT_ORDER_ID'],
                'payer_email' => $validated['P_EMAIL'] ?? '',
                'payer_phone' => $validated['P_PHONE'] ?? '',
                'payer_account' => $validated['payer_account'] ?? '',
                'commission' => $validated['commission'] ?? '',
                'currency_id' => $validated['CUR_ID'] ?? '',
                'status' => $status,
            ]);

            $user->balance = $user->balance + $amount;
            $user->save();
        });

        return response('YES', 200);
    }
}
