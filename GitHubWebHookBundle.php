<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Swop\Bundle\GitHubWebHookBundle;

use Swop\Bundle\GitHubWebHookBundle\DependencyInjection\GitHubWebHookExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class GitHubWebHookBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new GitHubWebHookExtension();
    }
}
