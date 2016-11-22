<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Swop\Bundle\GitHubWebHookBundle\Exception;

use Psr\Http\Message\RequestInterface;
use Swop\GitHubWebHook\Exception\GitHubWebHookException;

/**
 * Thrown when the the given event type cannot be handled by the controller.
 *
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class NotManagedGitHubEventException extends GitHubWebHookException
{
    /** @var string */
    private $eventType;
    /** @var array */
    private $managedTypes;

    /**
     * @param RequestInterface $request
     * @param string           $eventType
     * @param array            $managedTypes
     * @param callable         $controller
     * @param \Exception       $previous
     */
    public function __construct(
        RequestInterface $request,
        $eventType,
        array $managedTypes,
        callable $controller,
        \Exception $previous = null
    ) {
        $this->eventType    = $eventType;
        $this->managedTypes = $managedTypes;

        if (is_object($controller)) {
            $controllerName = get_class($controller);
        } elseif (is_array($controller)) {
            $controllerName = get_class($controller[0]) . '::' . $controller[1] . '()';
        } elseif (is_string($controller)) {
            $controllerName = $controller;
        } else {
            $controllerName = '';
        }

        parent::__construct(
            $request,
            sprintf(
                'The GitHub event type "%s" is not managed in controller %s. Managed types: %s.',
                $eventType,
                $controllerName,
                join(', ', $managedTypes)
            ),
            $previous
        );
    }

    /**
     * @return array
     */
    public function getManagedTypes()
    {
        return $this->managedTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicMessage()
    {
        return 'The event type ' . $this->eventType . ' is not managed';
    }
}
