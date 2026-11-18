<?php

namespace Database\Seeders;

use App\Actions\Reports\MakeOperator;
use App\Models\User;
use Illuminate\Database\Seeder;

class OperatorSeeder extends Seeder
{
    /**
     * Creates the default operator if it does not exist yet, and makes sure it is an operator. An
     * existing account's password is left as it is.
     */
    public function run(MakeOperator $makeOperator): void
    {
        $operator = User::query()->firstOrCreate(
            ['email' => config('operator.email')],
            ['name' => config('operator.name'), 'password' => config('operator.password')],
        );

        $makeOperator->handle($operator);
    }
}
