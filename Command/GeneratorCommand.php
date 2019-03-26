<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\Bundle\GeneratorBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Zikula\Bundle\GeneratorBundle\Command\Helper\DialogHelper;
use Zikula\Bundle\GeneratorBundle\Generator\Generator;

/**
 * Base class for generator commands.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
abstract class GeneratorCommand extends ContainerAwareCommand
{
    /**
     * @var Generator
     */
    private $generator;

    // only useful for unit tests
    public function setGenerator(Generator $generator): void
    {
        $this->generator = $generator;
    }

    abstract protected function createGenerator(): Generator;

    protected function getGenerator(string $bundle = null): Generator
    {
        if (null === $this->generator) {
            $this->generator = $this->createGenerator();
            $this->generator->setSkeletonDirs($this->getSkeletonDirs($bundle));
        }

        return $this->generator;
    }

    protected function getSkeletonDirs(string $bundle = null): array
    {
        $skeletonDirs = [];

        if (isset($bundle) && is_dir($dir = $bundle->getPath().'/Resources/ZikulaGeneratorBundle/skeleton')) {
            $skeletonDirs[] = $dir;
        }

        if (is_dir($dir = $this->getContainer()->get('kernel')->getRootdir().'/Resources/ZikulaGeneratorBundle/skeleton')) {
            $skeletonDirs[] = $dir;
        }

        $skeletonDirs[] = __DIR__.'/../Resources/skeleton';
        $skeletonDirs[] = __DIR__.'/../Resources';

        return $skeletonDirs;
    }

    protected function getDialogHelper(): DialogHelper
    {
        if ($this->getHelperSet()->has('dialog')) {
            return $this->getHelperSet()->get('dialog');
        }
        $this->getHelperSet()->set($dialog = new DialogHelper(), 'dialog');

        return $dialog;
    }
}
