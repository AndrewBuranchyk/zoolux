<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use App\Http\Requests\IncomeRequest;
use App\Services\ExternalApiRequests\FreekassaRequests;

class FinanceController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index(): \Illuminate\View\View
    {
        return view('finances');
    }

    /**
     * Display a listing of the user finances.
     *
     * @return \Illuminate\View\View
     */
    public function myFinancesIndex(): \Illuminate\View\View
    {
        return view('my-finances');
    }

    /**
     * Display a listing of the user referrals.
     *
     * @return \Illuminate\View\View
     */
    public function myReferralsIndex(): \Illuminate\View\View
    {
        return view('my-referrals');
    }

    /**
     * Create a new payment form.
     *
     * @return \Illuminate\View\View
     */
    public function createPaymentForm(): \Illuminate\View\View
    {
        return view('income');
    }

    /**
     * Send a new payment form to payment system.
     *
     * @param  IncomeRequest  $request
     * @param  FreekassaRequests  $freekassa
     * @return RedirectResponse
     */
    public function sendFormToPaymentSystem(IncomeRequest $request, FreekassaRequests $freekassa): RedirectResponse
    {
        $validated = $request->validated();

        $url = $freekassa->makePaymentUrl([
            'amount' => $validated['amount'] ?? '',
            'merchantOrderId' => auth()->user()->external_id ?? '',
        ]);

        return redirect($url);
    }

    /**
     * Create a new or edit the specified outcome request.
     *
     * @return \Illuminate\View\View
     */
    public function createOrEditWithdrawal(): \Illuminate\View\View
    {
        return view('withdrawal');
    }
}
