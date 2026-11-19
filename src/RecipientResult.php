<?php

namespace AlhajiAki\Sms;

/**
 * What the provider said about one recipient of a message: accepted, with the provider's id for it when it gives one,
 * or refused, with the reason.
 */
final class RecipientResult
{
    public function __construct(
        public readonly string $phone,
        public readonly bool $accepted,
        public readonly ?string $providerMessageId = null,
        public readonly ?string $reason = null,
    ) {}

    public static function accepted(string $phone, ?string $providerMessageId = null): self
    {
        return new self($phone, true, $providerMessageId);
    }

    public static function refused(string $phone, string $reason): self
    {
        return new self($phone, false, reason: $reason);
    }
}
