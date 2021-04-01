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
    private $input_collection;


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
            $file_path = $this->argument('file_path');
            $csv_input_sanitizer = $this->csvInputSanitizer->setFile($file_path);

            $this->input_collection = $csv_input_sanitizer->parse();

            // store total raw input to show output for unprocessed inputs
            $this->number_of_raw_input = $csv_input_sanitizer->total_raw_input;

            if ($this->input_collection->count() > 0) {
                if ($this->input_collection->has('deposit')) {
                    $deposit_inputs = $this->input_collection->get('deposit');

                    $this->deposit_output = $this->depositCalculator
                        ->setInput($deposit_inputs)
                        ->calculate()
                        ->getOutput()
                    ;
                }
                if ($this->input_collection->has('withdraw')) {
                    $withdraw_inputs = $this->input_collection->get('withdraw');

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
            $console_error_message = $exception->getMessage() . ' in ' . $exception->getFile() . ' at ' . $exception->getLine();
            $this->error($console_error_message);
        }
    }

    private function showOutput()
    {
        $combined_outputs = $this->deposit_output + $this->withdraw_output;
        // sort the $combined_outputs by key for ensuring correct output sequence
        ksort($combined_outputs);

        for ($i = 0; $i < $this->number_of_raw_input; $i++) {
            if (isset($combined_outputs[$i])) {
                $output = $combined_outputs[$i];
                if (is_float($output) || is_int($output)) {
                    $this->line(number_format($output, 2, '.', ''));
                } else {
                    /*
                     * this is for showing the errors for unprocessed transactions
                     * both external and internal
                     * */
                    $this->info($output);
                }
            } else {
                /*
                 * this is for showing the incomplete/invalid inputs
                 * don't have much time for specific message
                 * */
                $this->error("Unprocessable Entity");
            }
        }
    }
}
