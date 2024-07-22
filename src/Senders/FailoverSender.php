<?php

namespace AlhajiAki\Sms\Senders;

/**
 * Uses several Senders using a failover algorithm.
 */
class FailoverSender extends RoundRobinSender
{
    private ?SenderInterface $currentSender = null;

    protected function getNextSender(): ?SenderInterface
    {
        if ($this->currentSender === null || $this->isSenderDead($this->currentSender)) {
            $this->currentSender = parent::getNextSender();
        }

        return $this->currentSender;
    }

    protected function getInitialCursor(): int
    {
        return 0;
    }

    protected function getNameSymbol(): string
    {
        return 'failover';
    }
}
