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
use Symfony\Component\Console\Output\Output;
use Zikula\Bundle\GeneratorBundle\Generator\BundleGenerator;

/**
 * Generates bundles.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class GenerateBundleCommand extends GeneratorCommand
{
    private $generator;

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDefinition([
                new InputOption('namespace', '', InputOption::VALUE_REQUIRED, 'The namespace of the module to create'),
                new InputOption('dir', '', InputOption::VALUE_REQUIRED, 'The directory where to create the module'),
                new InputOption('module-name', '', InputOption::VALUE_REQUIRED, 'The optional module name'),
                new InputOption('format', '', InputOption::VALUE_REQUIRED, 'Use the format for configuration files (php, xml, yml, or annotation)'),
            ])
            ->setDescription('Generates a module')
            ->setHelp(<<<EOT
The <info>generate:module</info> command helps you generates new modules.

By default, the command interacts with the developer to tweak the generation.
Any passed option will be used as a default value for the interaction
(<comment>--namespace</comment> is the only one needed if you follow the
conventions):

<info>php app/console generate:module ----namespace=Acme/BlogModule</info>

Note that you can use <comment>/</comment> instead of <comment>\\ </comment>for the namespace delimiter to avoid any
problem.

If you want to disable any user interaction, use <comment>--no-interaction</comment> but don't forget to pass all needed options:

<info>php app/console generate:module --namespace=Acme/BlogModule --dir=src [--module-name=...] --no-interaction</info>

Note that the module namespace must end with "Module".
EOT
            )
            ->setName('generate:module')
        ;
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When namespace doesn't end with Bundle
     * @throws \RuntimeException         When bundle can't be executed
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();

        if ($input->isInteractive()) {
            if (!$dialog->askConfirmation($output, $dialog->getQuestion('Do you confirm generation', 'yes', '?'), true)) {
                $output->writeln('<error>Command aborted</error>');

                return 1;
            }
        }

        foreach (['namespace', 'dir'] as $option) {
            if (null === $input->getOption($option)) {
                throw new \RuntimeException(sprintf('The "%s" option must be provided.', $option));
            }
        }

        $namespace = Validators::validateBundleNamespace($input->getOption('namespace'));
        if (!$bundle = $input->getOption('module-name')) {
            $bundle = strtr($namespace, ['\\' => '']);
        }
        $bundle = Validators::validateBundleName($bundle);
        $dir = Validators::validateTargetDir($input->getOption('dir'), $bundle, $namespace);
        if (null === $input->getOption('format')) {
            $input->setOption('format', 'annotation');
        }
        $format = Validators::validateFormat($input->getOption('format'));

        $dialog->writeSection($output, 'Module generation');

        if (!$this->getContainer()->get('filesystem')->isAbsolutePath($dir)) {
            $dir = getcwd().'/'.$dir;
        }

        $generator = $this->getGenerator();
        $generator->generate($namespace, $bundle, $dir, $format);

        $output->writeln('Generating the module code: <info>OK</info>');

        $errors = [];
        //$runner = $dialog->getRunner($output, $errors);

        // routing
        //$runner($this->updateRouting($dialog, $input, $output, $bundle, $format));

        $dialog->writeGeneratorSummary($output, $errors);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();
        $dialog->writeSection($output, 'Welcome to the Zikula module generator');

        // namespace
        $namespace = null;
        try {
            $namespace = $input->getOption('namespace') ? Validators::validateBundleNamespace($input->getOption('namespace')) : null;
        } catch (\Exception $error) {
            $output->writeln($dialog->getHelperSet()->get('formatter')->formatBlock($error->getMessage(), 'error'));
        }

        if (null === $namespace) {
            $output->writeln([
                '',
                'Your application code must be written in <comment>modules</comment>. This command helps',
                'you generate them easily.',
                '',
                'Each module is hosted under a namespace (like <comment>Acme/Module/BlogModule</comment>).',
                'The namespace should begin with a "vendor" name like your company name, your',
                'project name, or your client name, followed by one or more optional category',
                'sub-namespaces, and it should end with the module name itself',
                '(which must have <comment>Module</comment> as a suffix).',
                '',
                'See http://zikula.org/doc/current/cookbook/modules/best_practices.html#index-1 for more',
                'details on module naming conventions.',
                '',
                'Use <comment>/</comment> instead of <comment>\\ </comment> for the namespace delimiter to avoid any problem.',
                '',
            ]);

            $namespace = $dialog->askAndValidate($output, $dialog->getQuestion('Module namespace', $input->getOption('namespace')), ['Zikula\Bundle\GeneratorBundle\Command\Validators', 'validateBundleNamespace'], false, $input->getOption('namespace'));
            $input->setOption('namespace', $namespace);
        }

        // bundle name
        $bundle = null;
        try {
            $bundle = $input->getOption('module-name') ? Validators::validateBundleName($input->getOption('module-name')) : null;
        } catch (\Exception $error) {
            $output->writeln($dialog->getHelperSet()->get('formatter')->formatBlock($error->getMessage(), 'error'));
        }

        if (null === $bundle) {
            $bundle = strtr($namespace, ['\\Module\\' => '', '\\' => '']);

            $output->writeln([
                '',
                'In your code, a module is often referenced by its name. It can be the',
                'concatenation of all namespace parts but it\'s really up to you to come',
                'up with a unique name (a good practice is to start with the vendor name).',
                'Based on the namespace, we suggest <comment>'.$bundle.'</comment>.',
                '',
            ]);
            $bundle = $dialog->askAndValidate($output, $dialog->getQuestion('Module name', $bundle), ['Zikula\Bundle\GeneratorBundle\Command\Validators', 'validateBundleName'], false, $bundle);
            $input->setOption('module-name', $bundle);
        }

        // target dir
        $dir = null;
        try {
            $dir = $input->getOption('dir') ? Validators::validateTargetDir($input->getOption('dir'), $bundle, $namespace) : null;
        } catch (\Exception $error) {
            $output->writeln($dialog->getHelperSet()->get('formatter')->formatBlock($error->getMessage(), 'error'));
        }

        if (null === $dir) {
            $dir = dirname($this->getContainer()->getParameter('kernel.root_dir')).'/src';

            $output->writeln([
                '',
                'The module can be generated anywhere. The suggested default directory uses',
                'the standard conventions.',
                '',
            ]);
            $dir = $dialog->askAndValidate($output, $dialog->getQuestion('Target directory', $dir), function ($dir) use ($bundle, $namespace) { return Validators::validateTargetDir($dir, $bundle, $namespace); }, false, $dir);
            $input->setOption('dir', $dir);
        }

        // format
        $format = null;
        try {
            $format = $input->getOption('format') ? Validators::validateFormat($input->getOption('format')) : null;
        } catch (\Exception $error) {
            $output->writeln($dialog->getHelperSet()->get('formatter')->formatBlock($error->getMessage(), 'error'));
        }

        if (null === $format) {
            $output->writeln([
                '',
                'Determine the format to use for the generated configuration.',
                '',
            ]);
            $format = $dialog->askAndValidate($output, $dialog->getQuestion('Configuration format (yml, xml, php, or annotation)', $input->getOption('format')), ['Zikula\Bundle\GeneratorBundle\Command\Validators', 'validateFormat'], false, $input->getOption('format'));
            $input->setOption('format', $format);
        }

        // optional files to generate
        $output->writeln([
            '',
            'To help you get started faster, the command can generate some',
            'code snippets for you.',
            '',
        ]);

        // summary
        $output->writeln([
            '',
            $this->getHelper('formatter')->formatBlock('Summary before generation', 'bg=blue;fg=white', true),
            '',
            sprintf("You are going to generate a \"<info>%s\\%s</info>\" module\nin \"<info>%s</info>\" using the \"<info>%s</info>\" format.", $namespace, $bundle, $dir, $format),
            '',
        ]);
    }


    protected function createGenerator()
    {
        return new BundleGenerator($this->getContainer()->get('filesystem'));
    }
}
