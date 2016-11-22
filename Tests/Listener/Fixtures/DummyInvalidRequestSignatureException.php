<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Swop\Bundle\GitHubWebHookBundle\Tests\Listener\Fixtures;

use Swop\GitHubWebHook\Exception\InvalidGitHubRequestSignatureException;
use Zend\Diactoros\ServerRequest;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class DummyInvalidRequestSignatureException extends InvalidGitHubRequestSignatureException
{
    private $publicMessage;

    public function __construct($message, $publicMessage)
    {
        $this->publicMessage = $publicMessage;

        parent::__construct(new ServerRequest(), $message);
    }

    public function getPublicMessage()
    {
        return $this->publicMessage;
    }
}
