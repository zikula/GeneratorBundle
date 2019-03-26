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

use Zikula\Bundle\GeneratorBundle\Generator\DoctrineFormGenerator;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

class DoctrineFormGeneratorTest extends GeneratorTest
{
    public function testGenerate(): void
    {
        $generator = new DoctrineFormGenerator($this->filesystem);
        $generator->setSkeletonDirs(__DIR__.'/../../Resources/skeleton');

        $bundle = $this->getMock(BundleInterface::class);
        $bundle->method('getPath')->willReturn($this->tmpDir);
        $bundle->method('getNamespace')->willReturn('Foo\BarBundle');

        $metadata = $this->getMockBuilder(ClassMetadataInfo::class)->disableOriginalConstructor()->getMock();
        $metadata->identifier = ['id'];
        $metadata->associationMappings = ['title' => ['type' => 'string']];

        $generator->generate($bundle, 'Post', $metadata);

        $this->assertFileExists($this->tmpDir . '/Form/PostType.php');

        $content = file_get_contents($this->tmpDir.'/Form/PostType.php');
        $this->assertContains('->add(\'title\')', $content);
        $this->assertContains('class PostType extends AbstractType', $content);
        $this->assertContains("'data_class' => 'Foo\BarBundle\Entity\Post'", $content);
        $this->assertContains("'foo_barbundle_posttype'", $content);
    }
}
