<?php

namespace App\Http\Controllers\Gateway;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Models\AdminNotification;
use App\Models\Deposit;
use App\Models\GatewayCurrency;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserPick;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function deposit()
    {
        $gatewayCurrency = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', Status::ENABLE);
        })->with('method')->orderby('method_code')->get();

        $userPick  = null;
        $pageTitle = 'Purchase ' . coinName();

        if (request()->user_pick) {
            try {
                $userPickId = decrypt(request()->user_pick);
            } catch (\Throwable $th) {
                $notify[] = ['error', 'Invalid Request'];
                return back()->withNotify($notify);
            }

            $userPick = UserPick::with('userMultiDraw')->where('user_id', auth()->id())->find($userPickId);
            if (!$userPick) {
                $notify[] = ['error', 'Invalid Request'];
                return back()->withNotify($notify);
            }

            $pageTitle = 'Payment Methods';
        }

        return view($this->activeTemplate . 'user.payment.deposit', compact('gatewayCurrency', 'pageTitle', 'userPick'));
    }

    public function depositInsert(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'gateway' => 'required',
            'currency' => 'required',
        ]);

        $user = auth()->user();
        $gate = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', Status::ENABLE);
        })->where('method_code', $request->gateway)->where('currency', $request->currency)->first();
        if (!$gate) {
            $notify[] = ['error', 'Invalid gateway'];
            return back()->withNotify($notify);
        }

        $amount = $request->amount;
        $userPick = null;
        if ($request->user_pick_id) {
            $userPick = UserPick::with('userMultiDraw')->where('id', $request->user_pick_id)->where('user_id', auth()->id())->first();
            if (!$userPick) {
                $notify[] = ['error', 'Lottery picking history not found'];
                return back()->withNotify($notify);
            }
            $amount = $userPick->amount + ($userPick->userMultiDraw->advance ?? 0);
            if ($amount > $request->amount) {
                $notify[] = ['error', 'Amount can\'t be less than ticket price'];
                return back()->withNotify($notify);
            }
        }

        if ($gate->min_amount > $amount || $gate->max_amount < $amount) {
            $notify[] = ['error', 'Please follow payment limit'];
            return back()->withNotify($notify);
        }

        $charge = $gate->fixed_charge + ($amount * $gate->percent_charge / 100);
        $payable = $amount + $charge;
        $final_amo = $payable * $gate->rate;

        $deposit = new Deposit();
        $deposit->user_id = $user->id;
        $deposit->user_pick_id = @$userPick->id ?? 0;
        $deposit->method_code = $gate->method_code;
        $deposit->method_currency = strtoupper($gate->currency);
        $deposit->amount = $amount;
        $deposit->charge = $charge;
        $deposit->rate = $gate->rate;
        $deposit->final_amo = $final_amo;
        $deposit->btc_amo = 0;
        $deposit->btc_wallet = "";
        $deposit->trx = getTrx();
        $deposit->save();
        session()->put('Track', $deposit->trx);
        return to_route('user.deposit.confirm');
    }


    public function appDepositConfirm($hash)
    {
        try {
            $id = decrypt($hash);
        } catch (\Exception $ex) {
            return "Sorry, invalid URL.";
        }
        $data = Deposit::where('id', $id)->where('status', Status::PAYMENT_INITIATE)->orderBy('id', 'DESC')->firstOrFail();
        $user = User::findOrFail($data->user_id);
        auth()->login($user);
        session()->put('Track', $data->trx);
        return to_route('user.deposit.confirm');
    }


    public function depositConfirm()
    {
        $track = session()->get('Track');
        $deposit = Deposit::where('trx', $track)->where('status', Status::PAYMENT_INITIATE)->orderBy('id', 'DESC')->with('gateway')->firstOrFail();

        if ($deposit->method_code >= 1000) {
            return to_route('user.deposit.manual.confirm');
        }


        $dirName = $deposit->gateway->alias;
        $new = __NAMESPACE__ . '\\' . $dirName . '\\ProcessController';

        $data = $new::process($deposit);
        $data = json_decode($data);


        if (isset($data->error)) {
            $notify[] = ['error', $data->message];
            return to_route(gatewayRedirectUrl())->withNotify($notify);
        }
        if (isset($data->redirect)) {
            return redirect($data->redirect_url);
        }

        // for Stripe V3
        if (@$data->session) {
            $deposit->btc_wallet = $data->session->id;
            $deposit->save();
        }

        $pageTitle = 'Payment Confirm';
        return view($this->activeTemplate . $data->view, compact('data', 'pageTitle', 'deposit'));
    }


    public static function userDataUpdate($deposit, $isManual = null)
    {
        if ($deposit->status == Status::PAYMENT_INITIATE || $deposit->status == Status::PAYMENT_PENDING) {
            $deposit->status = Status::PAYMENT_SUCCESS;
            $deposit->save();

            $user = User::with('referrer')->find($deposit->user_id);
            $user->balance += $deposit->amount;
            $user->save();

            $transaction = new Transaction();
            $transaction->user_id = $deposit->user_id;
            $transaction->amount = $deposit->amount;
            $transaction->post_balance = $user->balance;
            $transaction->charge = $deposit->charge;
            $transaction->trx_type = '+';
            $transaction->details = 'Payment for purchase ' . coinName();
            $transaction->trx = $deposit->trx;
            $transaction->remark = 'payment';
            $transaction->save();

            if (gs('deposit_commission')) {
                levelCommission($user, $deposit->amount, 'deposit_commission', $deposit->trx);
            }

            if (!$isManual) {
                $adminNotification = new AdminNotification();
                $adminNotification->user_id = $user->id;
                $adminNotification->title = 'Payment successful via ' . $deposit->gatewayCurrency()->name;
                $adminNotification->click_url = urlPath('admin.deposit.successful');
                $adminNotification->save();
            }

            notify($user, $isManual ? 'DEPOSIT_APPROVE' : 'DEPOSIT_COMPLETE', [
                'method_name' => $deposit->gatewayCurrency()->name,
                'method_currency' => $deposit->method_currency,
                'method_amount' => showAmount($deposit->final_amo),
                'amount' => showAmount($deposit->amount),
                'charge' => showAmount($deposit->charge),
                'rate' => showAmount($deposit->rate),
                'trx' => $deposit->trx,
                'post_balance' => showAmount($user->balance)
            ]);

            if ($deposit->user_pick_id) {
                $userPick = UserPick::with('userMultiDraw')->find($deposit->user_pick_id);

                $user->balance -= $deposit->amount;
                $user->save();

                $transaction = new Transaction();
                $transaction->user_id = $deposit->user_id;
                $transaction->amount = $userPick->amount;
                $transaction->post_balance = $user->balance;
                $transaction->charge = 0;
                $transaction->trx_type = '-';
                $transaction->details = 'Payment for purchase ticket';
                $transaction->trx = $deposit->trx;
                $transaction->remark = 'payment';
                $transaction->save();

                $userPick = UserPick::with('phase.lottery', 'pickedTickets')->find($deposit->user_pick_id);
                $userPick->status = Status::PAYMENT_SUCCESS;
                $userPick->save();
                $lottery = $userPick->phase->lottery;

                notify($user, 'PURCHASE_COMPLETE', [
                    'trx'            => $transaction->trx,
                    'lottery'        => $lottery->name,
                    'price'          => showAmount($lottery->price, exceptZeros: true),
                    'total_ticket'   => count($userPick->pickedTickets),
                    'total_price'    => showAmount($deposit->amount),
                    'post_balance'   => showAmount($user->balance)
                ]);

                if (gs('lottery_purchase_commission')) {
                    levelCommission($user, $deposit->amount, 'lottery_purchase_commission', $deposit->trx);
                }
            }
        }
    }

    public function manualDepositConfirm()
    {
        $track = session()->get('Track');
        $data = Deposit::with('gateway')->where('status', Status::PAYMENT_INITIATE)->where('trx', $track)->first();
        if (!$data) {
            return to_route(gatewayRedirectUrl());
        }
        if ($data->method_code > 999) {

            $pageTitle = $data->user_pick_id ? 'Payment Confirm' : 'Deposit Confirm';
            $method = $data->gatewayCurrency();
            $gateway = $method->method;
            return view($this->activeTemplate . 'user.payment.manual', compact('data', 'pageTitle', 'method', 'gateway'));
        }
        abort(404);
    }

    public function manualDepositUpdate(Request $request)
    {
        $track = session()->get('Track');
        $data = Deposit::with('gateway')->where('status', Status::PAYMENT_INITIATE)->where('trx', $track)->first();
        if (!$data) {
            return to_route(gatewayRedirectUrl());
        }
        $gatewayCurrency = $data->gatewayCurrency();
        $gateway = $gatewayCurrency->method;
        $formData = $gateway->form->form_data;

        $formProcessor = new FormProcessor();
        $validationRule = $formProcessor->valueValidation($formData);
        $request->validate($validationRule);
        $userData = $formProcessor->processFormData($request, $formData);


        $data->detail = $userData;
        $data->status = Status::PAYMENT_PENDING;
        $data->save();


        $adminNotification = new AdminNotification();
        $adminNotification->user_id = $data->user->id;
        $adminNotification->title =  'Payment request from ' . $data->user->username;
        $adminNotification->click_url = urlPath('admin.deposit.details', $data->id);
        $adminNotification->save();

        if ($data->user_pick_id) {
            $userPick = UserPick::with('phase.lottery')->find($data->user_pick_id);
            $userPick->status = Status::PAYMENT_PENDING;
            $userPick->save();

            notify($data->user, 'PAYMENT_REQUEST', [
                'lottery' => @$userPick->phase->lottery->name . '(' . showPhase($userPick->phase->phase_no) . ')',
                'method_name' => $data->gatewayCurrency()->name,
                'method_currency' => $data->method_currency,
                'method_amount' => showAmount($data->final_amo),
                'amount' => showAmount($data->amount),
                'charge' => showAmount($data->charge),
                'rate' => showAmount($data->rate),
                'trx' => $data->trx
            ]);
        } else {

            notify($data->user, 'DEPOSIT_REQUEST', [
                'method_name' => $data->gatewayCurrency()->name,
                'method_currency' => $data->method_currency,
                'method_amount' => showAmount($data->final_amo),
                'amount' => showAmount($data->amount),
                'charge' => showAmount($data->charge),
                'rate' => showAmount($data->rate),
                'trx' => $data->trx
            ]);
        }

        $notify[] = ['success', 'Your payment request has been taken'];
        return to_route('user.deposit.history')->withNotify($notify);
    }
}
