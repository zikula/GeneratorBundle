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

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Sensio\Bundle\GeneratorBundle\Command\GenerateControllerCommand;

class GenerateControllerCommandTest extends GenerateCommandTest
{
    protected $generator;
    protected $bundle;
    protected $tmpDir;

    /**
     * @dataProvider getInteractiveCommandData
     */
    public function testInteractiveCommand($options, $input, $expected)
    {
        list($controller, $routeFormat, $templateFormat, $actions) = $expected;

        $generator = $this->getGenerator();
        $generator
            ->expects($this->once())
            ->method('generate')
            ->with($this->getBundle(), $controller, $routeFormat, $templateFormat, $actions)
        ;

        $tester = $this->getCommandTester($generator, $input);
        $tester->execute($options);
    }

    public function getInteractiveCommandData()
    {
        $tmp = sys_get_temp_dir();

        return [
            [[], "AcmeBlogBundle:Post\n", ['Post', 'annotation', 'twig', []]],
            [['--controller' => 'AcmeBlogBundle:Post'], '', ['Post', 'annotation', 'twig', []]],

            [[], "AcmeBlogBundle:Post\nyml\nphp\n", ['Post', 'yml', 'php', []]],

            [[], "AcmeBlogBundle:Post\nyml\nphp\nshowAction\n\n\ngetListAction\n/_getlist/{max}\nAcmeBlogBundle:Lists:post.html.php\n", ['Post', 'yml', 'php', [
                'showAction' => [
                    'name' => 'showAction',
                    'route' => '/show',
                    'placeholders' => [],
                    'template' => 'default',
                ],
                'getListAction' => [
                    'name' => 'getListAction',
                    'route' => '/_getlist/{max}',
                    'placeholders' => ['max'],
                    'template' => 'AcmeBlogBundle:Lists:post.html.php',
                ],
            ]]],

            [['--route-format' => 'xml', '--template-format' => 'php', '--actions' => 'showAction:/{slug}:AcmeBlogBundle:article.html.php'], 'AcmeBlogBundle:Post', ['Post', 'xml', 'php', [
                'showAction' => [
                    'name' => 'showAction',
                    'route' => '/{slug}',
                    'placeholders' => ['slug'],
                    'template' => 'AcmeBlogBundle:article.html.php',
                ],
            ]]],
        ];
    }

    /**
     * @dataProvider getNonInteractiveCommandData
     */
    public function testNonInteractiveCommand($options, $expected)
    {
        list($controller, $routeFormat, $templateFormat, $actions) = $expected;

        $generator = $this->getGenerator();
        $generator
            ->expects($this->once())
            ->method('generate')
            ->with($this->getBundle(), $controller, $routeFormat, $templateFormat, $actions)
        ;

        $tester = $this->getCommandTester($generator);
        $tester->execute($options, ['interactive' => false]);
    }

    public function getNonInteractiveCommandData()
    {
        $tmp = sys_get_temp_dir();

        return [
            [['--controller' => 'AcmeBlogBundle:Post'], ['Post', 'annotation', 'twig', []]],
            [['--controller' => 'AcmeBlogBundle:Post', '--route-format' => 'yml', '--template-format' => 'php'], ['Post', 'yml', 'php', []]],
            [['--controller' => 'AcmeBlogBundle:Post', '--actions' => 'showAction getListAction:/_getlist/{max}:AcmeBlogBundle:List:post.html.twig createAction:/admin/create'], ['Post', 'annotation', 'twig', [
                'showAction' => [
                    'name' => 'showAction',
                    'route' => '/show',
                    'placeholders' => [],
                    'template' => 'default',
                ],
                'getListAction' => [
                    'name' => 'getListAction',
                    'route' => '/_getlist/{max}',
                    'placeholders' => ['max'],
                    'template' => 'AcmeBlogBundle:List:post.html.twig',
                ],
                'createAction' => [
                    'name' => 'createAction',
                    'route' => '/admin/create',
                    'placeholders' => [],
                    'template' => 'default',
                ],
            ]]],
            [['--controller' => 'AcmeBlogBundle:Post', '--route-format' => 'xml', '--template-format' => 'php', '--actions' => 'showAction::'], ['Post', 'xml', 'php', [
                'showAction' => [
                    'name' => 'showAction',
                    'route' => '/show',
                    'placeholders' => [],
                    'template' => 'default',
                ],
            ]]],
        ];
    }

    protected function getCommand($generator, $input)
    {
        $command = $this
            ->getMockBuilder('Sensio\Bundle\GeneratorBundle\Command\GenerateControllerCommand')
            ->setMethods(['generateRouting'])
            ->getMock()
        ;

        $command->setContainer($this->getContainer());
        $command->setHelperSet($this->getHelperSet($input));
        $command->setGenerator($generator);

        return $command;
    }

    protected function getCommandTester($generator, $input = '')
    {
        return new CommandTester($this->getCommand($generator, $input));
    }

    protected function getApplication($input = '')
    {
        $application = new Application();

        $command = new GenerateControllerCommand();
        $command->setContainer($this->getContainer());
        $command->setHelperSet($this->getHelperSet($input));
        $command->setGenerator($this->getGenerator());

        $application->add($command);

        return $application;
    }

    protected function getGenerator()
    {
        if (null == $this->generator) {
            $this->setGenerator();
        }

        return $this->generator;
    }

    protected function setGenerator()
    {
        // get a noop generator
        $this->generator = $this
            ->getMockBuilder('Sensio\Bundle\GeneratorBundle\Generator\ControllerGenerator')
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock()
        ;
    }

    protected function getBundle()
    {
        if (null == $this->bundle) {
            $this->setBundle();
        }

        return $this->bundle;
    }

    protected function setBundle()
    {
        $bundle = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        $bundle->expects($this->any())->method('getPath')->will($this->returnValue($this->tmpDir));
        $bundle->expects($this->any())->method('getName')->will($this->returnValue('FooBarBundle'));
        $bundle->expects($this->any())->method('getNamespace')->will($this->returnValue('Foo\BarBundle'));

        $this->bundle = $bundle;
    }
}
