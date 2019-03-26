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

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Zikula\Bundle\GeneratorBundle\Command\GenerateDoctrineCrudCommand;
use Zikula\Bundle\GeneratorBundle\Command\GeneratorCommand;
use Zikula\Bundle\GeneratorBundle\Generator\DoctrineCrudGenerator;
use Zikula\Bundle\GeneratorBundle\Generator\DoctrineFormGenerator;
use Symfony\Bridge\Doctrine\RegistryInterface;

class GenerateDoctrineCrudCommandTest extends GenerateCommandTest
{
    /**
     * @dataProvider getInteractiveCommandData
     */
    public function testInteractiveCommand($options, $input, $expected): void
    {
        list($entity, $format, $prefix, $withWrite) = $expected;

        $generator = $this->getGenerator();
        $generator
            ->expects($this->once())
            ->method('generate')
            ->with($this->getBundle(), $entity, $this->getDoctrineMetadata(), $format, $prefix, $withWrite)
        ;

        $tester = new CommandTester($this->getCommand($generator, $input));
        $tester->execute($options);
    }

    public function getInteractiveCommandData(): array
    {
        return [
            [[], "AcmeBlogBundle:Blog/Post\n", ['Blog\\Post', 'annotation', 'blog_post', false]],
            [['--entity' => 'AcmeBlogBundle:Blog/Post'], '', ['Blog\\Post', 'annotation', 'blog_post', false]],
            [[], "AcmeBlogBundle:Blog/Post\ny\nyml\nfoobar\n", ['Blog\\Post', 'yml', 'foobar', true]],
            [[], "AcmeBlogBundle:Blog/Post\ny\nyml\n/foobar\n", ['Blog\\Post', 'yml', 'foobar', true]],
            [['--entity' => 'AcmeBlogBundle:Blog/Post', '--format' => 'yml', '--route-prefix' => 'foo', '--with-write' => true], '', ['Blog\\Post', 'yml', 'foo', true]],
        ];
    }

    /**
     * @dataProvider getNonInteractiveCommandData
     */
    public function testNonInteractiveCommand($options, $expected): void
    {
        list($entity, $format, $prefix, $withWrite) = $expected;

        $generator = $this->getGenerator();
        $generator
            ->expects($this->once())
            ->method('generate')
            ->with($this->getBundle(), $entity, $this->getDoctrineMetadata(), $format, $prefix, $withWrite)
        ;

        $tester = new CommandTester($this->getCommand($generator, ''));
        $tester->execute($options, ['interactive' => false]);
    }

    public function getNonInteractiveCommandData(): array
    {
        return [
            [['--entity' => 'AcmeBlogBundle:Blog/Post'], ['Blog\\Post', 'annotation', 'blog_post', false]],
            [['--entity' => 'AcmeBlogBundle:Blog/Post', '--format' => 'yml', '--route-prefix' => 'foo', '--with-write' => true], ['Blog\\Post', 'yml', 'foo', true]],
        ];
    }

    protected function getCommand($generator, $input): GeneratorCommand
    {
        $command = $this
            ->getMockBuilder(GenerateDoctrineCrudCommand::class)
            ->setMethods(['getEntityMetadata'])
            ->getMock()
        ;

        $command
            ->method('getEntityMetadata')
            ->willReturn([$this->getDoctrineMetadata()])
        ;

        $command->setContainer($this->getContainer());
        $command->setHelperSet($this->getHelperSet($input));
        $command->setGenerator($generator);
        $command->setFormGenerator($this->getFormGenerator());

        return $command;
    }

    protected function getDoctrineMetadata(): ClassMetadataInfo
    {
        return $this
            ->getMockBuilder(ClassMetadataInfo::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    protected function getGenerator(): Generator
    {
        // get a noop generator
        return $this
            ->getMockBuilder(DoctrineCrudGenerator::class)
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock()
        ;
    }

    protected function getFormGenerator(): Generator
    {
        return $this
            ->getMockBuilder(DoctrineFormGenerator::class)
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock()
        ;
    }

    protected function getContainer(): ContainerInterface
    {
        $container = parent::getContainer();

        $registry = $this->getMock(RegistryInterface::class);
        $registry
            ->method('getEntityNamespace')
            ->willReturn('Foo\\FooBundle\\Entity')
        ;

        $container->set('doctrine', $registry);

        return $container;
    }
}
