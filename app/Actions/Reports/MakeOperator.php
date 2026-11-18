<?php

namespace App\Actions\Reports;

use App\Models\User;

class MakeOperator
{
    /**
     * Lets the user see the operator report. Only the user:make-admin command calls this; no web
     * request can set it (ADR 0016).
     */
    public function handle(User $user): void
    {
        $user->forceFill(['is_admin' => true])->save();
    }
}
