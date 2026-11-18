<?php

namespace App\Console\Commands;

use App\Actions\Reports\MakeOperator;
use App\Models\User;
use Illuminate\Console\Command;

class MakeAdminCommand extends Command
{
    protected $signature = 'user:make-admin {email : The email of the user to make an operator}';

    protected $description = 'Make a user an operator, who can see the operator report';

    public function handle(MakeOperator $makeOperator): int
    {
        $email = (string) $this->argument('email');
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error("No user has the email {$email}.");

            return self::FAILURE;
        }

        $makeOperator->handle($user);
        $this->info("{$email} is now an operator.");

        return self::SUCCESS;
    }
}
