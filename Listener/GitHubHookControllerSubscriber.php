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

use Swop\Bundle\GitHubWebHookBundle\Annotation\GitHubWebHook;
use Swop\Bundle\GitHubWebHookBundle\Exception\NotManagedGitHubEventException;
use Swop\GitHubWebHook\Event\GitHubEventFactoryInterface;
use Swop\GitHubWebHook\Security\SignatureValidatorInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Zend\Diactoros\Stream;

/**
 * This pre-controller subscriber will look at previously loaded GitHubWebHooks controller annotations
 * and will verify if the given GitHub web hook request could be handled by the controller.
 *
 * It will also verify request signature and will pre-built a GitHub event which could be injected in the controller
 * parameters if needed.
 *
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class GitHubHookControllerSubscriber implements EventSubscriberInterface
{
    /** @var HttpMessageFactoryInterface */
    private $messageFactory;
    /** @var SignatureValidatorInterface */
    private $signatureValidator;
    /** @var ContainerInterface */
    private $container;
    /** @var string */
    private $defaultSecret;
    /** @var GitHubEventFactoryInterface */
    private $gitHubEventFactory;

    /**
     * @param HttpMessageFactoryInterface $messageFactory
     * @param SignatureValidatorInterface $signatureValidator
     * @param GitHubEventFactoryInterface $gitHubEventFactory
     * @param ContainerInterface          $container
     * @param string                      $defaultSecret
     */
    public function __construct(
        HttpMessageFactoryInterface $messageFactory,
        SignatureValidatorInterface $signatureValidator,
        GitHubEventFactoryInterface $gitHubEventFactory,
        ContainerInterface $container,
        $defaultSecret
    ) {
        $this->messageFactory     = $messageFactory;
        $this->signatureValidator = $signatureValidator;
        $this->gitHubEventFactory = $gitHubEventFactory;
        $this->defaultSecret      = $defaultSecret;
        $this->container          = $container;
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => ['checkSecurity', 0],
        ];
    }

    /**
     * @param FilterControllerEvent $event
     *
     * @throws NotManagedGitHubEventException
     *
     * @api
     */
    public function checkSecurity(FilterControllerEvent $event)
    {
        if ($event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        $request = $event->getRequest();

        /*
         * The _github_webhook attribute is previously loaded by the SensioFrameworkExtraBundle Annotation reader.
         *
         * @see Sensio\Bundle\FrameworkExtraBundle\EventListener\ControllerListener
         * @see Swop\Bundle\GitHubWebHookBundle\Annotation\GitHubWebHook
         */
        if (!$configurations = $request->attributes->get('_github_webhook')) {
            // The controller is not a web hook
            return;
        }

        // Rewriting the php://input stream into a temp stream allow us to read the request body multiple times
        $bodyStream = new Stream('php://temp', 'wb+');
        $bodyStream->write(file_get_contents('php://input'));

        $psr7Request = $this->messageFactory->createRequest($request)->withBody($bodyStream);

        $eventType            = $request->headers->get('X-GitHub-Event');
        $mappedConfigurations = $this->mapConfigurations($configurations);

        if (!isset($mappedConfigurations[$eventType])) {
            throw new NotManagedGitHubEventException(
                $psr7Request,
                $eventType,
                array_keys($mappedConfigurations),
                $event->getController()
            );
        }

        $configuration = $mappedConfigurations[$eventType];

        if (null !== $secret = $configuration->getSecret()) {
            if (preg_match('/^%(.+)%$/', $secret, $matches)) {
                // If the secret has the %xxx% form, we'll fetch the parameter from the container
                $secretParameter = $matches[1];
                $secret          = $this->container->getParameter($secretParameter);
            }
        } else {
            $secret = $this->defaultSecret;
        }

        $this->signatureValidator->validate($psr7Request, $secret);

        // Registers the GitHub event into the request attributes in order to inject the event into the controller's
        // parameters if needed.
        $request->attributes->set('gitHubEvent', $this->gitHubEventFactory->buildFromRequest($psr7Request));
    }

    /**
     * @param array $configurations
     *
     * @return array
     */
    private function mapConfigurations(array $configurations)
    {
        return array_reduce(
            $configurations,
            function (array $mappedConfigurations, GitHubWebHook $configuration) {
                $mappedConfigurations[$configuration->getEventType()] = $configuration;

                return $mappedConfigurations;
            },
            []
        );
    }
}
