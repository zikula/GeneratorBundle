<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Zikula\Bundle\GeneratorBundle\ZikulaGeneratorBundle(),
        );

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
    }

    public function boot()
    {
        parent::boot();

        $this->container->set('filesystem', new \Symfony\Component\Filesystem\Filesystem());
    }
}
