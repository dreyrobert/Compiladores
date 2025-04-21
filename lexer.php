<?php

require_once __DIR__ . '/base.php';

class DFA {

    private array $transitions = [];
    private array $finalStates = [];

    public function __construct() {
        $this->buildDFA();
    }

    private function buildDFA(): void {
        // Operadores e símbolos
        $this->transitions[0]['='] = 2;
        $this->transitions[0]['!'] = 3;
        $this->transitions[0]['>'] = 4;
        $this->transitions[0]['<'] = 5;
        $this->transitions[0]['('] = 10;
        $this->transitions[0][')'] = 11;
        $this->transitions[0][':'] = 30;

        $this->transitions[2]['='] = 6;
        $this->transitions[3]['='] = 7;
        $this->transitions[4]['='] = 8;
        $this->transitions[5]['='] = 9;
        $this->transitions[30]['='] = 31;

        // Palavras-chave true/false
        foreach (['t' => 'r', 'T' => 'R'] as $t => $r) {
            $this->transitions[0][$t] = 12;
            $this->transitions[12][$r] = 13;
            $this->transitions[13][strtolower('U')] = 14;
            $this->transitions[14][strtolower('E')] = 15;
        }
        $this->transitions[15]['_'] = 21;

        foreach (['f' => 'a', 'F' => 'A'] as $f => $a) {
            $this->transitions[0][$f] = 16;
            $this->transitions[16][$a] = 17;
            $this->transitions[17][strtolower('L')] = 18;
            $this->transitions[18][strtolower('S')] = 19;
            $this->transitions[19][strtolower('E')] = 20;
        }
        $this->transitions[20]['_'] = 21;

        // if / else / do
        $this->transitions[0]['i'] = $this->transitions[0]['I'] = 22;
        $this->transitions[22]['f'] = $this->transitions[22]['F'] = 23;

        $this->transitions[0]['e'] = $this->transitions[0]['E'] = 24;
        $this->transitions[24]['l'] = $this->transitions[24]['L'] = 25;
        $this->transitions[25]['s'] = $this->transitions[25]['S'] = 26;
        $this->transitions[26]['e'] = $this->transitions[26]['E'] = 27;

        $this->transitions[0]['d'] = $this->transitions[0]['D'] = 28;
        $this->transitions[28]['o'] = $this->transitions[28]['O'] = 29;

        // Números
        for ($c = ord('0'); $c <= ord('9'); ++$c) {
            $this->transitions[0][chr($c)] = 32;
            $this->transitions[32][chr($c)] = 32;
        }

        // Identificadores
        for ($c = ord('a'); $c <= ord('z'); ++$c) {
            $this->transitions[1][chr($c)] = 1;
        }
        for ($c = ord('A'); $c <= ord('Z'); ++$c) {
            $this->transitions[1][chr($c)] = 1;
        }
        for ($c = ord('0'); $c <= ord('9'); ++$c) {
            $this->transitions[1][chr($c)] = 1;
        }

        $this->transitions[0]['_'] = 1;
        $this->transitions[1]['_'] = 1;

        // Estados finais
        $this->finalStates = [
            21 => TokenType::TOKEN_UNKNOWN,
            4 => TokenType::TOKEN_GT,
            5 => TokenType::TOKEN_LT,
            6 => TokenType::TOKEN_EQ,
            7 => TokenType::TOKEN_NEQ,
            8 => TokenType::TOKEN_GTE,
            9 => TokenType::TOKEN_LTE,
            10 => TokenType::TOKEN_LPAREN,
            11 => TokenType::TOKEN_RPAREN,
            15 => TokenType::TOKEN_TRUE,
            20 => TokenType::TOKEN_FALSE,
            23 => TokenType::TOKEN_IF,
            27 => TokenType::TOKEN_ELSE,
            29 => TokenType::TOKEN_DO,
            31 => TokenType::TOKEN_ATRIB,
            32 => TokenType::TOKEN_NUMBER,
            1  => TokenType::TOKEN_VAR,
        ];
    }

    public function analyze(string $input): array {
        $tokens = [];
        $buffer = [];
        $position = 0;
        $current_line = 1;

        while ($position < strlen($input)) {
            while ($position < strlen($input) && !ctype_space($input[$position])) {
                $token = $this->getToken($input, $position);
                $token->line = $current_line;
                $token->position = $position;
                $buffer[] = $token;
                $position += strlen($token->value);
            }

            if (isset($input[$position]) && $input[$position] === "\n") {
                $current_line++;
            }
            $position++;

            $buffer = $this->processBuffer($buffer);

            foreach ($buffer as $token) {
                if (!empty($token->value)) {
                    $tokens[] = $token;
                } else {
                    fwrite(STDERR, "Erro lexico: token inválido '{$token->value}' na posição $position\n");
                }
            }
            $buffer = [];
        }

        $endToken = new Token(
            TokenType::END_OF_SENTENCE,
            "$",
            $current_line,  // linha
            0,  // posição (ajustar conforme necessário)
            getName(TokenType::END_OF_SENTENCE)
        );
        
        // Adicionar o token ao array de tokens
        $tokens[] = $endToken;
        
        return $tokens;
        
    }

    private function processBuffer(array $buffer): array {
        $tokens = [];
        $hasInvalid = false;

        foreach ($buffer as $tok) {
            if ($tok->type === TokenType::TOKEN_UNKNOWN) {
                $hasInvalid = true;
                fwrite(STDERR, "Erro lexico: token inválido '{$tok->value}' na posição {$tok->position}, na linha {$tok->line}\n");
                break;
            }
        }

        if ($hasInvalid) {
            $combined = implode('', array_map(fn($t) => $t->value, $buffer));
            $tokens[] = new Token(
                TokenType::TOKEN_UNKNOWN,
                $combined,
                $buffer[0]->line,
                $buffer[0]->position,
                getName(TokenType::TOKEN_UNKNOWN)
            );
        } else {
            $tokens = $buffer;
        }

        return $tokens;
    }

    private function getToken(string $input, int $start): Token {
        $state = 0;
        $value = '';
        $pos = $start;
        $lastValidState = -1;
        $lastValidPos = $start;

        while ($pos < strlen($input)) {
            $char = $input[$pos];

            if (ctype_space($char)) {
                if (!empty($value)) break;
                $pos++;
                continue;
            }

            if (isset($this->transitions[$state][$char])) {
                $state = $this->transitions[$state][$char];
                $value .= $char;
                $pos++;

                if (isset($this->finalStates[$state])) {
                    $lastValidState = $state;
                    $lastValidPos = $pos;
                }
            } else {
                $state = 21;
                $value .= $char;
                $pos++;
            }
        }

        if ($lastValidState !== -1) {
            return new Token(
                $this->finalStates[$lastValidState],
                substr($input, $start, $lastValidPos - $start),
                0,  // linha (será ajustado depois)
                0,  // posição (será ajustado depois)
                getName($this->finalStates[$lastValidState])
            );
        } else {
            return new Token(
                TokenType::TOKEN_UNKNOWN,
                substr($input, $start, $pos - $start),
                0,  // linha (será ajustado depois)
                0,  // posição (será ajustado depois)
                getName(TokenType::TOKEN_UNKNOWN)
            );
        }
        
    }
}
