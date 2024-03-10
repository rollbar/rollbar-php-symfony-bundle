<?php

namespace Rollbar\Symfony\RollbarBundle\Service;

interface PersonProviderInterface
{
    public function getUserInfo(): array|string|int|float|bool|\ArrayObject|null;
}
