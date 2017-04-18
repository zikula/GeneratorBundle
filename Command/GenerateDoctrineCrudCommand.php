<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\Bundle\GeneratorBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zikula\Bundle\GeneratorBundle\Generator\DoctrineCrudGenerator;
use Zikula\Bundle\GeneratorBundle\Generator\DoctrineFormGenerator;
use Zikula\Bundle\GeneratorBundle\Manipulator\RoutingManipulator;

/**
 * Generates a CRUD for a Doctrine entity.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class GenerateDoctrineCrudCommand extends GenerateDoctrineCommand
{
    private $formGenerator;

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDefinition([
                new InputOption('entity', '', InputOption::VALUE_REQUIRED, 'The entity class name to initialize (shortcut notation)'),
                new InputOption('route-prefix', '', InputOption::VALUE_REQUIRED, 'The route prefix'),
                new InputOption('with-write', '', InputOption::VALUE_NONE, 'Whether or not to generate create, new and delete actions'),
                new InputOption('format', '', InputOption::VALUE_REQUIRED, 'Use the format for configuration files (php, xml, yml, or annotation)', 'annotation'),
                new InputOption('overwrite', '', InputOption::VALUE_NONE, 'Do not stop the generation if crud controller already exist, thus overwriting all generated files'),
            ])
            ->setDescription('Generates a CRUD based on a Doctrine entity')
            ->setHelp(<<<EOT
The <info>zikula:doctrine:generate:crud</info> command generates a CRUD based on a Doctrine entity.

The default command only generates the list and show actions.

<info>php app/console zikula:doctrine:generate:crud --entity=AcmeBlogModule:Post --route-prefix=post_admin</info>

Using the --with-write option allows to generate the new, edit and delete actions.

<info>php app/console zikula:doctrine:generate:crud --entity=AcmeBlogModule:Post --route-prefix=post_admin --with-write</info>
EOT
            )
            ->setName('zikula:doctrine:generate:crud')
            ->setAliases(['zikula:generate:doctrine:crud'])
        ;
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->isInteractive()) {
            if (!$io->confirm('Do you confirm generation?', true)) {
                $io->error('Command aborted');

                return 1;
            }
        }

        $entity = Validators::validateEntityName($input->getOption('entity'));
        list($bundle, $entity) = $this->parseShortcutNotation($entity);

        $format = Validators::validateFormat($input->getOption('format'));
        $prefix = $this->getRoutePrefix($input, $entity);
        $withWrite = $input->getOption('with-write');
        $forceOverwrite = $input->getOption('overwrite');

        $io->block('CRUD generation', 'info');

        $entityClass = $this->getContainer()->get('doctrine')->getAliasNamespace($bundle).'\\'.$entity;
        $metadata    = $this->getEntityMetadata($entityClass);
        $bundle      = $this->getContainer()->get('kernel')->getBundle($bundle);

        $generator = $this->getGenerator($bundle);
        $generator->generate($bundle, $entity, $metadata[0], $format, $prefix, $withWrite, $forceOverwrite);

        $output->writeln('Generating the CRUD code: <info>OK</info>');

        $errors = [];
        $runner = function ($err) use ($output, &$errors) {
            if ($err) {
                $output->writeln('<fg=red>FAILED</>');
                $errors = array_merge($errors, $err);
            } else {
                $output->writeln('<info>OK</info>');
            }
        };

        // form
        if ($withWrite) {
            $this->generateForm($bundle, $entity, $metadata);
            $output->writeln('Generating the Form code: <info>OK</info>');
        }

        // routing
        if ('annotation' != $format) {
            $runner($this->updateRouting($io, $input, $output, $bundle, $format, $entity, $prefix));
        }

        $io->block('You can now start using the generated code!', null, 'bg=blue;fg=white', '  ', true);

        return 0;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->block('Welcome to the Doctrine2 CRUD generator', null, 'bg=blue;fg=white', '  ', true);

        // namespace
        $output->writeln([
            '',
            'This command helps you generate CRUD controllers and templates.',
            '',
            'First, you need to give the entity for which you want to generate a CRUD.',
            'You can give an entity that does not exist yet and the wizard will help',
            'you defining it.',
            '',
            'You must use the shortcut notation like <comment>AcmeBlogModule:Post</comment>.',
            '',
        ]);

        $entity = $io->ask('The Entity shortcut name', $input->getOption('entity'), ['Zikula\Bundle\GeneratorBundle\Command\Validators', 'validateEntityName']);
        $input->setOption('entity', $entity);
        list($bundle, $entity) = $this->parseShortcutNotation($entity);

        // Entity exists?
        try {
            $entityClass = $this->getContainer()->get('doctrine')->getAliasNamespace($bundle) . '\\' . $entity;
            $metadata = $this->getEntityMetadata($entityClass);
        } catch (\Doctrine\ORM\ORMException $e) {
        }
        if (isset($metadata)) {
            $io->error('Entity already exists!');
            exit;
        }


        // write?
        $withWrite = $input->getOption('with-write') ?: false;
        $output->writeln([
            '',
            'By default, the generator creates two actions: list and show.',
            'You can also ask it to generate "write" actions: new, update, and delete.',
            '',
        ]);
        $withWrite = $io->confirm('Do you want to generate the "write" actions', $withWrite ? true : false);
        $input->setOption('with-write', $withWrite);

        // format
        $output->writeln([
            '',
            'Determine the format to use for the generated CRUD.',
            '',
        ]);
        $defaultFormat = (null !== $input->getOption('format') ? $input->getOption('format') : 'annotation');
        $format = $io->choice('Configuration format (yml, xml, php, or annotation)', ['yml', 'xml', 'php', 'annotation'], $defaultFormat);
        $input->setOption('format', $format);

        // route prefix
        $prefix = $this->getRoutePrefix($input, $entity);
        $output->writeln([
            '',
            'Determine the routes prefix (all the routes will be "mounted" under this',
            'prefix: /prefix/, /prefix/new, ...).',
            '',
        ]);
        $prefix = $io->ask('Routes prefix', '/'.$prefix);
        $input->setOption('route-prefix', $prefix);

        // summary
        $output->writeln([
            '',
            $this->getHelper('formatter')->formatBlock('Summary before generation', 'bg=blue;fg=white', true),
            '',
            sprintf("You are going to generate a CRUD controller for \"<info>%s:%s</info>\"", $bundle, $entity),
            sprintf("using the \"<info>%s</info>\" format.", $format),
            '',
        ]);
    }

    /**
     * Tries to generate forms if they don't exist yet and if we need write operations on entities.
     */
    protected function generateForm($bundle, $entity, $metadata)
    {
        try {
            $this->getFormGenerator($bundle)->generate($bundle, $entity, $metadata[0]);
        } catch (\RuntimeException $e ) {
            // form already exists
        }
    }

    protected function updateRouting(SymfonyStyle $io, InputInterface $input, OutputInterface $output, $bundle, $format, $entity, $prefix)
    {
        $auto = true;
        if ($input->isInteractive()) {
            $auto = $io->ask('Confirm automatic update of the Routing', true);
        }

        $output->write('Importing the CRUD routes: ');
        $this->getContainer()->get('filesystem')->mkdir($bundle->getPath().'/Resources/config/');
        $routing = new RoutingManipulator($bundle->getPath().'/Resources/config/routing.yml');
        try {
            $ret = $auto ? $routing->addResource($bundle->getName(), $format, '/'.$prefix, 'routing/'.strtolower(str_replace('\\', '_', $entity))) : false;
        } catch (\RuntimeException $exc) {
            $ret = false;
        }

        if (!$ret) {
            $help = sprintf("        <comment>resource: \"@%s/Resources/config/routing/%s.%s\"</comment>\n", $bundle->getName(), strtolower(str_replace('\\', '_', $entity)), $format);
            $help .= sprintf("        <comment>prefix:   /%s</comment>\n", $prefix);

            return [
                '- Import the bundle\'s routing resource in the bundle routing file',
                sprintf('  (%s).', $bundle->getPath().'/Resources/config/routing.yml'),
                '',
                sprintf('    <comment>%s:</comment>', $bundle->getName().('' !== $prefix ? '_'.str_replace('/', '_', $prefix) : '')),
                $help,
                '',
            ];
        }
    }

    protected function getRoutePrefix(InputInterface $input, $entity)
    {
        $prefix = $input->getOption('route-prefix') ?: strtolower(str_replace(['\\', '/'], '_', $entity));

        if ($prefix && '/' === $prefix[0]) {
            $prefix = substr($prefix, 1);
        }

        return $prefix;
    }

    protected function createGenerator($bundle = null)
    {
        return new DoctrineCrudGenerator($this->getContainer()->get('filesystem'));
    }

    protected function getFormGenerator($bundle = null)
    {
        if (null === $this->formGenerator) {
            $this->formGenerator = new DoctrineFormGenerator($this->getContainer()->get('filesystem'));
            $this->formGenerator->setSkeletonDirs($this->getSkeletonDirs($bundle));
        }

        return $this->formGenerator;
    }

    public function setFormGenerator(DoctrineFormGenerator $formGenerator)
    {
        $this->formGenerator = $formGenerator;
    }
}
