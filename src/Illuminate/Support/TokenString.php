<?php

declare(strict_types=1);

namespace Illuminate\Support;

final class TokenString
{
    public function __construct(protected string $value)
    {
    }

    /**
     * Count the number of tokens in the underlying value.
     */
    public function count(): int
    {
        return Token::count($this->value);
    }

    /**
     * Estimate the number of tokens in the underlying value.
     */
    public function estimate(): int
    {
        return Token::estimate($this->value);
    }

    /**
     * Determine if the underlying value fits within the provided token budget.
     */
    public function fits(int $maxTokens): bool
    {
        return Token::fits($this->value, $maxTokens);
    }

    /**
     * Truncate the underlying value to the provided token budget.
     */
    public function truncate(int $maxTokens, string $suffix = ''): static
    {
        return new static(Token::truncate($this->value, $maxTokens, $suffix));
    }

    /**
     * Chunk the underlying value into token-bounded pieces.
     */
    public function chunk(int $maxTokens, bool $preserveWords = true): array
    {
        return Token::chunk($this->value, $maxTokens, $preserveWords);
    }

    /**
     * Get the underlying string value.
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Get the underlying string value.
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
