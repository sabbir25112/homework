<?php

namespace Tests\Feature;

use App\BulkCommissionCalculator\DepositCommissionCalculator;
use App\BulkCommissionCalculator\WithdrawCommissionCalculator;
use App\Console\Commands\CalculateCommissionCommand;
use App\CurrencyConverter\ConversionRateDetector;
use App\CurrencyConverter\CurrencyConverter;
use App\InputSanitizer\CommissionCsvInputSanitizer;
use Mockery\MockInterface;
use Tests\TestCase;

class CommissionTest extends TestCase
{
    public function testCheckTaskDescriptionInputOutput()
    {
        $conversionRateDetector = $this->createMock(ConversionRateDetector::class);

        $conversionRateDetector->method("getConversionRate")
            ->will(
                $this->returnValueMap([
                    ["EUR", "JPY", "2016-01-06", 129.53],
                    ["EUR", "JPY", "2016-02-19", 129.53],
                    ["JPY", "EUR", "2016-01-06", 1/129.53],
                    ["JPY", "EUR", "2016-02-19", 1/129.53],
                    ["EUR", "USD", "2016-01-07", 1.1497],
                    ["USD", "EUR", "2016-01-07", 1/1.1497],
                ])
            )
        ;

        $currencyConverter = new CurrencyConverter($conversionRateDetector);
        $withdrawCommissionCalculator = new WithdrawCommissionCalculator($currencyConverter);
        $file_path = public_path('input_on_homework.csv');
        $expected = [
            "0.60",
            "3.00",
            "0.00",
            "0.06",
            "1.50",
            "0",
            "0.70",
            "0.30",
            "0.30",
            "3.00",
            "0.00",
            "0.00",
            "8612",
        ];

        $this->expectOutputString(implode(PHP_EOL, $expected) . PHP_EOL);

        (new CalculateCommissionCommand(new DepositCommissionCalculator, $withdrawCommissionCalculator, new CommissionCsvInputSanitizer))
            ->setFilePath($file_path)
            ->handle();
    }

    public function testCheckHappyFlow()
    {
        $expect = [
            "0.60",
            "3.00",
            "0.00",
            "0.06",
            "1.50",
            "0",
            "0.71"
        ];

        $this->expectOutputString(implode(PHP_EOL, $expect) . PHP_EOL);
        $this->artisan('calculate:commission '. public_path('input.csv'));
    }

    public function testCheckCsvParserWithoutFilePath()
    {
        $csvParser = new CommissionCsvInputSanitizer();
        $this->expectException("Exception");
        $this->expectExceptionMessage("Oops!! this is not a file");
        $csvParser->parse();
    }

    public function testInvalidFilePathAsCommandArgument()
    {
        $this->expectException("Exception");
        $this->expectExceptionMessage("Oops!! this is not a file");

        $this->artisan('calculate:commission ' . public_path('input'));
    }

    public function testCheckNotEnoughArgumentsExceptionOnCommand()
    {
        $this->expectException("Exception");
        $this->expectExceptionMessage("Not enough arguments");

        $this->artisan('calculate:commission');
    }

    public function testCheckDoubleCalculationForWeeklyPrivateWithdraw()
    {
        $expect = [
            "0.00",
            "0.90",
            "1.50",
        ];
        $this->expectOutputString(implode(PHP_EOL, $expect) . PHP_EOL);
        $this->artisan('calculate:commission '. public_path('input2.csv'));
    }

    public function testCheckUnprocessableEntity()
    {
        $expect = [
            "0.00",
            "Unprocessable Entity",
            "Unprocessable Entity",
            "0.60",
        ];
        $this->expectOutputString(implode(PHP_EOL, $expect) . PHP_EOL);
        $this->artisan('calculate:commission '. public_path('input3.csv'));
    }

    public function privateDepositProvider(): array
    {

        return [
            [
                [
                    'private' => [
                        4 => [
                            [
                                'date'           => "2021-01-01",
                                'user_id'        => 4,
                                'user_type'      => "private",
                                'operation_type' => "deposit",
                                'amount'         => 100,
                                'currency'       => 'EUR',
                                'input_trace'    => 5,
                            ],
                        ],
                    ],
                ],
                [
                    5 => 0.03,
                ],
            ],
            [
                [
                    'private' => [
                        4 => [
                            [
                                'date'           => "2021-01-01",
                                'user_id'        => 4,
                                'user_type'      => "private",
                                'operation_type' => "deposit",
                                'amount'         => 100,
                                'currency'       => 'EUR',
                                'input_trace'    => 6,
                            ],
                            [
                                'date'           => "2021-01-01",
                                'user_id'        => 4,
                                'user_type'      => "private",
                                'operation_type' => "deposit",
                                'amount'         => 200,
                                'currency'       => 'EUR',
                                'input_trace'    => 7,
                            ],
                        ],
                    ],
                ],
                [
                    6 => 0.03,
                    7 => 0.06,
                ],
            ],
        ];
    }

    /**
     * @dataProvider privateDepositProvider
     * @param $transactions
     * @param $except
     */

    public function testPrivateDepositCalculator($transactions, $except)
    {
        $depositCalculator = new DepositCommissionCalculator();
        $output = $depositCalculator
            ->setInput($transactions)
            ->calculate()
            ->getOutput()
        ;
        $this->assertSame($output, $except);
    }

    public function businessDepositProvider(): array
    {
        return [
            [
                [
                    'business' => [
                        8 => [
                            [
                                'date'           => "2021-01-01",
                                'user_id'        => 8,
                                'user_type'      => "business",
                                'operation_type' => "deposit",
                                'amount'         => 500,
                                'currency'       => 'EUR',
                                'input_trace'    => 2,
                            ],
                            [
                                'date'           => "2021-01-01",
                                'user_id'        => 8,
                                'user_type'      => "business",
                                'operation_type' => "deposit",
                                'amount'         => 800,
                                'currency'       => 'EUR',
                                'input_trace'    => 3,
                            ],
                        ],
                    ],
                ],
                [
                    2 => 0.15,
                    3 => 0.24,
                ],
            ],
            [
                [
                    'business' => [
                        4 => [
                            [
                                'date'           => "2021-01-01",
                                'user_id'        => 4,
                                'user_type'      => "business",
                                'operation_type' => "deposit",
                                'amount'         => 100,
                                'currency'       => 'EUR',
                                'input_trace'    => 6,
                            ]
                        ],
                    ],
                ],
                [
                    6 => 0.03,
                ],
            ],
        ];
    }

    /**
     * @dataProvider businessDepositProvider
     * @param $transactions
     * @param $except
     */

    public function testBusinessDepositCalculator($transactions, $except)
    {
        $depositCalculator = new DepositCommissionCalculator();
        $output = $depositCalculator
            ->setInput($transactions)
            ->calculate()
            ->getOutput()
        ;
        $this->assertSame($output, $except);
    }

    public function testCurrencyConverterWithZeroInput()
    {
        $conversionRateDetector = $this->partialMock(ConversionRateDetector::class, function (MockInterface $mock) {
            $mock->shouldReceive('getConversionRate')->andReturn(0);
        });
        $currency_converter = new CurrencyConverter($conversionRateDetector);
        $output = $currency_converter->convert(0, 'EUR', 'USD');
        $this->assertEquals(0.0, $output['to_amount']);
        $this->assertEquals($output['from_amount'], $output['to_amount']);
    }

    public function testCheckJapaneseYenResponse()
    {
        $expect = [
            "0",
            "5",
            "1",
            "1",
        ];
        $this->expectOutputString(implode(PHP_EOL, $expect) . PHP_EOL);
        $this->artisan('calculate:commission '. public_path('japanese_yen_input.csv'));
    }

    public function testExceptionOnCurrencyConverterForFromCurrency()
    {
        $this->expectException("Exception");
        $this->expectExceptionMessage("No Base Currency Found To Convert");

        $conversionRateDetector = $this->createMock(ConversionRateDetector::class);
        $currencyConverter = new CurrencyConverter($conversionRateDetector);
        $currencyConverter->convert(100);
    }

    public function testExceptionOnCurrencyConverterForToCurrency()
    {
        $this->expectException("Exception");
        $this->expectExceptionMessage("No Destination Currency Found To Convert");
        $conversionRateDetector = $this->createMock(ConversionRateDetector::class);
        $currencyConverter = new CurrencyConverter($conversionRateDetector);
        $currencyConverter->convert(100, "USD");
    }

    public function testExceptionOnCurrencyConverterForAmount()
    {
        $this->expectException("Exception");
        $this->expectExceptionMessage("Amount Not Found To Convert");
        $conversionRateDetector = $this->createMock(ConversionRateDetector::class);
        $currencyConverter = new CurrencyConverter($conversionRateDetector);
        $currencyConverter->convert();
    }

    public function testExceptionOnExchangeRateDetectorForFromCurrency()
    {
        $this->expectException("Exception");
        $this->expectExceptionMessage("No Base Currency Found");

        $conversionRateDetector = new ConversionRateDetector();
        $conversionRateDetector->getConversionRate();
    }

    public function testExceptionOnExchangeRateDetectorForToCurrency()
    {
        $this->expectException("Exception");
        $this->expectExceptionMessage("No Destination Currency Found");

        $conversionRateDetector = new ConversionRateDetector();
        $conversionRateDetector->getConversionRate("USD");
    }

    public function businessDepositProviderWithoutInputTrace(): array
    {
        return [
            [
                [
                    'business' => [
                        8 => [
                            [
                                'date'           => "2021-01-01",
                                'user_id'        => 8,
                                'user_type'      => "business",
                                'operation_type' => "deposit",
                                'amount'         => 500,
                                'currency'       => 'EUR'
                            ],
                            [
                                'date'           => "2021-01-01",
                                'user_id'        => 8,
                                'user_type'      => "business",
                                'operation_type' => "deposit",
                                'amount'         => 800,
                                'currency'       => 'EUR',
                            ],
                        ],
                    ],
                ],
                [
                    0 => 0.15,
                    1 => 0.24,
                ],
            ],
            [
                [
                    'business' => [
                        4 => [
                            [
                                'date'           => "2021-01-01",
                                'user_id'        => 4,
                                'user_type'      => "business",
                                'operation_type' => "deposit",
                                'amount'         => 100,
                                'currency'       => 'EUR',
                            ]
                        ],
                    ],
                ],
                [
                    0 => 0.03,
                ],
            ],
        ];
    }

    /**
     * @dataProvider businessDepositProviderWithoutInputTrace
     * @param $transactions
     * @param $except
     */
    public function testDepositCommissionCalculatorReturnOutputWithoutInputTrace($transactions, $except)
    {
        $depositCalculator = new DepositCommissionCalculator();
        $output = $depositCalculator
            ->setInput($transactions)
            ->calculate()
            ->getOutput()
        ;
        $this->assertSame($output, $except);
    }

    public function withdrawProviderWithoutInputTrace(): array
    {

        return [
            [
                [
                    'private' => [
                        4 => [
                            [
                                'date'           => "2021-01-01",
                                'user_id'        => 4,
                                'user_type'      => "private",
                                'operation_type' => "withdraw",
                                'amount'         => 100,
                                'currency'       => 'EUR',
                            ],
                        ],
                    ],
                ],
                [
                    0 => 0.00,
                ],
            ],
            [
                [
                    'business' => [
                        4 => [
                            [
                                'date'           => "2021-01-01",
                                'user_id'        => 4,
                                'user_type'      => "business",
                                'operation_type' => "withdraw",
                                'amount'         => 100,
                                'currency'       => 'EUR',
                            ],
                            [
                                'date'           => "2021-01-01",
                                'user_id'        => 4,
                                'user_type'      => "business",
                                'operation_type' => "withdraw",
                                'amount'         => 200,
                                'currency'       => 'EUR',
                                'input_trace'    => 7,
                            ],
                        ],
                    ],
                ],
                [
                    0 => 0.50,
                    7 => 1.00,
                ],
            ],
        ];
    }

    /**
     * @dataProvider withdrawProviderWithoutInputTrace
     * @param $transactions
     * @param $except
     */

    public function testWithdrawCommissionCalculatorReturnOutputWithoutInputTrace($transactions, $except)
    {
        $depositCalculator = new WithdrawCommissionCalculator(new CurrencyConverter(new ConversionRateDetector()));
        $output = $depositCalculator
            ->setInput($transactions)
            ->calculate()
            ->getOutput()
        ;
        $this->assertSame($output, $except);
    }
}
