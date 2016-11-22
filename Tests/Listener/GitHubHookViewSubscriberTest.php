<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Swop\Bundle\GitHubWebHookBundle\Tests\Listener;

use Swop\Bundle\GitHubWebHookBundle\Listener\GitHubHookViewSubscriber;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Prophecy\Argument;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class GitHubHookViewSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testItShouldNotSerializeIfNotWebHook()
    {
        $request = new Request();

        $event = $this->prophesize('Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent');
        $event->getRequest()->shouldBeCalled()->willReturn($request);

        $event->setResponse(Argument::any())->shouldNotBeCalled();

        $subscriber = new GitHubHookViewSubscriber();
        $subscriber->serialize($event->reveal());
    }

    public function testItShouldSerializeWebHookResult()
    {
        $controllerResult = ['result' => ['everything' => 'is_ok']];
        $expectedResponse = new JsonResponse($controllerResult);

        $request = new Request();
        $request->attributes->set('_github_webhook', ['hook_config']);

        $event = $this->prophesize('Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent');
        $event->getRequest()->shouldBeCalled()->willReturn($request);
        $event->getControllerResult()->shouldBeCalled()->willReturn($controllerResult);

        $event->setResponse($expectedResponse)->shouldBeCalled();

        $subscriber = new GitHubHookViewSubscriber();
        $subscriber->serialize($event->reveal());
    }
}
