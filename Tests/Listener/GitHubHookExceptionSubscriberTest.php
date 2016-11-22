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

use Swop\Bundle\GitHubWebHookBundle\Listener\GitHubHookExceptionSubscriber;
use Swop\Bundle\GitHubWebHookBundle\Tests\Listener\Fixtures\DummyException;
use Prophecy\Argument;
use Swop\Bundle\GitHubWebHookBundle\Tests\Listener\Fixtures\DummyInvalidRequestSignatureException;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class GitHubHookExceptionSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testItShouldDoNothingIfNotGitHubWebHookException()
    {
        $subscriber = new GitHubHookExceptionSubscriber(true, null);

        $event = $this->prophesize('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent');

        $subscriber->onKernelException($event->reveal());
    }

    public function testItShouldLogException()
    {
        $exception = new DummyException('Exception message', 'Public message');

        $logger = $this->prophesize('Psr\Log\LoggerInterface');
        $logger->error(
            sprintf(
                'Uncaught PHP Exception %s: "%s" at %s line %s',
                'Swop\Bundle\GitHubWebHookBundle\Tests\Listener\Fixtures\DummyException',
                'Exception message',
                $exception->getFile(),
                $exception->getLine()
            ),
            ['exception' => $exception]
        )->shouldBeCalled();

        $subscriber = new GitHubHookExceptionSubscriber(true, $logger->reveal());

        $event = $this->prophesize('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent');
        $event->getException()->shouldBeCalled()->willReturn($exception);
        $event->setResponse(Argument::Any())->willReturn();
        $event->stopPropagation()->willReturn();

        $subscriber->onKernelException($event->reveal());

        // Without Logger
        $subscriber = new GitHubHookExceptionSubscriber(true, null);
        $subscriber->onKernelException($event->reveal());
    }

    /**
     * @dataProvider responseDataProvider
     *
     * @param $exception
     * @param $debug
     * @param $expectedResponseCode
     * @param $expectedResponseData
     */
    public function testItShouldAssignedProperResponse($exception, $debug, $expectedResponseCode, $expectedResponseData)
    {
        $subscriber = new GitHubHookExceptionSubscriber($debug, null);

        $event = $this->prophesize('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent');
        $event->getException()->shouldBeCalled()->willReturn($exception);
        $event->stopPropagation()->shouldBeCalled()->willReturn();

        $expectedResponse = new JsonResponse($expectedResponseData, $expectedResponseCode);
        $event->setResponse($expectedResponse)->willReturn();

        $subscriber->onKernelException($event->reveal());
    }

    public function responseDataProvider()
    {
        $exception = new DummyException(
            'Exception message',
            'Public message'
        );
        $invalidRequestSignatureException = new DummyInvalidRequestSignatureException(
            'Invalid signature',
            'Public: Invalid signature'
        );

        return [
            [$exception, false, 400, ['error' => 'Public message']],
            [$exception, true, 400, ['error' => 'Exception message', 'trace' => $exception->getTrace()]],
            [$invalidRequestSignatureException, false, 403, ['error' => 'Public: Invalid signature']],
        ];
    }
}
