<?php

namespace Tests\Fixtures\App;

use Exception;
use Rollbar\Symfony\RollbarBundle\RollbarBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new SecurityBundle(),
            new MonologBundle(),
            new RollbarBundle(),
        ];
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    /**
     * @throws Exception
     */
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load($this->getProjectDir().'/config/config_'.$this->getEnvironment().'.yml');
    }

    public function getCacheDir(): string
    {
        return realpath(__DIR__ . '/../../../') . '/var/' . $this->environment . '/cache';
    }

    public function getLogDir(): string
    {
        return realpath(__DIR__ . '/../../../') . '/var/' . $this->environment . '/logs';
    }
}
