<?php

namespace Tests\Feature;

use App\BulkCommissionCalculator\CurrencyConverter;
use App\BulkCommissionCalculator\DepositCommissionCalculator;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CommissionTest extends TestCase
{
    public function testFilePathException()
    {
        try {
            Artisan::call('calculate:commission');
            $this->assertTrue(false);
        } catch (\Exception $exception) {
            $this->assertStringContainsString("Not enough arguments", $exception->getMessage());
        }
    }

    public function testHappyFlow()
    {
        $this->artisan('calculate:commission '. public_path('input.csv'))
            ->expectsOutput(0.60)
            ->expectsOutput(3.00)
            ->expectsOutput(0.00)
            ->expectsOutput(0.06)
            ->expectsOutput(1.50)
            ->expectsOutput(0.00)
            ->expectsOutput(0.70)
        ;
    }

    public function testCheckDoubleCalculationForWeeklyPrivateWithdraw()
    {
        $this->artisan('calculate:commission '. public_path('input2.csv'))
            ->expectsOutput(0.00)
            ->expectsOutput(0.90)
            ->expectsOutput(1.50)
        ;
    }

    public function testCheckUnprocessableEntity()
    {
        $this->artisan('calculate:commission '. public_path('input3.csv'))
            ->expectsOutput(0.00)
            ->expectsOutput("Unprocessable Entity")
            ->expectsOutput("Unprocessable Entity")
            ->expectsOutput(0.60)
        ;
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
        $currency_converter = new CurrencyConverter();
        $output = $currency_converter->convert(0, 'EUR', 'USD');
        $this->assertEquals(0.0, $output['to_amount']);
        $this->assertEquals($output['from_amount'], $output['to_amount']);
    }
}
