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

namespace Zikula\Bundle\GeneratorBundle\Manipulator;


/**
 * Changes the PHP code of a Kernel.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Manipulator
{
    /**
     * @var array
     */
    protected $tokens;

    /**
     * @var int
     */
    protected $line;

    /**
     * Sets the code to manipulate.
     */
    protected function setCode(array $tokens, int $line = 0): void
    {
        $this->tokens = $tokens;
        $this->line = $line;
    }

    /**
     * Gets the next token.
     *
     * @return mixed A PHP token
     */
    protected function next()
    {
        while ($token = array_shift($this->tokens)) {
            $this->line += substr_count($this->value($token), "\n");

            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return $token;
        }
    }

    /**
     * Peeks the next token.
     *
     * @return mixed A PHP token
     */
    protected function peek(int $nb = 1)
    {
        $i = 0;
        $tokens = $this->tokens;
        while ($token = array_shift($tokens)) {
            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            ++$i;
            if ($i === $nb) {
                return $token;
            }
        }
    }

    /**
     * Gets the value of a token.
     *
     * @param string|array $token
     */
    protected function value($token): string
    {
        return is_array($token) ? $token[1] : $token;
    }
}
