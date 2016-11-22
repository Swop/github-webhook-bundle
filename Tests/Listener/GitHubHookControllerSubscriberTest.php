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

use Swop\Bundle\GitHubWebHookBundle\Annotation\GitHubWebHook;
use Swop\Bundle\GitHubWebHookBundle\Listener\GitHubHookControllerSubscriber;
use Swop\GitHubWebHook\Event\GitHubEvent;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Zend\Diactoros\ServerRequest;
use Prophecy\Argument;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class GitHubHookControllerSubscriberTest extends \PHPUnit_Framework_TestCase
{
    private $messageFactory;
    private $signatureValidator;
    private $eventFactory;
    private $container;
    private $subscriber;

    public function setUp()
    {
        $this->messageFactory     = $this->prophesize('Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface');
        $this->signatureValidator = $this->prophesize('Swop\GitHubWebHook\Security\SignatureValidatorInterface');
        $this->eventFactory       = $this->prophesize('Swop\GitHubWebHook\Event\GitHubEventFactoryInterface');
        $this->container          = $this->prophesize('Symfony\Component\DependencyInjection\ContainerInterface');

        $this->subscriber = new GitHubHookControllerSubscriber(
            $this->messageFactory->reveal(),
            $this->signatureValidator->reveal(),
            $this->eventFactory->reveal(),
            $this->container->reveal(),
            'default_secret'
        );
    }

    public function testItShouldReturnEarlyIfNotMasterRequest()
    {
        $event = $this->prophesize('Symfony\Component\HttpKernel\Event\FilterControllerEvent');
        $event->isMasterRequest()->shouldBeCalled()->willReturn(false);

        $this->subscriber->checkSecurity($event->reveal());
    }

    public function testItShouldReturnEarlyIfNotMasterRequestIf()
    {
        $request = new Request();

        $event = $this->prophesize('Symfony\Component\HttpKernel\Event\FilterControllerEvent');
        $event->isMasterRequest()->shouldBeCalled()->willReturn(true);
        $event->getRequest()->shouldBeCalled()->willReturn($request);

        $this->subscriber->checkSecurity($event->reveal());
    }

    /**
     * @expectedException \Swop\Bundle\GitHubWebHookBundle\Exception\NotManagedGitHubEventException
     * @expectedExceptionMessage The GitHub event type "unknown_type" is not managed in controller Swop\Bundle\GitHubWebHookBundle\Tests\Listener\GitHubHookControllerSubscriberTest::testNotHandledEventTypesShouldFail(). Managed types: handled_type.
     */
    public function testNotHandledEventTypesShouldFail()
    {
        $request = new Request();
        $request->attributes->set('_github_webhook', [new GitHubWebHook(['eventType' => 'handled_type'])]);
        $request->headers->set('X-GitHub-Event', 'unknown_type');

        $event = $this->prophesize('Symfony\Component\HttpKernel\Event\FilterControllerEvent');
        $event->isMasterRequest()->shouldBeCalled()->willReturn(true);
        $event->getRequest()->shouldBeCalled()->willReturn($request);
        $event->getController()->shouldBeCalled()->willReturn([$this, 'testNotHandledEventTypesShouldFail']);

        $psrRequest = new ServerRequest();
        $this->messageFactory->createRequest($request)->shouldBeCalled()->willReturn($psrRequest);

        $this->subscriber->checkSecurity($event->reveal());
    }

    /**
     * @dataProvider subscriberDataProvider
     * @param $hookConfig
     */
    public function testSubscriber($hookConfig, $expectedParameterName, $expectedSecret)
    {
        $request = new Request();
        $requestAttributes = ['_github_webhook' => [new GitHubWebHook($hookConfig)]];
        foreach ($requestAttributes as $key => $value) {
            $request->attributes->set($key, $value);
        }

        $request->headers->set('X-GitHub-Event', 'handled_type');

        $event = $this->prophesize('Symfony\Component\HttpKernel\Event\FilterControllerEvent');
        $event->isMasterRequest()->shouldBeCalled()->willReturn(true);
        $event->getRequest()->shouldBeCalled()->willReturn($request);
        $event->getController()->shouldNotBeCalled();

        $initialPsrRequest = $this->prophesize('Psr\Http\Message\ServerRequestInterface');
        $initialPsrRequest->withBody(Argument::any())->shouldBeCalled()->willReturn($initialPsrRequest);

        $this->messageFactory->createRequest($request)->shouldBeCalled()->willReturn($initialPsrRequest);

        if ($expectedParameterName) {
            $this->container->getParameter($expectedParameterName)->shouldBeCalled()->willReturn($expectedSecret);
        }

        $this->signatureValidator->validate($initialPsrRequest, $expectedSecret)->shouldBeCalled();
        $gitHubEvent = new GitHubEvent('handled_type', []);
        $this->eventFactory->buildFromRequest($initialPsrRequest)->shouldBeCalled()->willReturn($gitHubEvent);

        $this->subscriber->checkSecurity($event->reveal());

        $this->assertEquals(
            new ParameterBag(array_merge($requestAttributes, ['gitHubEvent' => $gitHubEvent])),
            $request->attributes
        );
    }

    public function subscriberDataProvider()
    {
        $data = [];

        $data[] = [
            ['eventType' => 'handled_type', 'secret' => 'my_secret'],
            null,
            'my_secret'
        ];
        $data[] = [
            ['eventType' => 'handled_type', 'secret' => '%secret_parameter%'],
            'secret_parameter',
            'secret_parameter_value'
        ];
        $data[] = [
            ['eventType' => 'handled_type'],
            null,
            'default_secret'
        ];

        return $data;
    }
}
