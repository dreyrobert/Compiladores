<?php

enum TokenType: int {
    case TOKEN_VAR = 0;
    case TOKEN_NUMBER = 1;
    case TOKEN_ATRIB = 2;
    case TOKEN_EQ = 3;
    case TOKEN_NEQ = 4;
    case TOKEN_GT = 5;
    case TOKEN_LT = 6;
    case TOKEN_GTE = 7;
    case TOKEN_LTE = 8;
    case TOKEN_LPAREN = 9;
    case TOKEN_RPAREN = 10;
    case TOKEN_TRUE = 11;
    case TOKEN_FALSE = 12;
    case TOKEN_IF = 13;
    case TOKEN_DO = 14;
    case TOKEN_ELSE = 15;
    case TOKEN_UNKNOWN = 16;
    case END_OF_SENTENCE = 17;
}

class Token {
    public TokenType $type;
    public string $value;
    public int $line;
    public int $position;
    public string $tokenName;

    public function __construct(TokenType $type, string $value, int $line, int $position, string $tokenName) {
        $this->type = $type;
        $this->value = $value;
        $this->line = $line;
        $this->position = $position;
        $this->tokenName = $tokenName;
    }
}

function getName(TokenType $type): string {
    return match ($type) {
        TokenType::TOKEN_VAR => "TOKEN_VAR",
        TokenType::TOKEN_NUMBER => "TOKEN_NUMBER",
        TokenType::TOKEN_ATRIB => "TOKEN_ATRIB",
        TokenType::TOKEN_EQ => "TOKEN_EQ",
        TokenType::TOKEN_NEQ => "TOKEN_NEQ",
        TokenType::TOKEN_GT => "TOKEN_GT",
        TokenType::TOKEN_LT => "TOKEN_LT",
        TokenType::TOKEN_GTE => "TOKEN_GTE",
        TokenType::TOKEN_LTE => "TOKEN_LTE",
        TokenType::TOKEN_LPAREN => "TOKEN_LPAREN",
        TokenType::TOKEN_RPAREN => "TOKEN_RPAREN",
        TokenType::TOKEN_TRUE => "TOKEN_TRUE",
        TokenType::TOKEN_FALSE => "TOKEN_FALSE",
        TokenType::TOKEN_IF => "TOKEN_IF",
        TokenType::TOKEN_DO => "TOKEN_DO",
        TokenType::TOKEN_ELSE => "TOKEN_ELSE",
        TokenType::TOKEN_UNKNOWN => "TOKEN_UNKNOWN",
        TokenType::END_OF_SENTENCE => "EOF",
        default => "desconhecido",
    };
}
