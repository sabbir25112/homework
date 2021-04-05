<?php

namespace App\Console\Commands;

use App\BulkCommissionCalculator\DepositCommissionCalculator;
use App\BulkCommissionCalculator\WithdrawCommissionCalculator;
use App\InputSanitizer\CommissionCsvInputSanitizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CalculateCommissionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calculate:commission {file_path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'calculate commission for given csv file';

    private $deposit_output = [];
    private $withdraw_output = [];
    private $number_of_raw_input = 0;
    private $depositCalculator;
    private $withdrawCalculator;
    private $csvInputSanitizer;
    private $file_path;


    /**
     * Create a new command instance.
     *
     * @param DepositCommissionCalculator $depositCalculator
     * @param WithdrawCommissionCalculator $withdrawCalculator
     * @param CommissionCsvInputSanitizer $csvInputSanitizer
     */
    public function __construct(
        DepositCommissionCalculator $depositCalculator,
        WithdrawCommissionCalculator $withdrawCalculator,
        CommissionCsvInputSanitizer $csvInputSanitizer
    )
    {
        parent::__construct();
        $this->depositCalculator = $depositCalculator;
        $this->withdrawCalculator = $withdrawCalculator;
        $this->csvInputSanitizer = $csvInputSanitizer;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $file_path = $this->file_path !== null ? $this->file_path : $this->argument('file_path');
            $csv_input_sanitizer = $this->csvInputSanitizer->setFile($file_path);

            $input_collection = $csv_input_sanitizer->parse();

            // store total raw input to show output for unprocessed inputs
            $this->number_of_raw_input = $csv_input_sanitizer->total_raw_input;

            if ($input_collection->count() > 0) {
                if ($input_collection->has('deposit')) {
                    $deposit_inputs = $input_collection->get('deposit');

                    $this->deposit_output = $this->depositCalculator
                        ->setInput($deposit_inputs)
                        ->calculate()
                        ->getOutput()
                    ;
                }
                if ($input_collection->has('withdraw')) {
                    $withdraw_inputs = $input_collection->get('withdraw');

                    $this->withdraw_output = $this->withdrawCalculator
                        ->setInput($withdraw_inputs)
                        ->calculate()
                        ->getOutput()
                    ;
                }
            }

            $this->showOutput();
        } catch (\Exception $exception) {
            Log::error($exception);
            throw $exception;
        }
    }

    private function showOutput()
    {
        $combined_outputs = $this->deposit_output + $this->withdraw_output;
        // sort the $combined_outputs by key for ensuring correct output sequence
        ksort($combined_outputs);

        for ($input_trace = 0; $input_trace < $this->number_of_raw_input; $input_trace++) {
            if (isset($combined_outputs[$input_trace])) {
                $output = $combined_outputs[$input_trace];
                if (is_float($output)) {
                    /*
                     * rounding number by two decimal point as per requirement
                     * */
                    $round_number = round(ceil($output * 100 ) / 100,2);
                    echo number_format($round_number, 2, '.', '') . PHP_EOL;
                } elseif (is_int($output)) {
                    echo $output . PHP_EOL;
                } else {
                    /*
                     * this is for showing the errors for unprocessed transactions
                     * both external and internal
                     * */
                    echo $output . PHP_EOL;
                }
            } else {
                /*
                 * this is for showing the incomplete/invalid inputs
                 * don't have much time for specific message
                 * */
                echo "Unprocessable Entity" . PHP_EOL;
            }
        }
    }

    /**
     * @param mixed $file_path
     * @return CalculateCommissionCommand
     * @throws \Exception
     */
    public function setFilePath($file_path): CalculateCommissionCommand
    {
        if (!is_file($file_path)) {
            throw new \Exception("Oops!! this is not a file");
        }
        $this->file_path = $file_path;
        return $this;
    }
}
