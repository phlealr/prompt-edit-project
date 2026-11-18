<?php

namespace App\ImageProviders;

use App\Enums\ImageProviderName;
use Illuminate\Contracts\Container\Container;

/**
 * Finds a provider by name (ADR 0044). Each provider is bound in the container as
 * "image-providers.<name>" (AppServiceProvider).
 */
final readonly class ImageProviders
{
    public function __construct(private Container $container) {}

    /**
     * The provider a generation was sent to.
     */
    public function for(ImageProviderName $name): ImageProvider
    {
        /** @var ImageProvider */
        return $this->container->make("image-providers.{$name->value}");
    }

    /**
     * The provider new generations go to, named in configuration.
     */
    public function default(): ImageProvider
    {
        return $this->for(ImageProviderName::from((string) config('services.image_provider')));
    }
}
