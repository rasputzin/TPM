<?php

declare(strict_types=1);

namespace TpmPsych\Validation\Expression;

final class ExpressionToken
{
    public const NUMBER = 'number';
    public const IDENT = 'ident';
    public const PLUS = 'plus';
    public const MINUS = 'minus';
    public const STAR = 'star';
    public const SLASH = 'slash';
    public const LPAREN = 'lparen';
    public const RPAREN = 'rparen';
    public const EOF = 'eof';

    public function __construct(
        public readonly string $type,
        public readonly string $value,
    ) {
    }
}
