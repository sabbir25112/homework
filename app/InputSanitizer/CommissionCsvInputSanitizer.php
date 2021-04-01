<?php


namespace App\InputSanitizer;


use DateTime;
use Illuminate\Support\Facades\Log;

class CommissionCsvInputSanitizer
{
    private $file_path;
    public $total_raw_input = 0;

    public function setFile($file_path): CommissionCsvInputSanitizer
    {
        if (!is_file($file_path)) {
            throw new \Exception("Oops!! this is not a file");
        }
        $this->file_path = $file_path;
        return $this;
    }

    public function parse(): object
    {
        $output = [];
        try {
            $input_trace = 0;
            $file = fopen($this->file_path, "r");
            while(!feof($file))
            {
                $raw_input = fgetcsv($file);
                if ($this->isRawInputProcessable((array) $raw_input)) {
                    $formatted_input = $this->formatRawInput($raw_input);
                    // add input trace for returning output sequentially
                    $formatted_input['input_trace'] = $input_trace;
                    $operation_type = $formatted_input["operation_type"];
                    $user_type = $formatted_input["user_type"];
                    $user_id = $formatted_input["user_id"];
                    $output[$operation_type][$user_type][$user_id][] = $formatted_input;
                }
                $input_trace++;
            }
            fclose($file);
            $this->total_raw_input = $input_trace - 1;
        } catch (\Exception $exception) {
            Log::error($exception);
            $output = [];
        }

        return collect($output);
    }

    private function isRawInputProcessable(array $raw_input): bool
    {
        if (is_array($raw_input) && count($raw_input) === count(config('commission.csv_format'))) {
            // check if the input operation type, user type and currency type is valid
            return (
                in_array(
                    strtolower($raw_input[config('commission.csv_format.operation_type_column')]),
                    config('commission.operation_type')
                )
                && in_array(
                    strtolower($raw_input[config('commission.csv_format.user_type_column')]),
                    config('commission.user_type')
                )
                && in_array(
                    strtoupper($raw_input[config('commission.csv_format.currency_column')]),
                    config('commission.currency_type')
                )
                && DateTime::createFromFormat(config('commission.input_date_format'), $raw_input[config('commission.csv_format.date_column')]) !== false
            );
        }
        return false;
    }

    private function formatRawInput(array $raw_input): array
    {
        return [
            'date'           => $raw_input[config('commission.csv_format.date_column')],
            'user_id'        => $raw_input[config('commission.csv_format.user_id_column')],
            'user_type'      => strtolower($raw_input[config('commission.csv_format.user_type_column')]),
            'operation_type' => strtolower($raw_input[config('commission.csv_format.operation_type_column')]),
            'amount'         => (float) $raw_input[config('commission.csv_format.amount_column')],
            'currency'       => strtoupper($raw_input[config('commission.csv_format.currency_column')]),
        ];
    }
}
