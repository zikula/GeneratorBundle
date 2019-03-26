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

namespace Zikula\Bundle\GeneratorBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Mapping\DisconnectedMetadataFactory;
use InvalidArgumentException;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;

abstract class GenerateDoctrineCommand extends GeneratorCommand
{
    public function isEnabled(): bool
    {
        return class_exists(DoctrineBundle::class);
    }

    protected function parseShortcutNotation(string $shortcut): array
    {
        $entity = str_replace('/', '\\', $shortcut);

        if (false === $pos = strpos($entity, ':')) {
            throw new InvalidArgumentException(sprintf('The entity name must contain a ":" ("%s" given, expecting something like AcmeBlogModule:Post)', $entity));
        }

        return [substr($entity, 0, $pos), substr($entity, $pos + 1)];
    }

    protected function getEntityMetadata(string $entity): array
    {
        $factory = new DisconnectedMetadataFactory($this->getContainer()->get('doctrine'));

        return $factory->getClassMetadata($entity)->getMetadata();
    }
}
