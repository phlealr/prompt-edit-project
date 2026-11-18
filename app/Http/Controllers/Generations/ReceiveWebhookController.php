<?php

namespace App\Http\Controllers\Generations;

use App\Actions\Generations\RecordWebhookEvent;
use App\Http\Controllers\Controller;
use App\ImageProviders\Exceptions\InvalidWebhook;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReceiveWebhookController extends Controller
{
    /**
     * The provider's webhook: called by the provider, never by our pages. It answers 200 or 400 and
     * never redirects, since the provider does not follow redirects (ADR 0010).
     */
    public function __invoke(Request $request, RecordWebhookEvent $recordWebhookEvent): Response
    {
        try {
            $recordWebhookEvent->handle($request->getContent(), $request->headers);
        } catch (InvalidWebhook) {
            return response('', 400);
        }

        return response('', 200);
    }
}
