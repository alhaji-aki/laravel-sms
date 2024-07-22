<?php

namespace AlhajiAki\Sms\Contracts;

interface Factory
{
    /**
     * Get a sender instance by name.
     */
    public function sender(?string $name = null): Sender;
}
