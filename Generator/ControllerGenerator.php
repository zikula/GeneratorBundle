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

use DOMDocument;
use RuntimeException;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Generates a Controller inside a bundle.
 *
 * @author Wouter J <wouter@wouterj.nl>
 */
class ControllerGenerator extends Generator
{
    public function generate(BundleInterface $bundle, string $controller, string $routeFormat, string $templateFormat, array $actions = []): void
    {
        $dir = $bundle->getPath();
        $controllerFile = $dir.'/Controller/'.$controller.'Controller.php';
        if (file_exists($controllerFile)) {
            throw new RuntimeException(sprintf('Controller "%s" already exists', $controller));
        }

        $parameters = [
            'namespace'  => $bundle->getNamespace(),
            'bundle'     => $bundle->getName(),
            'format'     => [
                'routing'    => $routeFormat,
                'templating' => $templateFormat,
            ],
            'controller' => $controller,
        ];

        foreach ($actions as $i => $action) {
            // get the actioname without the sufix Action (for the template logical name)
            $actions[$i]['basename'] = substr($action['name'], 0, -6);
            $params = $parameters;
            $params['action'] = $actions[$i];

            // create a template
            $template = $actions[$i]['template'];
            if ('default' === $template) {
                $template = $bundle->getName().':'.$controller.':'.substr($action['name'], 0, -6).'.html.'.$templateFormat;
            }

            if ('twig' === $templateFormat) {
                $this->renderFile('controller/Template.html.twig', $dir.'/Resources/views/'.$this->parseTemplatePath($template), $params);
            } else {
                $this->renderFile('controller/Template.html.php', $dir.'/Resources/views/'.$this->parseTemplatePath($template), $params);
            }

            $this->generateRouting($bundle, $controller, $actions[$i], $routeFormat);
        }

        $parameters['actions'] = $actions;

        $this->renderFile('controller/Controller.php', $controllerFile, $parameters);
        $this->renderFile('controller/ControllerTest.php', $dir.'/Tests/Controller/'.$controller.'ControllerTest.php', $parameters);
    }

    public function generateRouting(BundleInterface $bundle, string $controller, array $action, string $format): void
    {
        // annotation is generated in the templates
        if ('annotation' === $format) {
            return;
        }

        $content = '';
        $file = $bundle->getPath().'/Resources/config/routing.'.$format;
        if (file_exists($file)) {
            $content = file_get_contents($file);
        } elseif (!is_dir($dir = $bundle->getPath().'/Resources/config')) {
            if (!mkdir($dir) && !is_dir($dir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
            }
        }

        $controller = $bundle->getName().':'.$controller.':'.$action['basename'];
        $name = strtolower(preg_replace('/([A-Z])/', '_\\1', $action['basename']));

        if ('yml' === $format) {
            // yaml
            $content .= sprintf(
                "\n%s:\n    pattern: %s\n    defaults: { _controller: %s }\n",
                $name,
                $action['route'],
                $controller
            );
        } elseif ('xml' === $format) {
            // xml
            if (!isset($content)) {
                // new file
                $content = <<<EOT
<?xml version="1.0" encoding="UTF-8" ?>
<routes xmlns="http://symfony.com/schema/routing"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://symfony.com/schema/routing http://symfony.com/schema/routing/routing-1.0.xsd">
</routes>
EOT;
            }

            $sxe = simplexml_load_string($content);

            $route = $sxe->addChild('route');
            $route->addAttribute('id', $name);
            $route->addAttribute('pattern', $action['route']);

            $default = $route->addChild('default', $controller);
            $default->addAttribute('key', '_controller');

            $dom = new DOMDocument('1.0');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($sxe->asXML());
            $content = $dom->saveXML();
        } elseif ('php' === $format) {
            // php
            if (isset($content)) {
                // edit current file
                $pointer = strpos($content, 'return');
                if (false === $pointer || !preg_match('/(\$[^ ]*).*?new RouteCollection\(\)/', $content, $collection)) {
                    throw new \RunTimeException('Routing.php file is not correct, please initialize RouteCollection.');
                }

                $content = substr($content, 0, $pointer);
                $content .= sprintf("%s->add('%s', new Route('%s', [", $collection[1], $name, $action['route']);
                $content .= sprintf("\n    '_controller' => '%s',", $controller);
                $content .= "\n]));\n\nreturn ".$collection[1];
            } else {
                // new file
                $content = <<<EOT
<?php
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

\$collection = new RouteCollection();
EOT;
                $content .= sprintf("\n\$collection->add('%s', new Route('%s', [", $name, $action['route']);
                $content .= sprintf("\n    '_controller' => '%s',", $controller);
                $content .= "\n]));\n\nreturn \$collection";
            }
        }

        $flink = fopen($file, 'wb');
        if ($flink) {
            $write = fwrite($flink, $content);

            if ($write) {
                fclose($flink);
            } else {
                throw new RuntimeException(sprintf('We cannot write into file "%s", has that file the correct access level?', $file));
            }
        } else {
            throw new RuntimeException(sprintf('Problems with generating file "%s", did you gave write access to that directory?', $file));
        }
    }

    protected function parseTemplatePath(string $template): string
    {
        $data = $this->parseLogicalTemplateName($template);

        return $data['controller'].'/'.$data['template'];
    }

    protected function parseLogicalTemplateName(string $logicalname, string $part = ''): array
    {
        $data = [];

        list($data['bundle'], $data['controller'], $data['template']) = explode(':', $logicalname);

        return ($part ? $data[$part] : $data);
    }
}
