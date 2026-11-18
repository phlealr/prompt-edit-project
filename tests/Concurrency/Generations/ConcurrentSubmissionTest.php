<?php

use App\Actions\Generations\SubmitGeneration;
use App\Enums\CreditTransactionType;
use App\Enums\Quality;
use App\Enums\Size;
use App\Exceptions\NotEnoughCredits;
use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

it('lets only one of two concurrent submissions hold a balance that covers one', function () {
    fakeImageProvider(); // estimate maximum: $0.02
    Queue::fake();
    $user = User::factory()->create();
    CreditTransaction::factory()->for($user)->purchase(30_000_000)->create(); // $0.03: enough for one

    // Slow down every balance read, so that without the row lock both submissions would read
    // $0.03 before either of them writes its hold.
    DB::listen(function (QueryExecuted $query) {
        if (str_contains($query->sql, 'sum(')) {
            usleep(500_000);
        }
    });

    // Each child process opens its own database connection.
    DB::purge();
    $children = [];

    foreach ([1, 2] as $submission) {
        $pid = pcntl_fork();

        if ($pid === 0) {
            try {
                app(SubmitGeneration::class)->handle($user, 'A cat', Size::Square, Quality::Low, []);
            } catch (NotEnoughCredits) {
                // The second submission is refused: the expected outcome for one of the two.
            }

            // Stop here, without running the test runner's shutdown in the child.
            posix_kill(getmypid(), SIGKILL);
        }

        $children[] = $pid;
    }

    foreach ($children as $pid) {
        pcntl_waitpid($pid, $status);
    }

    expect(CreditTransaction::query()->where('type', CreditTransactionType::Hold)->count())->toBe(1);
});
