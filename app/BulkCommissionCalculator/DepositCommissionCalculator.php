<?php


namespace App\BulkCommissionCalculator;


class DepositCommissionCalculator extends AbstractCommissionCalculator
{
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
        $commission_rate = config('commission.commission_rate.deposit.business');
        $this->calculateAndAddOutput($this->business_inputs, $commission_rate);
    }

    protected function calculatePrivateInputs()
    {
        $commission_rate = config('commission.commission_rate.deposit.private');
        $this->calculateAndAddOutput($this->private_inputs, $commission_rate);
    }

    private function calculateAndAddOutput($inputs, $commission_rate)
    {
        foreach ($inputs as $deposit_transactions) {
            foreach ($deposit_transactions as $transaction) {
                $commission = $transaction['amount'] * ($commission_rate/100);
                $this->addOutput($transaction['input_trace'], $commission);
            }
        }
    }
}
