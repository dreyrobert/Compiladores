<?php

require_once 'base.php';
require_once 'lexer.php';
require_once 'parser.php';
require_once 'semActions.php';

function cerr($message) {
    file_put_contents('php://stderr', $message);
}

if ($argc < 2) {
    cerr("Uso: php main.php <arquivo_entrada>\n");
    exit(1);
}

$filename = $argv[1];
$input = file_get_contents($filename);
if ($input === false) {
    cerr("Erro ao abrir o arquivo de entrada " . $filename . "\n");
    exit(1);
}

// --- Análise Léxica ---
$lexer = new DFA();
$tokens = $lexer->analyze($input);

$tabelaDeSimbolos = [];
foreach ($tokens as $token) {
    $tabelaDeSimbolos[] = [
        'linha' => $token->line,
        'identificador' => $token->value,
        'rótulo' => $token->tokenName
    ];
}

echo "Linha\tIdentificador\tRótulo\n";
foreach ($tabelaDeSimbolos as $simbolo) {
    echo "{$simbolo['linha']}\t{$simbolo['identificador']}\t{$simbolo['rótulo']}\n";
}

echo "\n";
echo 'Fita de Saída (Tokens): ';
foreach ($tokens as $t) {
    echo getName($t->type) . " ";
}
echo PHP_EOL;

$parser = new SLR_Table();

$parser->displayTable();

$result = $parser->parse($tokens);

exit($result);

?>