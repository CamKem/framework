<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Token;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SupportTokenTest extends TestCase
{
    public function testCountReturnsZeroForEmptyString(): void
    {
        $this->assertSame(0, Token::count(''));
    }

    public function testCountReturnsZeroForWhitespaceOnlyString(): void
    {
        $this->assertSame(0, Token::count(" \n\t  "));
    }

    public function testCountHandlesSingleWord(): void
    {
        $this->assertSame(1, Token::count('hello'));
    }

    public function testCountHandlesMultipleWords(): void
    {
        $this->assertSame(2, Token::count('hello world'));
    }

    public function testCountHandlesTabsAndNewlines(): void
    {
        $this->assertSame(3, Token::count("hello\tworld\nagain"));
    }

    public function testCountTreatsPunctuationAsSeparateTokens(): void
    {
        $this->assertSame(4, Token::count('hello, world!'));
    }

    public function testCountHandlesNumbersAndSymbolsDeterministically(): void
    {
        $this->assertGreaterThan(0, Token::count('abc 123 $5.99'));
    }

    public function testCountHandlesAccentedUtf8Text(): void
    {
        $this->assertSame(3, Token::count('café naïve résumé'));
    }

    public function testCountHandlesEmoji(): void
    {
        $this->assertGreaterThan(0, Token::count('Hello 👋 world 🌍'));
    }

    public function testCountHandlesCjkText(): void
    {
        $this->assertGreaterThan(0, Token::count('你好世界'));
    }

    public function testCountAndEstimateAreIdenticalInVersionOne(): void
    {
        $value = "Hello, world!\nThis is a test.";

        $this->assertSame(Token::count($value), Token::estimate($value));
    }

    public function testFitsReturnsTrueWhenUnderLimit(): void
    {
        $this->assertTrue(Token::fits('hello world', 2));
    }

    public function testFitsReturnsTrueAtExactLimit(): void
    {
        $this->assertTrue(Token::fits('hello world', 2));
    }

    public function testFitsReturnsFalseWhenOverLimit(): void
    {
        $this->assertFalse(Token::fits('hello world again', 2));
    }

    public function testFitsReturnsFalseForNegativeLimit(): void
    {
        $this->assertFalse(Token::fits('hello', -1));
    }

    public function testFitsReturnsTrueForEmptyStringAtZero(): void
    {
        $this->assertTrue(Token::fits('', 0));
    }

    public function testTruncateReturnsOriginalValueWhenAlreadyWithinBudget(): void
    {
        $this->assertSame('hello world', Token::truncate('hello world', 2));
    }

    public function testTruncateReturnsEmptyStringWhenBudgetIsZero(): void
    {
        $this->assertSame('', Token::truncate('hello world', 0));
    }

    public function testTruncateReturnsEmptyStringWhenBudgetIsNegative(): void
    {
        $this->assertSame('', Token::truncate('hello world', -1));
    }

    public function testTruncateRespectsSuffixBudget(): void
    {
        $result = Token::truncate('hello world again', 2, '...');

        $this->assertLessThanOrEqual(2, Token::estimate($result));
    }

    public function testTruncateReturnsEmptyStringWhenSuffixCannotFit(): void
    {
        $this->assertSame('', Token::truncate('hello world', 1, '...'));
    }

    public function testTruncateReturnsSuffixWhenOnlySuffixFits(): void
    {
        $this->assertSame('!', Token::truncate('hello world', 1, '!'));
    }

    public function testChunkThrowsForZeroBudget(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Token::chunk('hello world', 0);
    }

    public function testChunkThrowsForNegativeBudget(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Token::chunk('hello world', -1);
    }

    public function testChunkReturnsEmptyArrayForEmptyInput(): void
    {
        $this->assertSame([], Token::chunk('', 2));
    }

    public function testChunkReturnsSingleChunkWhenInputFits(): void
    {
        $this->assertSame(['hello world'], Token::chunk('hello world', 2));
    }

    public function testChunkSplitsContentIntoBudgetBoundChunks(): void
    {
        $chunks = Token::chunk('hello world again today', 2);

        $this->assertNotEmpty($chunks);

        foreach ($chunks as $chunk) {
            $this->assertLessThanOrEqual(2, Token::estimate($chunk));
        }
    }

    public function testChunkPreservesOrder(): void
    {
        $chunks = Token::chunk('one two three four', 2);

        $this->assertSame('one', strtok($chunks[0], ' '));
    }

    public function testChunkSupportsHardSplitting(): void
    {
        $chunks = Token::chunk('superlongunbrokenstringvalue', 1, false);

        $this->assertNotEmpty($chunks);

        foreach ($chunks as $chunk) {
            $this->assertLessThanOrEqual(1, Token::estimate($chunk));
        }
    }

    public function testChunkHandlesMarkdownLikeInput(): void
    {
        $value = "# Heading\n\nSome paragraph text with **bold** and `code`.";

        $chunks = Token::chunk($value, 5);

        $this->assertNotEmpty($chunks);
    }
}
