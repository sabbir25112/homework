<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400"></a></p>

# Solution

The solution is a console command `php arisan calculate:commission {csv_full_path}`

## Setup Project
- `git clone {this-repo}`
- `cd homework`  
- `composer install`
- **Copy `.env.example` to `.env`**
- **Add `EXCHANGE_RATE_API_ACCESS_KEY={exchange-rate-api-key}` in `.env`**

## Run Command

`php arisan calculate:commission {csv_full_path}`

Please provide full file path. Otherwise you'll have `Oops!! this is not a file` error.

## HOW IT WORKS

Basically there is four main component of this program.
- `CommissionCsvInputSanitizer` - For sanitizing incomplete / malformed data 
- `DepositCommissionCalculator` - To calculate commission for `deposit` operation
- `WithdrawCommissionCalculator` - To calculate commission for `withdraw` operation
- `CurrencyConverter` - It just do what it name for :P


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
