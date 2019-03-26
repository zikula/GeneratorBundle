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

use Symfony\Component\Console\Tester\CommandTester;
use Zikula\Bundle\GeneratorBundle\Command\GenerateBundleCommand;
use Zikula\Bundle\GeneratorBundle\Command\GeneratorCommand;
use Zikula\Bundle\GeneratorBundle\Generator\BundleGenerator;

class GenerateBundleCommandTest extends GenerateCommandTest
{
    /**
     * @dataProvider getInteractiveCommandData
     */
    public function testInteractiveCommand($options, $input, $expected): void
    {
        list($namespace, $bundle, $dir, $format, $structure) = $expected;

        $generator = $this->getGenerator();
        $generator
            ->expects($this->once())
            ->method('generate')
            ->with($namespace, $bundle, $dir, $format, $structure)
        ;

        $tester = new CommandTester($this->getCommand($generator, $input));
        $tester->execute($options);
    }

    public function getInteractiveCommandData(): array
    {
        $tmp = sys_get_temp_dir();

        return [
            [['--dir' => $tmp, '--format' => 'annotation'], "Foo/BarBundle\n", ['Foo\BarBundle', 'FooBarBundle', $tmp.'/', 'annotation', false]],
            [[], "Foo/BarBundle\nBarBundle\nfoo\nyml\nn", ['Foo\BarBundle', 'BarBundle', 'foo/', 'yml', false]],
            [['--dir' => $tmp, '--format' => 'yml', '--bundle-name' => 'BarBundle', '--structure' => true], "Foo/BarBundle\n", ['Foo\BarBundle', 'BarBundle', $tmp.'/', 'yml', true]],
        ];
    }

    /**
     * @dataProvider getNonInteractiveCommandData
     */
    public function testNonInteractiveCommand($options, $expected): void
    {
        list($namespace, $bundle, $dir, $format, $structure) = $expected;

        $generator = $this->getGenerator();
        $generator
            ->expects($this->once())
            ->method('generate')
            ->with($namespace, $bundle, $dir, $format, $structure)
        ;

        $tester = new CommandTester($this->getCommand($generator, ''));
        $tester->execute($options, ['interactive' => false]);
    }

    public function getNonInteractiveCommandData(): array
    {
        $tmp = sys_get_temp_dir();

        return [
            [['--dir' => $tmp, '--namespace' => 'Foo/BarBundle'], ['Foo\BarBundle', 'FooBarBundle', $tmp.'/', 'annotation', false]],
            [['--dir' => $tmp, '--namespace' => 'Foo/BarBundle', '--format' => 'yml', '--bundle-name' => 'BarBundle', '--structure' => true], ['Foo\BarBundle', 'BarBundle', $tmp.'/', 'yml', true]],
        ];
    }

    protected function getCommand($generator, $input): GeneratorCommand
    {
        $command = $this
            ->getMockBuilder(GenerateBundleCommand::class)
            ->setMethods(['checkAutoloader', 'updateKernel', 'updateRouting'])
            ->getMock()
        ;

        $command->setContainer($this->getContainer());
        $command->setHelperSet($this->getHelperSet($input));
        $command->setGenerator($generator);

        return $command;
    }

    protected function getGenerator(): BundleGenerator
    {
        // get a noop generator
        return $this
            ->getMockBuilder(BundleGenerator::class)
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock()
        ;
    }
}
