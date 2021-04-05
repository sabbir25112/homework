<?php


namespace App\BulkCommissionCalculator;


abstract class AbstractCommissionCalculator implements CommissionCalculationInterface
{
    protected $output_trace = [];
    protected $inputs = [];
    protected $private_inputs = [];
    protected $business_inputs = [];

    abstract protected function calculateBusinessInputs();
    abstract protected function calculatePrivateInputs();

    protected function addOutput($input_trace = null, $output): void
    {
        if ($input_trace === null)
            $this->output_trace[] = $output;
        else
            $this->output_trace[$input_trace] = $output;
    }

    public function getOutput(): array
    {
        return $this->output_trace;
    }

    public function setInput(array $input): self
    {
        $this->inputs = $input;
        return $this;
    }
}
