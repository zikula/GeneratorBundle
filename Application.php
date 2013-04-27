<?php

namespace Zikula\Bundle\GeneratorBundle;

use Symfony\Bundle\FrameworkBundle\Console\Application as BaseApplication;
use Symfony\Component\HttpKernel\KernelInterface;

class Application extends BaseApplication
{
    public function __construct(KernelInterface $kernel)
    {
        parent::__construct($kernel);

        $this->setName('Zikula');
        $this->setVersion('0.0.1 - '.$kernel->getName().'/'.$kernel->getEnvironment().($kernel->isDebug() ? '/debug' : ''));
    }
}