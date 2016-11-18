<?php

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
use Sensio\Bundle\GeneratorBundle\Command\GenerateDoctrineEntityCommand;

class GenerateDoctrineEntityCommandTest extends GenerateCommandTest
{
    /**
     * @dataProvider getInteractiveCommandData
     */
    public function testInteractiveCommand($options, $input, $expected)
    {
        list($entity, $format, $fields) = $expected;

        $generator = $this->getGenerator();
        $generator
            ->expects($this->once())
            ->method('generate')
            ->with($this->getBundle(), $entity, $format, $fields)
        ;

        $tester = new CommandTester($this->getCommand($generator, $input));
        $tester->execute($options);
    }

    public function getInteractiveCommandData()
    {
        return [
            [[], "AcmeBlogBundle:Blog/Post\n", ['Blog\\Post', 'annotation', []]],
            [['--entity' => 'AcmeBlogBundle:Blog/Post'], '', ['Blog\\Post', 'annotation', []]],
            [[], "AcmeBlogBundle:Blog/Post\nyml\n\n", ['Blog\\Post', 'yml', []]],
            [[], "AcmeBlogBundle:Blog/Post\nyml\ncreated_by\n\n255\ndescription\ntext\n\n", ['Blog\\Post', 'yml', [
                ['fieldName' => 'createdBy', 'type' => 'string', 'length' => 255, 'columnName' => 'created_by'],
                ['fieldName' => 'description', 'type' => 'text', 'columnName' => 'description'],
            ]]],
        ];
    }

    /**
     * @dataProvider getNonInteractiveCommandData
     */
    public function testNonInteractiveCommand($options, $expected)
    {
        list($entity, $format, $fields) = $expected;

        $generator = $this->getGenerator();
        $generator
            ->expects($this->once())
            ->method('generate')
            ->with($this->getBundle(), $entity, $format, $fields)
        ;
        $generator
            ->expects($this->any())
            ->method('isReservedKeyword')
            ->will($this->returnValue(false))
        ;

        $tester = new CommandTester($this->getCommand($generator, ''));
        $tester->execute($options, ['interactive' => false]);
    }

    public function getNonInteractiveCommandData()
    {
        return [
            [['--entity' => 'AcmeBlogBundle:Blog/Post'], ['Blog\\Post', 'annotation', []]],
            [['--entity' => 'AcmeBlogBundle:Blog/Post', '--format' => 'yml', '--fields' => 'created_by:string(255) description:text'], ['Blog\\Post', 'yml', [
                ['fieldName' => 'created_by', 'type' => 'string', 'length' => 255],
                ['fieldName' => 'description', 'type' => 'text', 'length' => ''],
            ]]],
        ];
    }

    protected function getCommand($generator, $input)
    {
        $command = new GenerateDoctrineEntityCommand();
        $command->setContainer($this->getContainer());
        $command->setHelperSet($this->getHelperSet($input));
        $command->setGenerator($generator);

        return $command;
    }

    protected function getGenerator()
    {
        // get a noop generator
        return $this
            ->getMockBuilder('Sensio\Bundle\GeneratorBundle\Generator\DoctrineEntityGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate', 'isReservedKeyword'])
            ->getMock()
        ;
    }
}
