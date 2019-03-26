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

use InvalidArgumentException;
use RuntimeException;

/**
 * Validator functions.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Validators
{
    public static function validateBundleNamespace(string $namespace): string
    {
        $namespace = str_replace('/', '\\', $namespace);
        if (!preg_match('/^(?:[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\\\?)+$/', $namespace)) {
            throw new InvalidArgumentException('The namespace contains invalid characters.');
        }

        if (!preg_match('/Module$/', $namespace)) {
            throw new InvalidArgumentException('The namespace must end with Module.');
        }

        // validate reserved keywords
        $reserved = self::getReservedWords();
        foreach (explode('\\', $namespace) as $word) {
            if (in_array(strtolower($word), $reserved, true)) {
                throw new InvalidArgumentException(sprintf('The namespace cannot contain PHP reserved words ("%s").', $word));
            }
        }

        // validate that the namespace is at least one level deep
        if (false === strpos($namespace, '\\')) {
            $msg = [];
            $msg[] = sprintf('The namespace must contain a vendor namespace (e.g. "VendorName\%s" instead of simply "%s").', $namespace, $namespace);
            $msg[] = 'If you\'ve specified a vendor namespace, did you forget to surround it with quotes (init:module "Acme\BlogModule")?';

            throw new InvalidArgumentException(implode("\n\n", $msg));
        }

        return $namespace;
    }

    public static function validateBundleName(string $bundle): string
    {
        if (!preg_match('/Module$/', $bundle)) {
            throw new InvalidArgumentException('The module name must end with Module.');
        }

        return $bundle;
    }

    public static function validateControllerName(string $controller): string
    {
        try {
            self::validateEntityName($controller);
        } catch (InvalidArgumentException $exception) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The controller name must contain a : ("%s" given, expecting something like AcmeBlogModule:Post)',
                    $controller
                )
            );
        }

        return $controller;
    }

    public static function validateTargetDir(string $dir/*, string $bundle, string $namespace*/): string
    {
        // add trailing / if necessary
        return '/' === $dir[strlen($dir) - 1] ? $dir : $dir.'/';
    }

    public static function validateFormat(string $format): string
    {
        $format = strtolower($format);

        if (!in_array($format, ['php', 'xml', 'yml', 'annotation'])) {
            throw new RuntimeException(sprintf('Format "%s" is not supported.', $format));
        }

        return $format;
    }

    public static function validateEntityName(string $entity): string
    {
        if (false === strpos($entity, ':')) {
            throw new InvalidArgumentException(sprintf('The entity name must contain a ":" ("%s" given, expecting something like AcmeBlogModule:Post)', $entity));
        }

        return $entity;
    }

    public static function getReservedWords(): array
    {
        return [
            'abstract',
            'and',
            'array',
            'as',
            'break',
            'case',
            'catch',
            'class',
            'clone',
            'const',
            'continue',
            'declare',
            'default',
            'do',
            'else',
            'elseif',
            'enddeclare',
            'endfor',
            'endforeach',
            'endif',
            'endswitch',
            'endwhile',
            'extends',
            'final',
            'for',
            'foreach',
            'function',
            'global',
            'goto',
            'if',
            'implements',
            'interface',
            'instanceof',
            'namespace',
            'new',
            'or',
            'private',
            'protected',
            'public',
            'static',
            'switch',
            'throw',
            'try',
            'use',
            'var',
            'while',
            'xor',
            '__CLASS__',
            '__DIR__',
            '__FILE__',
            '__LINE__',
            '__FUNCTION__',
            '__METHOD__',
            '__NAMESPACE__',
            'die',
            'echo',
            'empty',
            'exit',
            'eval',
            'include',
            'include_once',
            'isset',
            'list',
            'require',
            'require_once',
            'return',
            'print',
            'unset'
        ];
    }
}
