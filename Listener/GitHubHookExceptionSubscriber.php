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

use Psr\Log\LoggerInterface;
use Swop\GitHubWebHook\Exception\GitHubWebHookException;
use Swop\GitHubWebHook\Exception\InvalidGitHubRequestSignatureException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * This exception subscriber will transform GitHubWebHookException exceptions into a JSON response.
 * The message verbosity will depends on the debug parameter.
 *
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class GitHubHookExceptionSubscriber implements EventSubscriberInterface
{
    /** @var bool */
    private $debug;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param bool            $debug
     * @param LoggerInterface $logger
     */
    public function __construct($debug, LoggerInterface $logger = null)
    {
        $this->debug  = $debug;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 1000],
        ];
    }

    /**
     * @param GetResponseForExceptionEvent $event
     *
     * @api
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if (!$exception instanceof GitHubWebHookException) {
            return;
        }

        $this->logException(
            $exception,
            sprintf(
                'Uncaught PHP Exception %s: "%s" at %s line %s',
                get_class($exception),
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            )
        );

        if ($this->debug) {
            $responseData = [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTrace()
            ];
        } else {
            $responseData = [
                'error' => $exception->getPublicMessage(),
            ];
        }

        $statusCode = 400;
        if ($exception instanceof InvalidGitHubRequestSignatureException) {
            $statusCode = 403;
        }

        $event->setResponse(new JsonResponse($responseData, $statusCode));
        $event->stopPropagation();
    }

    /**
     * @param \Exception $exception The \Exception instance
     * @param string     $message   The error message to log
     */
    protected function logException(\Exception $exception, $message)
    {
        if (null !== $this->logger) {
            $this->logger->error($message, array('exception' => $exception));
        }
    }
}
