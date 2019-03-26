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
use Symfony\Component\DependencyInjection\Container;
use Zikula\Bundle\CoreBundle\HttpKernel\ZikulaKernel;

/**
 * Generates a bundle.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class BundleGenerator extends Generator
{
    public function generate(string $namespace, string $bundle, string $dir, string $format, string $license = 'MIT'): void
    {
        $dir .= '/'. str_replace('\\', '/', $namespace);
        if (file_exists($dir)) {
            if (!is_dir($dir)) {
                throw new RuntimeException(sprintf('Unable to generate the module as the target directory "%s" exists but is a file.', realpath($dir)));
            }
            $files = scandir($dir, SCANDIR_SORT_NONE);
            if ($files !== ['.', '..']) {
                throw new RuntimeException(sprintf('Unable to generate the module as the target directory "%s" is not empty.', realpath($dir)));
            }
            if (!is_writable($dir)) {
                throw new RuntimeException(sprintf('Unable to generate the module as the target directory "%s" is not writable.', realpath($dir)));
            }
        }

        $basename = substr($bundle, 0, -6);
        $namespaceParts = explode('\\', $namespace);
        $vendorName = $namespaceParts[0];
        $bundleName = substr($bundle, strlen($vendorName), strlen($bundle));
        $parameters = [
            'vendor'           => $vendorName,
            'namespace'        => $namespace,
            'bundle'           => $bundle,
            'bundle_name'      => $bundleName,
            'namespace_double' => str_replace('\\', '\\\\', $namespace),
            'bundle_double'    => str_replace('\\', '\\\\', $bundle),
            'format'           => $format,
            'bundle_basename'  => $basename,
            'extension_alias'  => Container::underscore($basename),
            'bundle_underscore'=> Container::underscore($bundle),
            'zikulaVersion'    => ZikulaKernel::VERSION,
            'php_min'          => ZikulaKernel::PHP_MINIMUM_VERSION
        ];

        $this->renderFile('bundle/.gitignore.twig', $dir.'/.gitignore', $parameters);
        $this->renderFile('bundle/composer.json.twig', $dir.'/composer.json', $parameters);
        $this->renderFile('bundle/Module.php.twig', $dir.'/'.$bundle.'.php', $parameters);
        $this->renderFile('bundle/Installer.php.twig', $dir.'/'.$bundleName.'Installer.php', $parameters);
        $this->renderFile('bundle/phpunit.xml.dist.twig', $dir.'/phpunit.xml.dist', $parameters);
        $this->renderFile('bundle/README.md.twig', $dir.'/README.md', $parameters);
        $this->renderFile('bundle/LICENSE-'.$license.'.twig', $dir.'/LICENSE.md', $parameters);
        $this->renderFile('bundle/Extension.php.twig', $dir.'/DependencyInjection/'.$basename.'Extension.php', $parameters);
        $this->renderFile('bundle/LinkContainer.php.twig', $dir.'/Container/LinkContainer.php', $parameters);
        $this->renderFile('bundle/Block.php.twig', $dir.'/Block/'.$bundleName.'Block.php', $parameters);
//        $this->renderFile('bundle/Configuration.php.twig', $dir.'/DependencyInjection/Configuration.php', $parameters);
        $this->renderFile('bundle/DefaultController.php.twig', $dir.'/Controller/DefaultController.php', $parameters);
        $this->renderFile('bundle/ConfigController.php.twig', $dir.'/Controller/ConfigController.php', $parameters);
        $this->renderFile('bundle/DefaultControllerTest.php.twig', $dir.'/Tests/Controller/DefaultControllerTest.php', $parameters);
        $this->renderFile('bundle/index.html.twig.twig', $dir.'/Resources/views/Default/index.html.twig', $parameters);
        $this->renderFile('bundle/settings.html.twig.twig', $dir.'/Resources/views/Config/settings.html.twig', $parameters);
        $this->renderFile('bundle/routing.yml.twig', $dir.'/Resources/config/routing.yml', $parameters);
        $this->filesystem->mkdir($dir.'/Resources/doc');
        $this->filesystem->touch($dir.'/Resources/doc/index.md');
        $this->filesystem->mkdir($dir.'/Resources/public/css');
        $this->filesystem->touch($dir.'/Resources/public/css/style.css');
        $this->filesystem->mkdir($dir.'/Resources/public/images');
        $this->filesystem->copy(__DIR__ . '/../Resources/skeleton/bundle/admin.png', $dir . '/Resources/public/images/admin.png');
        $this->filesystem->mkdir($dir.'/Resources/public/js');
        $this->filesystem->touch($dir.'/Resources/public/js/.gitkeep');

        $this->renderFile('bundle/services.yml.twig', $dir.'/Resources/config/services.yml', $parameters);
    }
}
