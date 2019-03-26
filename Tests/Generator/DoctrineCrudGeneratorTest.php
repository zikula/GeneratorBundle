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

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Zikula\Bundle\GeneratorBundle\Generator\DoctrineCrudGenerator;

class DoctrineCrudGeneratorTest extends GeneratorTest
{
    public function testGenerateYamlFull(): void
    {
        $this->getGenerator()->generate($this->getBundle(), 'Post', $this->getMetadata(), 'yml', '/post', true, true);

        $files = [
            'Controller/PostController.php',
            'Tests/Controller/PostControllerTest.php',
            'Resources/config/routing/post.yml',
            'Resources/views/Post/index.html.twig',
            'Resources/views/Post/show.html.twig',
            'Resources/views/Post/new.html.twig',
            'Resources/views/Post/edit.html.twig'
        ];
        foreach ($files as $file) {
            $this->assertFileExists($this->tmpDir . '/' . $file, sprintf('%s has been generated', $file));
        }

        $files = [
            'Resources/config/routing/post.xml'
        ];
        foreach ($files as $file) {
            $this->assertFileNotExists($this->tmpDir . '/' . $file, sprintf('%s has not been generated', $file));
        }

        $content = file_get_contents($this->tmpDir.'/Controller/PostController.php');
        $strings = [
            'namespace Foo\BarBundle\Controller;',
            'public function indexAction',
            'public function showAction',
            'public function newAction',
            'public function editAction'
        ];
        foreach ($strings as $string) {
            $this->assertContains($string, $content);
        }
    }

    public function testGenerateXml(): void
    {
        $this->getGenerator()->generate($this->getBundle(), 'Post', $this->getMetadata(), 'xml', '/post', false, true);

        $files = [
            'Controller/PostController.php',
            'Tests/Controller/PostControllerTest.php',
            'Resources/config/routing/post.xml',
            'Resources/views/Post/index.html.twig',
            'Resources/views/Post/show.html.twig'
        ];
        foreach ($files as $file) {
            $this->assertFileExists($this->tmpDir . '/' . $file, sprintf('%s has been generated', $file));
        }

        $files = [
            'Resources/config/routing/post.yml',
            'Resources/views/Post/new.html.twig',
            'Resources/views/Post/edit.html.twig'
        ];
        foreach ($files as $file) {
            $this->assertFileNotExists($this->tmpDir . '/' . $file, sprintf('%s has not been generated', $file));
        }

        $content = file_get_contents($this->tmpDir.'/Controller/PostController.php');
        $strings = [
            'namespace Foo\BarBundle\Controller;',
            'public function indexAction',
            'public function showAction'
        ];
        foreach ($strings as $string) {
            $this->assertContains($string, $content);
        }

        $content = file_get_contents($this->tmpDir.'/Controller/PostController.php');
        $strings = [
            'public function newAction',
            'public function editAction',
            '@Route'
        ];
        foreach ($strings as $string) {
            $this->assertNotContains($string, $content);
        }
    }

    public function testGenerateAnnotationWrite(): void
    {
        $this->getGenerator()->generate($this->getBundle(), 'Post', $this->getMetadata(), 'annotation', '/post', true, true);

        $files = [
            'Controller/PostController.php',
            'Tests/Controller/PostControllerTest.php',
            'Resources/views/Post/index.html.twig',
            'Resources/views/Post/show.html.twig',
            'Resources/views/Post/new.html.twig',
            'Resources/views/Post/edit.html.twig'
        ];
        foreach ($files as $file) {
            $this->assertFileExists($this->tmpDir . '/' . $file, sprintf('%s has been generated', $file));
        }

        $files = [
            'Resources/config/routing/post.yml',
            'Resources/config/routing/post.xml'
        ];
        foreach ($files as $file) {
            $this->assertFileNotExists($this->tmpDir . '/' . $file, sprintf('%s has not been generated', $file));
        }

        $content = file_get_contents($this->tmpDir.'/Controller/PostController.php');
        $strings = [
            'namespace Foo\BarBundle\Controller;',
            'public function indexAction',
            'public function showAction',
            'public function newAction',
            'public function editAction',
            '@Route'
        ];
        foreach ($strings as $string) {
            $this->assertContains($string, $content);
        }
    }

    public function testGenerateAnnotation(): void
    {
        $this->getGenerator()->generate($this->getBundle(), 'Post', $this->getMetadata(), 'annotation', '/post', false, true);

        $files = [
            'Controller/PostController.php',
            'Tests/Controller/PostControllerTest.php',
            'Resources/views/Post/index.html.twig',
            'Resources/views/Post/show.html.twig'
        ];
        foreach ($files as $file) {
            $this->assertFileExists($this->tmpDir . '/' . $file, sprintf('%s has been generated', $file));
        }

        $files = [
            'Resources/config/routing/post.yml',
            'Resources/config/routing/post.xml',
            'Resources/views/Post/new.html.twig',
            'Resources/views/Post/edit.html.twig'
        ];
        foreach ($files as $file) {
            $this->assertFileNotExists($this->tmpDir . '/' . $file, sprintf('%s has not been generated', $file));
        }

        $content = file_get_contents($this->tmpDir.'/Controller/PostController.php');
        $strings = [
            'namespace Foo\BarBundle\Controller;',
            'public function indexAction',
            'public function showAction',
            '@Route'
        ];
        foreach ($strings as $string) {
            $this->assertContains($string, $content);
        }

        $content = file_get_contents($this->tmpDir.'/Controller/PostController.php');
        $strings = [
            'public function newAction',
            'public function editAction'
        ];
        foreach ($strings as $string) {
            $this->assertNotContains($string, $content);
        }
    }

    protected function getGenerator(): DoctrineCrudGenerator
    {
        $generator = new DoctrineCrudGenerator($this->filesystem);
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

    public function getMetadata(): ClassMetadataInfo
    {
        $metadata = $this->getMockBuilder(ClassMetadataInfo::class)->disableOriginalConstructor()->getMock();
        $metadata->identifier = ['id'];
        $metadata->fieldMappings = ['title' => ['type' => 'string']];

        return $metadata;
    }
}
