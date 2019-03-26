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

namespace Sensio\Bundle\GeneratorBundle\Tests\Command;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Zikula\Bundle\GeneratorBundle\Command\Helper\DialogHelper;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class GenerateCommandTest extends TestCase
{
    protected function getHelperSet($input): HelperSet
    {
        $dialog = new DialogHelper();
        $dialog->setInputStream($this->getInputStream($input));

        return new HelperSet([new FormatterHelper(), $dialog]);
    }

    protected function getBundle(): BundleInterface
    {
        $bundle = $this->getMock(BundleInterface::class);
        $bundle
            ->method('getPath')
            ->willReturn(sys_get_temp_dir())
        ;

        return $bundle;
    }

    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'rb+', false);
        fwrite($stream, $input.str_repeat("\n", 10));
        rewind($stream);

        return $stream;
    }

    protected function getContainer(): ContainerInterface
    {
        $kernel = $this->getMock(KernelInterface::class);
        $kernel
            ->method('getBundle')
            ->willReturn($this->getBundle())
        ;
        $kernel
            ->method('getBundles')
            ->willReturn([$this->getBundle()])
        ;

        $filesystem = $this->getMock(Filesystem::class);
        $filesystem
            ->method('isAbsolutePath')
            ->willReturn(true)
        ;

        $container = new Container();
        $container->set('kernel', $kernel);
        $container->set('filesystem', $filesystem);

        $container->setParameter('kernel.root_dir', sys_get_temp_dir());

        return $container;
    }
}
