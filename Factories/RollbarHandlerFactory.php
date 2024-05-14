<?php

namespace Rollbar\Symfony\RollbarBundle\Factories;

use Psr\Log\LogLevel;
use Monolog\Handler\RollbarHandler;
use Rollbar\Rollbar;
use Rollbar\Symfony\RollbarBundle\DependencyInjection\RollbarExtension;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Serializer\SerializerInterface;

class RollbarHandlerFactory
{
    public function __construct(ContainerInterface $container, SerializerInterface $serializer, Security|null $security=null)
    {
        $config = $container->getParameter(RollbarExtension::ALIAS . '.config');

        if (isset($_ENV['ROLLBAR_TEST_TOKEN']) && $_ENV['ROLLBAR_TEST_TOKEN']) {
            $config['access_token'] = $_ENV['ROLLBAR_TEST_TOKEN'];
        }

        if (!empty($config['person_fn']) && is_callable($config['person_fn'])) {
            $config['person'] = null;
        } elseif (empty($config['person'])) {
            $config['person_fn'] = static function () use ($security, $serializer) {
                try {
                    $token = $security->getToken();

                    if ($token) {
                        $user = $token->getUser();

                        return \json_decode($serializer->serialize($user, 'json'), true, 512, JSON_THROW_ON_ERROR);
                    }
                } catch (\Throwable $exception) {
                    // Ignore
                }
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
