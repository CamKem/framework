<?php

declare(strict_types=1);

namespace Illuminate\Support;

use InvalidArgumentException;

final class Token
{
    /**
     * Create a new TokenString instance.
     */
    public static function of(string $value): TokenString
    {
        return new TokenString($value);
    }

    /**
     * Count the number of tokens in the given string.
     */
    public static function count(string $value): int
    {
        return static::estimate($value);
    }

    /**
     * Estimate the number of tokens in the given string.
     */
    public static function estimate(string $value): int
    {
        $normalized = static::normalize($value);

        if (trim($normalized) === '') {
            return 0;
        }

        return count(static::matches($normalized));
    }

    /**
     * Determine if the given value fits within the provided token budget.
     */
    public static function fits(string $value, int $maxTokens): bool
    {
        if ($maxTokens < 0) {
            return false;
        }

        return static::estimate($value) <= $maxTokens;
    }

    /**
     * Truncate the given value to the provided token budget.
     */
    public static function truncate(string $value, int $maxTokens, string $suffix = ''): string
    {
        if ($maxTokens <= 0) {
            return '';
        }

        if (static::fits($value, $maxTokens)) {
            return $value;
        }

        $normalized = static::normalize($value);

        $suffixTokens = static::estimate($suffix);

        if ($suffixTokens > $maxTokens) {
            return '';
        }

        if ($suffixTokens === $maxTokens) {
            return $suffix;
        }

        $tokens = static::matches($normalized);

        $tokenCount = 0;
        $cut = 0;

        foreach ($tokens as [$token, $offset]) {
            if ($tokenCount + 1 + $suffixTokens > $maxTokens) {
                break;
            }

            $tokenCount++;

            $cut = $offset + strlen($token);
        }

        $candidate = $cut > 0 ? static::substringByByteLength($normalized, $cut) : '';
        $result = $candidate.$suffix;

        if (static::estimate($result) > $maxTokens) {
            $result = static::forceFitByCharacters($candidate, $suffix, $maxTokens);
        }

        return $result;
    }

    /**
     * Split the given value into chunks that each fit the token budget.
     *
     * @throws InvalidArgumentException
     */
    public static function chunk(string $value, int $maxTokens, bool $preserveWords = true): array
    {
        if ($maxTokens <= 0) {
            throw new InvalidArgumentException('Token budget must be greater than zero.');
        }

        $normalized = static::normalize($value);

        if (trim($normalized) === '') {
            return [];
        }

        if (static::estimate($normalized) <= $maxTokens) {
            return [$normalized];
        }

        return $preserveWords
            ? static::chunkPreservingWords($normalized, $maxTokens)
            : static::chunkWithoutPreservingWords($normalized, $maxTokens);
    }

    /**
     * Normalize line endings to Unix style.
     */
    private static function normalize(string $value): string
    {
        return str_replace(["\r\n", "\r"], "\n", $value);
    }

    /**
     * Retrieve token matches for the given string.
     *
     * @return array<int, array{0: string, 1: int}>
     */
    private static function matches(string $value): array
    {
        preg_match_all('/[\\p{L}\\p{N}]+|[^\\s\\p{L}\\p{N}]/u', $value, $matches, PREG_OFFSET_CAPTURE);

        return $matches[0] ?? [];
    }

    /**
     * Force the candidate string to fit within the provided budget by character length.
     */
    private static function forceFitByCharacters(string $candidate, string $suffix, int $maxTokens): string
    {
        $length = strlen($candidate);

        for ($bytes = $length; $bytes >= 0; $bytes--) {
            $prefix = static::substringByByteLength($candidate, $bytes);
            $result = $prefix.$suffix;

            if (static::estimate($result) <= $maxTokens) {
                return $result;
            }
        }

        return '';
    }

    /**
     * Chunk the string while preserving token boundaries where possible.
     */
    private static function chunkPreservingWords(string $value, int $maxTokens): array
    {
        $tokens = static::matches($value);
        $chunks = [];
        $start = 0;
        $count = 0;
        $length = strlen($value);

        foreach ($tokens as [$token, $offset]) {
            if ($count + 1 > $maxTokens) {
                $chunks[] = static::substringSlice($value, $start, $offset - $start);
                $start = $offset;
                $count = 0;
            }

            $count++;
        }

        if ($start < $length) {
            $chunks[] = static::substringSlice($value, $start, $length - $start);
        }

        return $chunks;
    }

    /**
     * Chunk the string using hard splits when needed.
     */
    private static function chunkWithoutPreservingWords(string $value, int $maxTokens): array
    {
        $chunks = [];
        $length = strlen($value);
        $offset = 0;

        while ($offset < $length) {
            $nextLength = 1;
            $lastGood = 0;

            while ($offset + $nextLength <= $length) {
                $candidate = static::substringSlice($value, $offset, $nextLength);

                if (static::estimate($candidate) <= $maxTokens) {
                    $lastGood = $nextLength;
                    $nextLength++;

                    continue;
                }

                break;
            }

            if ($lastGood === 0) {
                $lastGood = 1;
            }

            $chunks[] = static::substringSlice($value, $offset, $lastGood);
            $offset += $lastGood;
        }

        return $chunks;
    }

    /**
     * Safely return a substring by byte length.
     */
    private static function substringByByteLength(string $value, int $length): string
    {
        return mb_strcut($value, 0, $length, 'UTF-8');
    }

    /**
     * Safely slice a substring from the given string.
     */
    private static function substringSlice(string $value, int $offset, int $length): string
    {
        return mb_strcut($value, $offset, $length, 'UTF-8');
    }
}
