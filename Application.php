<?php

namespace Zikula\Bundle\GeneratorBundle;

use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication
{
    public function __construct()
    {
        error_reporting(-1);

        parent::__construct('Zikula Generator', '0.0.1');

        $this->add(new Command\GenerateControllerCommand());
        $this->add(new Command\GenerateDoctrineCrudCommand());
        $this->add(new Command\GenerateDoctrineEntityCommand());
        $this->add(new Command\GenerateDoctrineFormCommand());
        $this->add(new Command\GenerateModuleCommand());
    }

    public function getLongVersion()
    {
        return parent::getLongVersion().' by <comment>Drak</comment>';
    }
}