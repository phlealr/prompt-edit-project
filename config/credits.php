<?php

/*
| The markup users pay on top of the provider's cost, as a whole percentage (ADR 0005, ADR 0045).
| Each generation keeps the markup of its hold, so a change affects new generations only. A value
| that is not a whole number from 0 to 1000 stops the app from booting, rather than quietly charging
| a wrong markup.
*/

$markupPercent = (string) env('MARKUP_PERCENT', '25');

if (! ctype_digit($markupPercent) || (int) $markupPercent > 1000) {
    throw new InvalidArgumentException("MARKUP_PERCENT must be a whole number from 0 to 1000, not [{$markupPercent}].");
}

return [

    'markup_percent' => (int) $markupPercent,

];
