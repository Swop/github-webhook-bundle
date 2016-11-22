<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Swop\Bundle\GitHubWebHookBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class GitHubWebHookExtension extends ConfigurableExtension
{
    /**
     * {@inheritdoc}
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('listener.xml');

        $controllerListenerDefinition = $container->getDefinition('github_webhook.controller_listener');

        $controllerListenerDefinition->replaceArgument(4, $mergedConfig['default_secret']);
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return 'github_webhook';
    }
}
