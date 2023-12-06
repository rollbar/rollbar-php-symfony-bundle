<?php

namespace Rollbar\Symfony\RollbarBundle\Factories;

use Psr\Log\LogLevel;
use Monolog\Handler\RollbarHandler;
use Rollbar\Rollbar;
use Rollbar\Symfony\RollbarBundle\Service\PersonProviderInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

class RollbarHandlerFactory
{
    public function __construct(array $config, SerializerInterface $serializer, PersonProviderInterface $personProvider)
    {
        if (isset($_ENV['ROLLBAR_TEST_TOKEN']) && $_ENV['ROLLBAR_TEST_TOKEN']) {
            $config['access_token'] = $_ENV['ROLLBAR_TEST_TOKEN'];
        }

        if (!empty($config['person_fn']) && is_callable($config['person_fn'])) {
            $config['person'] = null;
        } elseif (empty($config['person'])) {
            $config['person_fn'] = static function () use ($personProvider, $serializer) {
                try {
                    $person = call_user_func([$personProvider, 'getUserInfo']);

                    return \json_decode($serializer->serialize($person, 'json'), true, 512, JSON_THROW_ON_ERROR);
                } catch (Throwable) {
                    // do nothing
                }

                return [];
            };
        }

        $config['framework'] = 'symfony ' . Kernel::VERSION;

        Rollbar::init($config, false, false, false);
    }

    public function createRollbarHandler(): RollbarHandler
    {
        return new RollbarHandler(Rollbar::logger(), LogLevel::ERROR);
    }
}
