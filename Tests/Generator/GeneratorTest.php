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

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

abstract class GeneratorTest extends TestCase
{
    protected $filesystem;
    protected $tmpDir;

    public function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/sf2';
        $this->filesystem = new Filesystem();
        $this->filesystem->remove($this->tmpDir);
    }

    public function tearDown(): void
    {
        $this->filesystem->remove($this->tmpDir);
    }
}
