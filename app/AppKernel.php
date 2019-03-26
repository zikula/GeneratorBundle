<?php

declare(strict_types=1);

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;
use Zikula\Bundle\GeneratorBundle\ZikulaGeneratorBundle;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        return [
            new ZikulaGeneratorBundle()
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
    }

    public function boot()
    {
        parent::boot();

        $this->container->set('filesystem', new Filesystem());
    }
}
