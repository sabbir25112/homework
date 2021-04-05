<?php


namespace App\CurrencyConverter;


use Carbon\Carbon;

class CurrencyConverter
{
    use CommonPropertyGetterSetterTrait;

    private $amount;
    private $conversionRateDetector;

    public function __construct(ConversionRateDetector $conversionRateDetector)
    {
        $this->conversionRateDetector = $conversionRateDetector;
    }

    /**
     * @param mixed $amount
     * @return CurrencyConverter
     */
    public function setAmount($amount): CurrencyConverter
    {
        $this->amount = $amount;
        return $this;
    }

    public function convert(float $amount = null, string $from_currency = null, string $to_currency = null, string $date = null): array
    {
        try {
            $this->mapInputs($amount, $from_currency, $to_currency, $date);

            $conversion_rate = 1.00;
            if ($this->from_currency !== $this->to_currency) {
                $conversion_rate = $this->conversionRateDetector->getConversionRate($this->from_currency, $this->to_currency, $this->date);
            }
            $to_amount = (float) $this->amount * $conversion_rate;

            if ($this->to_currency === config('commission.japanese_yen')) {
                $to_amount = (int) ceil($to_amount);
            }
            return [
                'from_currency' => $this->from_currency,
                'to_currency'   => $this->to_currency,
                'from_amount'   => $this->amount,
                'to_amount'     => $to_amount,
            ];
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    private function mapInputs(
        $amount = null,
        $from_currency = null,
        $to_currency = null,
        $date = null
    )
    {
        if ($amount === null && $this->amount === null) {
            throw new \Exception("Amount Not Found To Convert");
        }

        if ($from_currency === null && $this->from_currency === null) {
            throw new \Exception("No Base Currency Found To Convert");
        }

        if ($to_currency === null && $this->to_currency === null) {
            throw new \Exception("No Destination Currency Found To Convert");
        }

        if ($date === null && $this->date === null) {
            $this->date = Carbon::today()->toDateString();
        }

        $this->amount = $amount === null ? $this->amount : $amount;
        $this->from_currency = $from_currency === null ? $this->from_currency : $from_currency;
        $this->to_currency = $to_currency === null ? $this->to_currency : $to_currency;
        $this->date = $date === null ? $this->date : $date;
    }
}
