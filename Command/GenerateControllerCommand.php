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

use Exception;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zikula\Bundle\GeneratorBundle\Generator\ControllerGenerator;
use Zikula\Bundle\GeneratorBundle\Generator\Generator;

/**
 * Generates controllers.
 *
 * @author Wouter J <wouter@wouterj.nl>
 */
class GenerateControllerCommand extends GeneratorCommand
{
    /**
     * @see Command
     */
    public function configure()
    {
        $this
            ->setDefinition([
                new InputOption(
                    'controller',
                    '',
                    InputOption::VALUE_REQUIRED,
                    'The name of the controller to create'
                ),
                new InputOption(
                    'route-format',
                    '',
                    InputOption::VALUE_REQUIRED,
                    'The format that is used for the routing (yml, xml, php, annotation)',
                    'annotation'
                ),
                new InputOption(
                    'actions',
                    '',
                    InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                    'The actions in the controller'
                ),
            ])
            ->setDescription('Generates a controller')
            ->setHelp(<<<EOT
The <info>zikula:generate:controller</info> command helps you generates new controllers
inside modules.

By default, the command interacts with the developer to tweak the generation.
Any passed option will be used as a default value for the interaction
(<comment>--module</comment> and <comment>--controller</comment> are the only
ones needed if you follow the conventions):

<info>php app/console zikula:generate:controller --controller=AcmeBlogModule:Post</info>

If you want to disable any user interaction, use <comment>--no-interaction</comment>
but don't forget to pass all needed options:

<info>php app/console zikula:generate:controller --controller=AcmeBlogModule:Post --no-interaction</info>
EOT
            )
            ->setName('zikula:generate:controller')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->isInteractive() && !$io->confirm('Do you confirm generation?', true)) {
            $io->error('Command aborted');

            return 1;
        }

        if (null === $input->getOption('controller')) {
            throw new RuntimeException('The controller option must be provided.');
        }

        list($bundle, $controller) = $this->parseShortcutNotation($input->getOption('controller'));
        if (is_string($bundle)) {
            $bundle = Validators::validateBundleName($bundle);

            try {
                $bundle = $this->getContainer()->get('kernel')->getBundle($bundle);
            } catch (Exception $exception) {
                $output->writeln(sprintf('<bg=red>Bundle "%s" does not exists.</>', $bundle));
            }
        }

        $io->block('Controller generation', 'info');

        $io->success('Generating the bundle code!');
        $generator = $this->getGenerator($bundle);
        $generator->generate($bundle, $controller, $input->getOption('route-format'), 'twig', $this->parseActions($input->getOption('actions')));
        $io->success('Code generated!');

        $io->block('You can now start using the generated code!', null, 'bg=blue;fg=white', '  ', true);
    }

    public function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->block('Welcome to the Zikula controller generator', null, 'bg=blue;fg=white', '  ', true);

        // namespace
        $output->writeln([
            '',
            'Every page, and even sections of a page, are rendered by a <comment>controller</comment>.',
            'This command helps you generate them easily.',
            '',
            'First, you need to give the controller name you want to generate.',
            'You must use the shortcut notation like <comment>AcmeBlogModule:Post</comment>',
            '',
        ]);

        $bundle = $controller = '';
        while (true) {
            $controller = $io->ask('Controller name', $input->getOption('controller'), ['Zikula\Bundle\GeneratorBundle\Command\Validators', 'validateControllerName']);
            list($bundle, $controller) = $this->parseShortcutNotation($controller);

            try {
                $b = $this->getContainer()->get('kernel')->getBundle($bundle);

                if (!file_exists($b->getPath().'/Controller/'.$controller.'Controller.php')) {
                    break;
                }

                $io->error(sprintf('Controller "%s:%s" already exists.', $bundle, $controller));
            } catch (Exception $exception) {
                $io->error(sprintf('Bundle "%s" does not exists.', $bundle));
            }
        }
        $input->setOption('controller', $bundle.':'.$controller);

        // routing format
        $output->writeln([
            '',
            'Determine the format to use for the routing.',
            '',
        ]);
        $defaultFormat = ($input->getOption('route-format') ?? 'annotation');
        $routeFormat = $io->choice('Routing format (yml, xml, php, or annotation)', ['yml', 'xml', 'php', 'annotation'], $defaultFormat);
        $input->setOption('route-format', $routeFormat);

        // actions
        $input->setOption('actions', $this->addActions($input, $output, $io));

        // summary
        $io->block('Summary before generation', null, 'bg=blue;fg=white', '  ', true);
        $io->writeln([
            '',
            sprintf('You are going to generate a "<info>%s:%s</info>" controller', $bundle, $controller),
            sprintf('using the "<info>%s</info>" format for the routing', $routeFormat),
            ''
        ]);
    }

    public function addActions(InputInterface $input, OutputInterface $output, SymfonyStyle $io): array
    {
        $output->writeln([
            '',
            'Instead of starting with a blank controller, you can add some actions now. An action',
            'is a PHP function or method that executes, for example, when a given route is matched.',
            'Actions should be suffixed by <comment>Action</comment>.',
            '',
        ]);

        $templateNameValidator = function($name) {
            if ('default' === $name) {
                return $name;
            }

            if (2 !== substr_count($name, ':')) {
                throw new InvalidArgumentException(sprintf('Template name "%s" does not have 2 colons', $name));
            }

            return $name;
        };

        $actions = $this->parseActions($input->getOption('actions'));

        while (true) {
            // name
            $output->writeln('');
            $actionName = $io->ask('New action name (press <return> to stop adding actions)', null, function ($name) use ($actions) {
                if (null === $name) {
                    return $name;
                }

                if (isset($actions[$name])) {
                    throw new InvalidArgumentException(sprintf('Action "%s" is already defined', $name));
                }

                if ('Action' !== substr($name, -6)) {
                    throw new InvalidArgumentException(sprintf('Name "%s" is not suffixed by Action', $name));
                }

                return $name;
            });
            if (!$actionName) {
                break;
            }

            // route
            $route = $io->ask('Action route', '/' . substr($actionName, 0, -6));
            $placeholders = $this->getPlaceholdersFromRoute($route);

            // template
            $defaultTemplate = $input->getOption('controller').':'.substr($actionName, 0, -6).'.html.twig';
            $template = $io->ask('Templatename (optional)', $defaultTemplate, $templateNameValidator);

            // adding action
            $actions[$actionName] = [
                'name'         => $actionName,
                'route'        => $route,
                'placeholders' => $placeholders,
                'template'     => $template,
            ];
        }

        return $actions;
    }

    public function parseActions($actions): array
    {
        if (is_array($actions)) {
            return $actions;
        }

        $newActions = [];

        foreach (explode(' ', $actions) as $action) {
            $data = explode(':', $action);

            // name
            if (!isset($data[0])) {
                throw new InvalidArgumentException('An action must have a name');
            }
            $name = array_shift($data);

            // route
            $route = (isset($data[0]) && '' !== $data[0]) ? array_shift($data) : '/' . substr($name, 0, -6);
            if ($route) {
                $placeholders = $this->getPlaceholdersFromRoute($route);
            } else {
                $placeholders = [];
            }

            // template
            $template = (0 < count($data) && '' !== $data[0]) ? implode(':', $data) : 'default';

            $newActions[$name] = [
                'name'         => $name,
                'route'        => $route,
                'placeholders' => $placeholders,
                'template'     => $template
            ];
        }

        return $newActions;
    }

    public function getPlaceholdersFromRoute($route)
    {
        preg_match_all('/{(.*?)}/', $route, $placeholders);
        $placeholders = $placeholders[1];

        return $placeholders;
    }

    public function parseShortcutNotation($shortcut): array
    {
        $entity = str_replace('/', '\\', $shortcut);

        if (false === $pos = strpos($entity, ':')) {
            throw new InvalidArgumentException(sprintf('The controller name must contain a : ("%s" given, expecting something like AcmeBlogModule:Post)', $entity));
        }

        return [substr($entity, 0, $pos), substr($entity, $pos + 1)];
    }

    protected function createGenerator(): Generator
    {
        return new ControllerGenerator($this->getContainer()->get('filesystem'));
    }
}
