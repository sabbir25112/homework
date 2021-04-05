<?php


namespace App\CurrencyConverter;


trait CommonPropertyGetterSetterTrait
{
    private $from_currency;
    private $to_currency;
    private $date;

    /**
     * @param string $from_currency
     * @return self
     */
    public function setFromCurrency(string $from_currency): self
    {
        $this->from_currency = $from_currency;
        return $this;
    }

    /**
     * @param string $to_currency
     * @return self
     */
    public function setToCurrency(string $to_currency): self
    {
        $this->to_currency = $to_currency;
        return $this;
    }

    /**
     * @param mixed $date
     * @return self
     */
    public function setDate($date): self
    {
        $this->date = $date;
        return $this;
    }
}
