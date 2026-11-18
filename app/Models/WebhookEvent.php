<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One notification the provider posted to us, stored once by its event id (ADR 0010).
 *
 * @property int $id
 * @property string $provider_event_id
 * @property string $payload
 * @property Carbon $created_at
 */
#[Fillable(['provider_event_id', 'payload'])]
class WebhookEvent extends Model
{
    public const null UPDATED_AT = null;
}
