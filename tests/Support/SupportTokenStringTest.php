<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Token;
use Illuminate\Support\TokenString;
use PHPUnit\Framework\TestCase;

class SupportTokenStringTest extends TestCase
{
    public function testOfReturnsTokenStringInstance(): void
    {
        $this->assertInstanceOf(TokenString::class, Token::of('hello world'));
    }

    public function testFluentCountMatchesStaticCount(): void
    {
        $value = 'hello world';

        $this->assertSame(Token::count($value), Token::of($value)->count());
    }

    public function testFluentEstimateMatchesStaticEstimate(): void
    {
        $value = 'hello world';

        $this->assertSame(Token::estimate($value), Token::of($value)->estimate());
    }

    public function testFluentFitsMatchesStaticFits(): void
    {
        $value = 'hello world';

        $this->assertSame(Token::fits($value, 2), Token::of($value)->fits(2));
    }

    public function testFluentTruncateReturnsNewInstance(): void
    {
        $original = Token::of('hello world again');
        $truncated = $original->truncate(2);

        $this->assertNotSame($original, $truncated);
        $this->assertSame('hello world again', $original->value());
    }

    public function testFluentValueReturnsUnderlyingString(): void
    {
        $this->assertSame('hello world', Token::of('hello world')->value());
    }

    public function testFluentToStringReturnsUnderlyingValue(): void
    {
        $this->assertSame('hello world', (string) Token::of('hello world'));
    }

    public function testFluentChunkMatchesStaticChunk(): void
    {
        $value = 'hello world again';

        $this->assertSame(
            Token::chunk($value, 2),
            Token::of($value)->chunk(2)
        );
    }

    public function testFluentTruncatePreservesOriginalInstance(): void
    {
        $value = Token::of('json: {"key":"value"}');

        $truncated = $value->truncate(3, '...');

        $this->assertSame('json: {"key":"value"}', $value->value());
        $this->assertNotSame($value, $truncated);
    }
}
