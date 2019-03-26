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

namespace Zikula\Bundle\GeneratorBundle\Command\Helper;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generates bundles.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class DialogHelper extends QuestionHelper
{
    public function writeGeneratorSummary(OutputInterface $output, $errors): void
    {
        if (!$errors) {
            $this->writeSection($output, 'You can now start using the generated code!');
        } else {
            $this->writeSection($output, [
                'The command was not able to configure everything automatically.',
                'You must do the following changes manually.'
            ], 'error');

            $output->writeln($errors);
        }
    }

    public function getRunner(OutputInterface $output, &$errors): callable
    {
        $runner = function ($err) use ($output, &$errors) {
            if ($err) {
                $output->writeln('<fg=red>FAILED</>');
                $errors = array_merge($errors, $err);
            } else {
                $output->writeln('<info>OK</info>');
            }
        };

        return $runner;
    }

    public function writeSection(OutputInterface $output, $text, $style = 'bg=blue;fg=white'): void
    {
        $output->writeln([
            '',
            null !== $this->getHelperSet() ? $this->getHelperSet()->get('formatter')->formatBlock($text, $style, true) : $text,
            ''
        ]);
    }

    public function getQuestion($question, $default, $sep = ':')
    {
        return $default ? sprintf('<info>%s</info> [<comment>%s</comment>]%s ', $question, $default, $sep) : sprintf('<info>%s</info>%s ', $question, $sep);
    }
}
