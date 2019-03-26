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

namespace Zikula\Bundle\GeneratorBundle\Generator;

use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Generator is the base class for all generators.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Generator
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var array
     */
    private $skeletonDirs;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Sets an array of directories to look for templates.
     *
     * The directories must be sorted from the most specific to the most
     * directory.
     */
    public function setSkeletonDirs(array $skeletonDirs = []): void
    {
        $this->skeletonDirs = $skeletonDirs;
    }

    protected function render(string $template, array $parameters): string
    {
        $twig = new Environment(new FilesystemLoader($this->skeletonDirs), [
            'debug'            => true,
            'cache'            => false,
            'strict_variables' => true,
            'autoescape'       => false,
        ]);

        return $twig->render($template, $parameters);
    }

    protected function renderFile(string $template, string $target, array $parameters = [])
    {
        if (!is_dir(dirname($target)) && !mkdir($concurrentDirectory = dirname($target), 0777, true) && !is_dir($concurrentDirectory)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }

        return file_put_contents($target, $this->render($template, $parameters));
    }
}
