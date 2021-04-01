<?php


namespace App\BulkCommissionCalculator;


use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class WithdrawCommissionCalculator extends AbstractCommissionCalculator
{
    private $currencyConverter;

    public function __construct(CurrencyConverter $currencyConverter)
    {
        $this->currencyConverter = $currencyConverter;
    }

    public function calculate(): self
    {
        $input_collection = collect($this->inputs);
        if ($input_collection->has('private')) {
            $this->private_inputs = $input_collection->get('private');
            $this->calculatePrivateInputs();
        }
        if ($input_collection->has('business')) {
            $this->business_inputs = $input_collection->get('business');
            $this->calculateBusinessInputs();
        }
        return $this;
    }

    protected function calculateBusinessInputs()
    {
        $commission_rate = config('commission.commission_rate.withdraw.business');
        foreach ($this->business_inputs as $business_withdraw_transactions) {
            foreach ($business_withdraw_transactions as $transaction) {
                $commission = $transaction['amount'] * ($commission_rate/100);
                $this->addOutput($transaction['input_trace'], $commission);
            }
        }
    }

    protected function calculatePrivateInputs()
    {
        $base_currency = config('commission.commission_rate.withdraw.weekly_discount.base_currency');
        $maximum_free_withdraw = config('commission.commission_rate.withdraw.weekly_discount.maximum_free_withdraw');
        $maximum_free_amount = config('commission.commission_rate.withdraw.weekly_discount.maximum_free_amount');
        $commission_rate = config('commission.commission_rate.withdraw.private');

        foreach ($this->private_inputs as $users_withdraw_transactions) {
            $weekly_chunk = $this->toWeeklyChunk($users_withdraw_transactions);
            foreach ($weekly_chunk as $user_weekly_transactions) {
                $transactions_this_week = 0;
                $weekly_transaction_amount_in_base_currency = 0;
                foreach ($user_weekly_transactions as $transaction) {
                    try {
                        $transactions_this_week++;
                        $transaction_amount_in_base_currency = $transaction['amount'];
                        $commission = 0;
                        if ($transaction['currency'] !== $base_currency) {
                            $converter_response = $this->currencyConverter->convert($transaction['amount'], $transaction['currency'], $base_currency, $transaction['date']);
                            $transaction_amount_in_base_currency = $converter_response['to_amount'];
                        }

                        $weekly_transaction_amount_in_base_currency += $transaction_amount_in_base_currency;
                        if ($transactions_this_week > $maximum_free_withdraw) {
                            $commission = $transaction_amount_in_base_currency * ($commission_rate/100);
                        } elseif ($weekly_transaction_amount_in_base_currency > $maximum_free_amount) {
                            $commission = ($weekly_transaction_amount_in_base_currency - $maximum_free_amount) * ($commission_rate/100);
                            /*
                             * weekly transaction amount tacking variable set to maximum_fee_amount,
                             * because it ensures nobody pay twice for the same transaction
                             * */
                            $weekly_transaction_amount_in_base_currency = $maximum_free_amount;
                        }
                        if ($transaction['currency'] !== $base_currency) {
                            $converter_response = $this->currencyConverter->convert($commission, $base_currency, $transaction['currency'], $transaction['date']);
                            $commission = $converter_response['to_amount'];
                        }
                        $this->addOutput($transaction['input_trace'], $commission);
                    } catch (\Exception $exception) {
                        Log::error($exception);

                        $output_message = $exception->getMessage() . ' in ' . $exception->getFile() . ' at ' . $exception->getLine();
                        $this->addOutput($transaction['input_trace'], $output_message);
                        continue;
                    }
                }
            }
        }
    }

    private function toWeeklyChunk($user_transactions): array
    {
        $week_wise_sorted_transactions = [];
        $date_wise_sorted_transactions = collect($user_transactions)->sortBy('date')->values()->all();
        foreach ($date_wise_sorted_transactions as $transaction) {
            $date = Carbon::createFromFormat('Y-m-d', $transaction['date']);
            $start_of_week = $date->startOfWeek(Carbon::MONDAY)
                ->endOfWeek(Carbon::SUNDAY)
                ->startOfWeek()
                ->toDateString()
            ;
            $week_wise_sorted_transactions[$start_of_week][] = $transaction;
        }
        return $week_wise_sorted_transactions;
    }
}
