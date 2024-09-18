<?php

namespace AlhajiAki\Sms\Senders;

use AlhajiAki\Sms\Exception\SenderException;
use AlhajiAki\Sms\Exception\SenderExceptionInterface;
use AlhajiAki\Sms\SentMessage;
use AlhajiAki\Sms\TextMessage;

/**
 * Uses several Senders using a round robin algorithm.
 */
class RoundRobinSender implements SenderInterface
{
    /**
     * @var \SplObjectStorage<SenderInterface, float>
     */
    private \SplObjectStorage $deadSenders;

    /**
     * @var array<int, SenderInterface>
     */
    private array $senders = [];

    private int $retryPeriod;

    private int $cursor = -1;

    /**
     * @param  SenderInterface[]  $senders
     */
    public function __construct(array $senders, int $retryPeriod = 60)
    {
        if (! $senders) {
            throw new SenderException(sprintf('"%s" must have at least one sender configured.', static::class));
        }

        $this->senders = $senders;
        $this->deadSenders = new \SplObjectStorage;
        $this->retryPeriod = $retryPeriod;
    }

    public function send(TextMessage $message): ?SentMessage
    {
        $exception = null;

        while ($sender = $this->getNextSender()) {
            try {
                return $sender->send($message);
            } catch (SenderExceptionInterface $e) {
                $exception ??= new SenderException('All senders failed.');
                $exception->appendDebug(sprintf("Sender \"%s\": %s\n", $sender::class, $e->getDebug()));
                $this->deadSenders[$sender] = microtime(true);
            }
        }

        throw $exception ?? new SenderException('No senders found.');
    }

    public function __toString(): string
    {
        return $this->getNameSymbol().'('.implode(' ', array_map('strval', $this->senders)).')';
    }

    /**
     * Rotates the sender list around and returns the first instance.
     */
    protected function getNextSender(): ?SenderInterface
    {
        if ($this->cursor === -1) {
            $this->cursor = $this->getInitialCursor();
        }

        $cursor = $this->cursor;
        while (true) {
            $sender = $this->senders[$cursor];

            if (! $this->isSenderDead($sender)) {
                break;
            }

            if ((microtime(true) - $this->deadSenders[$sender]) > $this->retryPeriod) {
                $this->deadSenders->detach($sender);

                break;
            }

            if ($this->cursor === $cursor = $this->moveCursor($cursor)) {
                return null;
            }
        }

        $this->cursor = $this->moveCursor($cursor);

        return $sender;
    }

    protected function isSenderDead(SenderInterface $sender): bool
    {
        return $this->deadSenders->contains($sender);
    }

    protected function getInitialCursor(): int
    {
        // the cursor initial value is randomized so that
        // when are not in a daemon, we are still rotating the senders
        return mt_rand(0, \count($this->senders) - 1);
    }

    protected function getNameSymbol(): string
    {
        return 'roundrobin';
    }

    private function moveCursor(int $cursor): int
    {
        return ++$cursor >= \count($this->senders) ? 0 : $cursor;
    }
}
