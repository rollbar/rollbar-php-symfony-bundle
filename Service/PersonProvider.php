<?php

namespace Rollbar\Symfony\RollbarBundle\Service;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PersonProvider implements PersonProviderInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private NormalizerInterface $normalizer,
    ) {}

    public function getUserInfo(): array
    {
        $token = $this->tokenStorage->getToken();

        if ($token) {
            $user = $token->getUser();

            return $this->normalizer->normalize($user, 'json');
        }

        return [];
    }
}
