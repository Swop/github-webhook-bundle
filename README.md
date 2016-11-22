Github WebHook Bundle
==================

[![Build
Status](https://secure.travis-ci.org/Swop/github-webhook-bundle.png?branch=master)](http://travis-ci.org/Swop/github-webhook-bundle)

This bundle aiming to reduce complexity when creating GitHub web hooks apps.

Installation
------------

The recommended way to install this bundle is through [Composer](https://getcomposer.org/):

```
composer require "swop/github-webhook-bundle"
```

Then, register the bundle into your `AppKernel` class. Note that this bundle rely on the `SensioFrameworkExtraBundle` bundle in order to work properly.

```php
<?php

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            // ...
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new Swop\Bundle\GitHubWebHookBundle\GitHubWebHookBundle()
        ];

        // ...
    }
}
```

Usage
------------

Simply use the `GitHubWebHook` annotation in your controllers to mark them as GitHub web hook controllers

```php
<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swop\GitHubWebHook\Event\GitHubEvent;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Swop\Bundle\GitHubWebHookBundle\Annotation\GitHubWebHook;

class DefaultController extends Controller
{
    /**
     * @Route("/webhook", name="webhook")
     *
     * @GitHubWebHook(eventType="push")
     * @GitHubWebHook(eventType="pull_request")
     */
    public function indexAction(GitHubEvent $gitHubEvent)
    {
        $payload = $gitHubEvent->getPayload(); // Request payload (array)
        $eventType = $gitHubEvent->getType(); // "pull" or "pull_request"

        // Do something depending on the payload & the event type...
        
        return ['status' => 'success'];
    }
}
````

By declaring that your controller is a GitHub web hook, the bundle will make the following actions for you:

- It will check if the incoming web hook event **is supported** by your controller.
If it's not the case, a `400` JSON response will be automatically returned.

- It will verify that the incoming request **has a correct signature**, using the web hook specific secret *(see "Annotation reference")* or the default one *(see "Bundle configuration reference")*.
If the request signature is invalid, a `403` JSON response will be automatically returned.

- It will build a `GitHubEvent` object which will be injected in the controller parameters.

- It will manage the serialization of the data you returned in your controller, and thus will send back a `200` JSON response to GitHub.

Configuration
------------

### Bundle configuration reference
````yaml
github_webhook:
    default_secret: my_secret
````

- `default_secret`: Default secret to use on every hooks when validating the incoming request signature.

### Annotation reference

Simple event type handling (will respond only to the `push` event): 
````php
<?php
/**
 * @GitHubWebHook(eventType="push")
 */
````

Multiple event types handling (will respond to the `push` and `pull_request` event): 
````php
<?php
/**
 * @GitHubWebHook(eventType="push")
 * @GitHubWebHook(eventType="pull_request")
 */
````

Configure a dedicated secret to use for signature validation: 
````php
<?php
/**
 * @GitHubWebHook(eventType="push", secret="push_secret")
 * @GitHubWebHook(eventType="pull_request", secret="pr_secret")
 */
````

You also can rely on a container parameter to configure the secret to use: 
````php
<?php
/**
 * @GitHubWebHook(eventType="push", secret="%hook.push.secret%")
 */
````

Contributing
------------

See [CONTRIBUTING](https://github.com/Swop/github-webhook-bundle/blob/master/CONTRIBUTING.md) file.

Original Credits
------------

* [Sylvain MAUDUIT](https://github.com/Swop) ([@Swop](https://twitter.com/Swop)) as main author.


License
------------

This library is released under the MIT license. See the complete license in the bundled [LICENSE](https://github.com/Swop/github-webhook-bundle/blob/master/LICENSE) file.
