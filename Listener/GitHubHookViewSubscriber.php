<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swop\Bundle\GitHubWebHookBundle\Listener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * This view subscriber will transform web hook controllers (and these ones only) results into a JSON response.
 *
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class GitHubHookViewSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['serialize', 0],
        ];
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     *
     * @api
     */
    public function serialize(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();

        if (null === $request->attributes->get('_github_webhook')) {
            return;
        }

        $result = $event->getControllerResult();

        $event->setResponse(new JsonResponse($result));
    }
}
