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

namespace Zikula\Bundle\GeneratorBundle\Tests\Generator;

use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Zikula\Bundle\GeneratorBundle\Generator\ControllerGenerator;

class ControllerGeneratorTest extends GeneratorTest
{
    public function testGenerateController(): void
    {
        $this->getGenerator()->generate($this->getBundle(), 'Welcome', 'annotation', 'twig');

        $files = [
            'Controller/WelcomeController.php',
            'Tests/Controller/WelcomeControllerTest.php',
        ];
        foreach ($files as $file) {
            $this->assertFileExists($this->tmpDir . '/' . $file, sprintf('%s has been generated', $file));
        }

        $content = file_get_contents($this->tmpDir.'/Controller/WelcomeController.php');
        $strings = [
            'namespace Foo\\BarBundle\\Controller',
            'class WelcomeController',
        ];
        foreach ($strings as $string) {
            $this->assertContains($string, $content);
        }

        /*$content = file_get_contents($this->tmpDir.'/Tests/Controller/WelcomeControllerTest.php');
        $strings = [
            'namespace Foo\\BarBundle\\Tests\\Controller',
            'class WelcomeControllerTest',
        ];*/
    }

    public function testGenerateActions(): void
    {
        $generator = $this->getGenerator();
        $actions = [
            0 => [
                'name' => 'showPageAction',
                'route' => '/{id}/{slug}',
                'placeholders' => ['id', 'slug'],
                'template' => 'default',
            ],
            1 => [
                'name' => 'getListOfPagesAction',
                'route' => '/_get-pages/{max_count}',
                'placeholders' => ['max_count'],
                'template' => 'FooBarBundle:Page:pages_list.html.twig',
            ]
        ];

        $generator->generate($this->getBundle(), 'Page', 'annotation', 'twig', $actions);

        $files = [
            'Resources/views/Page/showPage.html.twig',
            'Resources/views/Page/pages_list.html.twig',
        ];
        foreach ($files as $file) {
            $this->assertFileExists($this->tmpDir . '/' . $file, sprintf('%s has been generated', $file));
        }

        $content = file_get_contents($this->tmpDir.'/Controller/PageController.php');
        $strings = [
            'public function showPageAction($id, $slug)',
            'public function getListOfPagesAction($max_count)',
            '@Template("FooBarBundle:Page:pages_list.html.twig")'
        ];
        foreach ($strings as $string) {
            $this->assertContains($string, $content);
        }
    }

    public function testGenerateActionsWithNonDefaultFormats(): void
    {
        $generator = $this->getGenerator();

        $generator->generate($this->getBundle(), 'Page', 'yml', 'php', [
            1 => [
                'name' => 'showPageAction',
                'route' => '/{slug}',
                'placeholders' => ['slug'],
                'template' => 'FooBarBundle:Page:showPage.html.php'
            ],
        ]);

        $files = [
            'Resources/views/Page/showPage.html.php',
            'Resources/config/routing.yml'
        ];
        foreach ($files as $file) {
            $this->assertFileExists($this->tmpDir . '/' . $file, $file . ' has been generated');
        }

        $content = file_get_contents($this->tmpDir.'/Controller/PageController.php');
        $this->assertNotContains('@Route()', $content, 'Routing is done via a yml file');

        $content = file_get_contents($this->tmpDir.'/Resources/views/Page/showPage.html.php');
        $this->assertContains($this->getBundle()->getName().':Page:showPage', $content);

        $content = file_get_contents($this->tmpDir.'/Resources/config/routing.yml');
        $this->assertContains("show_page:\n    pattern: /{slug}\n    defaults: { _controller: FooBarBundle:Page:showPage }", $content);
    }

    protected function getGenerator(): ControllerGenerator
    {
        $generator = new ControllerGenerator($this->filesystem);
        $generator->setSkeletonDirs(__DIR__.'/../../Resources/skeleton');

        return $generator;
    }

    protected function getBundle(): BundleInterface
    {
        $bundle = $this->getMock(BundleInterface::class);
        $bundle->method('getPath')->willReturn($this->tmpDir);
        $bundle->method('getName')->willReturn('FooBarBundle');
        $bundle->method('getNamespace')->willReturn('Foo\BarBundle');

        return $bundle;
    }
}
