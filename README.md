<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400"></a></p>

# Solution

The solution is a console command `php arisan calculate:commission {csv_full_path}`

## Setup Project
- `git clone {this-repo}`
- `cd homework`  
- `composer install`
- copy `.env.example` to `.env`
- add `EXCHANGE_RATE_API_ACCESS_KEY={exchange-rate-api-key}` in `.env`

## Run Command

`php arisan calculate:commission {csv_full_path}`

Please provide full file path. Otherwise you'll have `Oops!! this is not a file` error.

## HOW IT WORKS

`CalculateCommissionCommand` class (`handle` method to be specific) handles the artisan command. This class takes input form artisan command argument or method parameter, delegate the inputs to respective classes, combine the outputs and display it sequentially.

Besides `CalculateCommissionCommand`, Basically there are five components of this program.
- `CommissionCsvInputSanitizer` - For sanitizing incomplete / malformed data 
- `DepositCommissionCalculator` - To calculate commission for `deposit` operation
- `WithdrawCommissionCalculator` - To calculate commission for `withdraw` operation
- `CurrencyConverter` - To convert amount of one currency to another currency  
- `ConversionRateDetector` -  It's actually a dependency of `CurrencyConverter` but it can also be used independently

### About `CommissionCsvInputSanitizer`
The main purpose of this class is to parse and sanitize the csv input. This class also arrange the input by `operation_type`, `user_type`, `user_id`, so that the controller process can easily dispatch the inputs to respective handler (`DepositCommissionCalculator` / `DepositCommissionCalculator`). It also appends `input_trace` (csv row index) into every formatted and raw input. `input_trace` is then used to relay the output sequentially. This class also expose some public value like `total_raw_input`, `raw_input`.   

*Input* | File Full Path | `file_path` | `public function setFile($file_path): CommissionCsvInputSanitizer`
--- | ---| --- | --- |
*Output* | a `Collection` object |  | `public function parse(): object`

### About `DepositCommissionCalculator` && `WithdrawCommissionCalculator`
This two classes are responsible for calculating commission of `deposit` and `withdraw` respectively. Both class extends `AbstractCommissionCalculator` which implements `CommissionCalculationInterface` (a common interface for commission calculation).

*`DepositCommissionCalculator`* | *Input* | Transactions | `inputs` | `public function setInput(array $input): self`
--- | --- | ---| --- | --- |
*`WithdrawCommissionCalculator`* | *Output* | a `Collection` object | `output_trace` | `public function getOutput(): array`

~~~ php
    $deposit_commission_output = (new DepsotitCommissionCalculator())
        ->setInput($parsed_deposit_input)
        ->calculate()
        ->getOutput()
    ;
    
    $deposit_commission_output = (new WithdrawCommissionCalculator())
        ->setInput($parsed_withdraw_input)
        ->calculate()
        ->getOutput()
    ;
~~~

### About `ConversionRateDetector`
This class gives the conversion rate of a particular date (by default today). The currency exchange API free tire doesn't support multiple base currency. This class is also an independent module, and a direct dependency of `CurrencyConverter`. 

~~~ php
    $conversionRateDetector = new ConversionRateDetector();
    
    // method 1
    $conversion_rate = $conversionRateDetector->setFromCurrency('EUR')
        ->setToCurrency('USD')
        ->setDate("2020-06-03")
        ->getConversionRate()
    ;
    // method 2
    $conversion_rate = $conversionRateDetector->getConversionRate('EUR', 'USD', "2020-06-03");
    
    // output
    xx.xx (float) 
~~~


### About `CurrencyConverter`
This class converts one currency amount into different currency. By default, it converts currency by today's currency rate but consumer classes can use `setDate` or pass `$date` as (`Y-m-d` format) `convert` method. This class can be used with `getter/seeter`:

>*The Japanese Yen does not use a decimal point. The yen is the lowest value possible in Japanese currency.*

This exception is handled in ConversionRateDetector

~~~ php
    $conversionRateDetector = new ConversionRateDetector();
    $currencyConverter = (new CurrencyConverter($conversionRateDetector));
    
    // method 1
    $output = $currencyConverter->setFromCurrency('EUR')
        ->setToCurrency('USD')
        ->setAmount(100)
        ->setDate("2021-01-01")
        ->convert()
    ;
    
    // method 2
    $output = $currencyConverter->convert(100, 'EUR', 'USD', "2021-01-01");
    
    // output
    [
        'from_currency' => "EUR",
        'to_currency'   => "USD",
        'from_amount'   => 100.00,
        'to_amount'     => xx.xx (float) OR xx (int)
    ]
~~~

## Some Extra Output

The command will definitely return the output in the requirement. But it also add some extra output for showing what goes wrong.
- If any error occurs, it will just skip that transaction and show the error message with file name on output.
- If any input is not provided as described in requirements, it will just remove that transaction for further process. It will give `Unprocessable Entity` as output for that particular line. 

## Dedicated Config File

This feature/program has a dedicated config file in `config` directory with name `commission.php`. It has configuration for,
- csv file column sequence
- currency types
- operation types (`deposit`/`withraw`)
- user types
- commission percentage for different operation & user types

## Test 

run `php artisan test` 
