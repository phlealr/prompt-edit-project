<?php

namespace App\Enums;

/**
 * The providers the app can send generations to (ADR 0044). Adding a provider adds a case here.
 */
enum ImageProviderName: string
{
    case Pixelforge = 'pixelforge';
}
