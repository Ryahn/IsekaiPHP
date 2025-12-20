<?php

namespace IsekaiPHP\Events\Listeners;

use IsekaiPHP\Events\Event;
use IsekaiPHP\Events\UserCreated;

class SendWelcomeEmail
{
    /**
     * Handle the event.
     */
    public function handle(UserCreated $event, string $eventName): void
    {
        //
    }
}
