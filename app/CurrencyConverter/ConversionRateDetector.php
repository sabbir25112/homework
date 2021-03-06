<?php


namespace App\CurrencyConverter;


use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConversionRateDetector
{
    use CommonPropertyGetterSetterTrait;

    private $from_currency;
    private $to_currency;
    private $date;

    public function getConversionRate(string $from_currency = null, string $to_currency = null, string $date = null): float
    {
        try {
            $this->mapInputs($from_currency, $to_currency, $date);

            $response = Http::get($this->getApiEndpoint());

            if ($response->clientError()) throw new \Exception("Currency Conversion Http Client Error");

            if ($response->serverError()) throw new \Exception("Currency Conversion Server Error");

            if ($response->failed()) throw new \Exception("Currency Conversion Failed");

            if ($response->successful()) {
                $json_response = $response->json();

                if ($json_response['success'] === true) {
                    /*
                     * Because we just have access to one base currency,
                     * we need to check responded base currency with to_currency and from_currency
                     * */
                    if ($this->from_currency == $json_response['base']) {
                        $result = $json_response['rates'][$this->to_currency];
                    } elseif ($this->to_currency == $json_response['base']) {
                        $result = 1.00 / (float) $json_response['rates'][$this->from_currency];
                    } else {
                        /*
                         * I hope it'll never happen, but just a precaution
                         * */
                        throw new \Exception("Base is not from currency or to currency. It's something else");
                    }
                    return (float) $result;
                } else {

                    // For all the errors generated by API EXCHANGE END
                    $error_message = $json_response['error']['type'] . ' ERROR in API EXCHANGE';

                    Log::error($error_message);
                    throw new \Exception($error_message);
                }
            }
        } catch (\Exception $exception) {
            Log::error($exception);
            throw $exception;
        }
    }

    private function mapInputs($from_currency = null, $to_currency = null, $date = null)
    {
        if ($from_currency === null && $this->from_currency === null)
            throw new \Exception("No Base Currency Found");

        if ($to_currency === null && $this->to_currency === null)
            throw new \Exception("No Destination Currency Found");

        if ($date === null && $this->date === null)
            $this->date = Carbon::today()->toDateString();

        $this->from_currency = $from_currency === null ? $this->from_currency : $from_currency;
        $this->to_currency = $to_currency === null ? $this->to_currency : $to_currency;
        $this->date = $date === null ? $this->date : $date;
    }

    private function getApiEndpoint(): string
    {
        // Base Currency Had to Change for Subscription Issue
        // '?base='. $this->from_currency .

        return config('url.currency_conversion_base_url'). $this->date .  '?access_key=' . config('commission.currency_exchange.access_key');
    }
}
