<?php

namespace Rollbar\Symfony\RollbarBundle\Factories;

use Psr\Log\LogLevel;
use Monolog\Handler\Handler;
use Monolog\Handler\RollbarHandler;
use Rollbar\Monolog\Handler\RollbarHandler as LegacyRollbarHandler;
use Rollbar\Rollbar;
use Rollbar\Symfony\RollbarBundle\DependencyInjection\RollbarExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RollbarHandlerFactory
 *
 * @package Rollbar\Symfony\RollbarBundle\Factories
 */
class RollbarHandlerFactory
{
    /**
     * RollbarHandlerFactory constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $config = $container->getParameter(RollbarExtension::ALIAS . '.config');

        if (isset($_ENV['ROLLBAR_TEST_TOKEN']) && $_ENV['ROLLBAR_TEST_TOKEN']) {
            $config['access_token'] = $_ENV['ROLLBAR_TEST_TOKEN'];
        }

        if (!empty($config['person_fn']) && is_callable($config['person_fn'])) {
            $config['person'] = null;
        } elseif (empty($config['person'])) {
            $config['person_fn'] = static function () use ($container) {
                try {
                    $token = $container->get('security.token_storage')->getToken();

                    if ($token) {
                        $user = $token->getUser();
                        $serializer = $container->get('serializer');
                        return \json_decode($serializer->serialize($user, 'json'), true);
                    }
                } catch (\Exception $exception) {
                    // Ignore
                }
            };
        }

        Rollbar::init($config, false, false, false);
    }

    /**
     * Create RollbarHandler
     *
     * @return Handler
     */
    public function createRollbarHandler(): Handler
    {
        if (! class_exists(RollbarHandler::class)) {
            return new LegacyRollbarHandler(Rollbar::logger(), LogLevel::ERROR);
        }

        return new RollbarHandler(Rollbar::logger(), LogLevel::ERROR);
    }
}
