<?php

namespace IsekaiPHP\Events;

class UserCreated extends Event
{
    /**
     * Create a new event instance.
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }
}
