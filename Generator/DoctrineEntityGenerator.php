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

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Tools\EntityGenerator;
use Doctrine\ORM\Tools\EntityRepositoryGenerator;
use Doctrine\ORM\Tools\Export\ClassMetadataExporter;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Generates a Doctrine entity class based on its name, fields and format.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 */
class DoctrineEntityGenerator extends Generator
{
    private $registry;

    public function __construct(Filesystem $filesystem, RegistryInterface $registry)
    {
        parent::__construct($filesystem);
        $this->registry = $registry;
    }

    public function generate(BundleInterface $bundle, string $entity, string $format, array $fields, bool $withRepository): void
    {
        // configure the bundle (needed if the bundle does not contain any Entities yet)
        $config = $this->registry->getEntityManager()->getConfiguration();
        $config->setEntityNamespaces(array_merge(
            [$bundle->getName() => $bundle->getNamespace().'\\Entity'],
            $config->getEntityNamespaces()
        ));

        $entityClass = $this->registry->getEntityNamespace($bundle->getName()).'\\'.$entity;
        $entityPath = $bundle->getPath().'/Entity/'.str_replace('\\', '/', $entity).'.php';
        if (file_exists($entityPath)) {
            throw new RuntimeException(sprintf('Entity "%s" already exists.', $entityClass));
        }

        $class = new ClassMetadataInfo($entityClass);
        if ($withRepository) {
            $class->customRepositoryClassName = $entityClass.'Repository';
        }
        $class->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
        $class->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_AUTO);
        foreach ($fields as $field) {
            $class->mapField($field);
        }

        $entityGenerator = $this->getEntityGenerator();
        if ('annotation' === $format) {
            $entityGenerator->setGenerateAnnotations(true);
            $entityCode = $entityGenerator->generateEntityClass($class);
            $mappingPath = $mappingCode = false;
        } else {
            $cme = new ClassMetadataExporter();
            $exporter = $cme->getExporter('yml' === $format ? 'yaml' : $format);
            $mappingPath = $bundle->getPath().'/Resources/config/doctrine/'.str_replace('\\', '.', $entity).'.orm.'.$format;

            if (file_exists($mappingPath)) {
                throw new \RuntimeException(sprintf('Cannot generate entity when mapping "%s" already exists.', $mappingPath));
            }

            $mappingCode = $exporter->exportClassMetadata($class);
            $entityGenerator->setGenerateAnnotations(false);
            $entityCode = $entityGenerator->generateEntityClass($class);
        }

        $this->filesystem->mkdir(dirname($entityPath));
        file_put_contents($entityPath, $entityCode);

        if ($mappingPath) {
            $this->filesystem->mkdir(dirname($mappingPath));
            file_put_contents($mappingPath, $mappingCode);
        }

        if ($withRepository) {
            $path = $bundle->getPath().str_repeat('/..', substr_count(get_class($bundle), '\\'));
            $this->getRepositoryGenerator()->writeEntityRepositoryClass($class->customRepositoryClassName, $path);
        }
    }

    public function isReservedKeyword(string $keyword): bool
    {
        return $this->registry->getConnection()->getDatabasePlatform()->getReservedKeywordsList()->isKeyword($keyword);
    }

    protected function getEntityGenerator(): EntityGenerator
    {
        $entityGenerator = new EntityGenerator();
        $entityGenerator->setGenerateAnnotations(false);
        $entityGenerator->setGenerateStubMethods(true);
        $entityGenerator->setRegenerateEntityIfExists(false);
        $entityGenerator->setUpdateEntityIfExists(true);
        $entityGenerator->setNumSpaces(4);
        $entityGenerator->setAnnotationPrefix('ORM\\');

        return $entityGenerator;
    }

    protected function getRepositoryGenerator(): EntityRepositoryGenerator
    {
        return new EntityRepositoryGenerator();
    }
}
