<?php

require_once 'base.php';

function lerFita(string $filename): array {
    $tokens = [];

    $fp = fopen($filename, 'rb');
    if (!$fp) {
        echo "Erro ao abrir o arquivo para leitura na análise sintática.\n";
        return [];
    }

    $length = unpack('i', fread($fp, 4))[1];

    for ($i = 0; $i < $length; $i++) {
        $typeInt = unpack('i', fread($fp, 4))[1];
        $position = unpack('i', fread($fp, 4))[1];
        $line = unpack('i', fread($fp, 4))[1];

        $valueLength = unpack('i', fread($fp, 4))[1];
        $value = fread($fp, $valueLength);

        $tokenNameLength = unpack('i', fread($fp, 4))[1];
        $tokenName = fread($fp, $tokenNameLength);

        $token = new Token(TokenType::from($typeInt), $value, $line, $position, $tokenName);
        $tokens[] = $token;
    }

    fclose($fp);
    return $tokens;
}

function escreverFita(array $tokens, string $filename): int {
    $fp = fopen($filename, 'wb');
    if (!$fp) {
        echo "Erro ao escrever a fita.\n";
        return 1;
    }

    fwrite($fp, pack('i', count($tokens)));

    foreach ($tokens as $t) {
        fwrite($fp, pack('i', $t->type->value));
        fwrite($fp, pack('i', $t->position));
        fwrite($fp, pack('i', $t->line));

        fwrite($fp, pack('i', strlen($t->value)));
        fwrite($fp, $t->value);

        fwrite($fp, pack('i', strlen($t->tokenName)));
        fwrite($fp, $t->tokenName);
    }

    fclose($fp);
    return 0;
}
