<?php

arch('money uses no float or rounding functions')
    ->expect('App\ValueObjects\Money')
    ->not->toUse(['floatval', 'round', 'floor', 'ceil', 'number_format', 'sprintf']);
