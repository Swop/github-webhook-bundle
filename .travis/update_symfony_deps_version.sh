#!/usr/bin/env bash

if [ "$SYMFONY_VERSION" != "" ]; then
    composer require --dev --no-update \
        symfony/http-kernel:$SYMFONY_VERSION \
        symfony/http-foundation:$SYMFONY_VERSION \
        symfony/dependency-injection:$SYMFONY_VERSION \
        symfony/config:$SYMFONY_VERSION \
        symfony/event-dispatcher:$SYMFONY_VERSION
fi
