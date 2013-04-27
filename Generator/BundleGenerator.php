<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\Bundle\GeneratorBundle\Generator;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\Container;

/**
 * Generates a bundle.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class BundleGenerator extends Generator
{
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function generate($namespace, $bundle, $dir, $format, $license = 'MIT')
    {
        $dir .= '/'.strtr($namespace, '\\', '/');
        if (file_exists($dir)) {
            if (!is_dir($dir)) {
                throw new \RuntimeException(sprintf('Unable to generate the module as the target directory "%s" exists but is a file.', realpath($dir)));
            }
            $files = scandir($dir);
            if ($files != array('.', '..')) {
                throw new \RuntimeException(sprintf('Unable to generate the module as the target directory "%s" is not empty.', realpath($dir)));
            }
            if (!is_writable($dir)) {
                throw new \RuntimeException(sprintf('Unable to generate the module as the target directory "%s" is not writable.', realpath($dir)));
            }
        }

        $basename = substr($bundle, 0, -6);
        $namespaceParts = explode('\\', $namespace);
        $vendorName = $namespaceParts[0];
        $bundleName = substr($bundle, strlen($vendorName), strlen($bundle));
        $parameters = array(
            'vendor'           => $vendorName,
            'namespace'        => $namespace,
            'bundle'           => $bundle,
            'bundle_name'      => $bundleName,
            'namespace_double' => str_replace('\\', '\\\\', $namespace),
            'bundle_double'    => str_replace('\\', '\\\\', $bundle),
            'format'           => $format,
            'bundle_basename'  => $basename,
            'extension_alias'  => Container::underscore($basename),
        );

        $this->filesystem->copy('bundle/.gitignore', $dir.'/.gitignore');
        $this->filesystem->copy('bundle/.travis.yml', $dir.'/.travis.yml');
        $this->renderFile('bundle/composer.json.twig', $dir.'/composer.json', $parameters);
        $this->renderFile('bundle/Module.php.twig', $dir.'/'.$bundle.'.php', $parameters);
        $this->renderFile('bundle/Version.php.twig', $dir.'/'.$bundleName.'Version.php', $parameters);
        $this->renderFile('bundle/Installer.php.twig', $dir.'/'.$bundleName.'Installer.php', $parameters);
        $this->renderFile('bundle/phpunit.xml.dist.twig', $dir.'/phpunit.xml.dist', $parameters);
        $this->renderFile('bundle/README.md.twig', $dir.'/README.md', $parameters);
        $this->renderFile('bundle/LICENSE-'.$license.'.twig', $dir.'/LICENSE.md', $parameters);
        $this->filesystem->copy('bundle/gettext.pot', $dir.'/Resources/locale/'.strtolower($module).'.pot');
//        $this->renderFile('bundle/Extension.php.twig', $dir.'/DependencyInjection/'.$basename.'Extension.php', $parameters);
//        $this->renderFile('bundle/Configuration.php.twig', $dir.'/DependencyInjection/Configuration.php', $parameters);
        $this->renderFile('bundle/DefaultController.php.twig', $dir.'/Controller/DefaultController.php', $parameters);
        $this->renderFile('bundle/DefaultControllerTest.php.twig', $dir.'/Tests/Controller/DefaultControllerTest.php', $parameters);
        $this->renderFile('bundle/index.html.twig.twig', $dir.'/Resources/views/Default/index.html.twig', $parameters);
        $this->filesystem->mkdir($dir.'/Resources/doc');
        $this->filesystem->touch($dir.'/Resources/doc/index.rst');
        $this->filesystem->mkdir($dir.'/Resources/public/css');
        $this->filesystem->mkdir($dir.'/Resources/public/images');
        $this->filesystem->mkdir($dir.'/Resources/public/js');

//        if ('xml' === $format || 'annotation' === $format) {
//            $this->renderFile('bundle/services.xml.twig', $dir.'/Resources/config/services.xml', $parameters);
//        } else {
//            $this->renderFile('bundle/services.'.$format.'.twig', $dir.'/Resources/config/services.'.$format, $parameters);
//        }
    }
}
