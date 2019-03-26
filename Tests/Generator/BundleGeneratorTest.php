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

use Zikula\Bundle\GeneratorBundle\Generator\BundleGenerator;

class BundleGeneratorTest extends GeneratorTest
{
    public function testGenerateYaml(): void
    {
        $this->getGenerator()->generate('Foo\BarBundle', 'FooBarBundle', $this->tmpDir, 'yml');

        $files = [
            'FooBarBundle.php',
            'Controller/DefaultController.php',
            'Resources/views/Default/index.html.twig',
            'Resources/config/routing.yml',
            'Tests/Controller/DefaultControllerTest.php',
            'Resources/config/services.yml',
            'DependencyInjection/Configuration.php',
            'DependencyInjection/FooBarExtension.php',
        ];
        foreach ($files as $file) {
            $this->assertFileExists($this->tmpDir . '/Foo/BarBundle/' . $file, sprintf('%s has been generated', $file));
        }

        $content = file_get_contents($this->tmpDir.'/Foo/BarBundle/FooBarBundle.php');
        $this->assertContains('namespace Foo\\BarBundle', $content);

        $content = file_get_contents($this->tmpDir.'/Foo/BarBundle/Controller/DefaultController.php');
        $this->assertContains('public function indexAction', $content);
        $this->assertNotContains('@Route("/hello/{name}"', $content);

        $content = file_get_contents($this->tmpDir.'/Foo/BarBundle/Resources/views/Default/index.html.twig');
        $this->assertContains('Hello {{ name }}!', $content);
    }

    public function testGenerateAnnotation(): void
    {
        $this->getGenerator()->generate('Foo\BarBundle', 'FooBarBundle', $this->tmpDir, 'annotation');

        $this->assertFileNotExists($this->tmpDir . '/Foo/BarBundle/Resources/config/routing.yml');
        $this->assertFileNotExists($this->tmpDir . '/Foo/BarBundle/Resources/config/routing.xml');

        $content = file_get_contents($this->tmpDir.'/Foo/BarBundle/Controller/DefaultController.php');
        $this->assertContains('@Route("/hello/{name}"', $content);
    }

    public function testDirIsFile(): void
    {
        $this->filesystem->mkdir($this->tmpDir.'/Foo');
        $this->filesystem->touch($this->tmpDir.'/Foo/BarBundle');

        try {
            $this->getGenerator()->generate('Foo\BarBundle', 'FooBarBundle', $this->tmpDir, 'yml');
            $this->fail('An exception was expected!');
        } catch (RuntimeException $exception) {
            $this->assertEquals(sprintf('Unable to generate the bundle as the target directory "%s" exists but is a file.', realpath($this->tmpDir.'/Foo/BarBundle')), $exception->getMessage());
        }
    }

    public function testIsNotWritableDir(): void
    {
        $this->filesystem->mkdir($this->tmpDir.'/Foo/BarBundle');
        $this->filesystem->chmod($this->tmpDir.'/Foo/BarBundle', 0444);

        try {
            $this->getGenerator()->generate('Foo\BarBundle', 'FooBarBundle', $this->tmpDir, 'yml');
            $this->fail('An exception was expected!');
        } catch (RuntimeException $exception) {
            $this->filesystem->chmod($this->tmpDir.'/Foo/BarBundle', 0777);
            $this->assertEquals(sprintf('Unable to generate the bundle as the target directory "%s" is not writable.', realpath($this->tmpDir.'/Foo/BarBundle')), $exception->getMessage());
        }
    }

    public function testIsNotEmptyDir(): void
    {
        $this->filesystem->mkdir($this->tmpDir.'/Foo/BarBundle');
        $this->filesystem->touch($this->tmpDir.'/Foo/BarBundle/somefile');

        try {
            $this->getGenerator()->generate('Foo\BarBundle', 'FooBarBundle', $this->tmpDir, 'yml');
            $this->fail('An exception was expected!');
        } catch (RuntimeException $exception) {
            $this->filesystem->chmod($this->tmpDir.'/Foo/BarBundle', 0777);
            $this->assertEquals(sprintf('Unable to generate the bundle as the target directory "%s" is not empty.', realpath($this->tmpDir.'/Foo/BarBundle')), $exception->getMessage());
        }
    }

    public function testExistingEmptyDirIsFine(): void
    {
        $this->filesystem->mkdir($this->tmpDir.'/Foo/BarBundle');

        $this->getGenerator()->generate('Foo\BarBundle', 'FooBarBundle', $this->tmpDir, 'yml');
    }

    protected function getGenerator(): BundleGenerator
    {
        $generator = new BundleGenerator($this->filesystem);
        $generator->setSkeletonDirs(__DIR__.'/../../Resources/skeleton');

        return $generator;
    }
}
