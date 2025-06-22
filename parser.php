<?php

require_once 'parser.php';
require_once 'semActions.php';

class Symbol {
    public int $index;
    public string $name;
    public int $type;
}

class Production {
    public int $index;
    public int $nonTerminalIndex;
    public int $symbolCount;
}

class Operation {
    public int $symbolIndex;
    public int $action;
    public int $value;
}

class State {
    public int $index;
    public int $actionCount;
    public array $operations;
}

class nonTerminal {
    public int $index;
    public string $name;
    public int $type;
    public $value;

    public function __construct(int $index, string $name, int $type, $value = null) {
        $this->index = $index;
        $this->name = $name;
        $this->type = $type; 
        $this->value = $value;
    }
}

class SLR_Table {
    public const OP_SHIFT = 1;
    public const OP_REDUCE = 2;
    public const OP_JUMP = 3;
    public const OP_ACCEPT = 4;

    private array $symbols = [];
    private array $productions = [];
    private array $LALRTable = [];

    public function __construct() {
        $this->initializeParserTable("./parserv1.xml");
    }

    private function initializeParserTable(string $filename): int {
        libxml_use_internal_errors(true);

        $doc = simplexml_load_file($filename);

        if ($doc === false) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                cerr("Erro ao carregar o arquivo XML: " . $error->message . "\n");
            }
            libxml_clear_errors();
            return 1;
        }

        if ($doc->m_Symbol === null) {
            cerr("Erro: elemento raiz ou m_Symbol não encontrado.\n");
            return 1;
        }

        $symbolCount = (int)$doc->m_Symbol->attributes()->Count;
        foreach ($doc->m_Symbol->Symbol as $xmlSymbol) {
            $s = new Symbol();
            $s->index = (int)$xmlSymbol->attributes()->Index;
            $s->name = (string)$xmlSymbol->attributes()->Name;
            $s->type = (int)$xmlSymbol->attributes()->Type;
            $this->symbols[] = $s;
        }

        if ($doc->m_Production === null) {
            cerr("Erro: elemento m_Production não encontrado.\n");
            return 1;
        }

        $productionCount = (int)$doc->m_Production->attributes()->Count;
        foreach ($doc->m_Production->Production as $xmlProd) {
            $p = new Production();
            $p->index = (int)$xmlProd->attributes()->Index;
            $p->nonTerminalIndex = (int)$xmlProd->attributes()->NonTerminalIndex;
            $p->symbolCount = (int)$xmlProd->attributes()->SymbolCount;
            $this->productions[] = $p;
        }

        if ($doc->LALRTable === null) {
            cerr("Erro: elemento LALRTable não encontrado.\n");
            return 1;
        }

        $lalrTableCount = (int)$doc->LALRTable->attributes()->Count;

        foreach ($doc->LALRTable->LALRState as $xmlState) {
            $s = new State();
            $s->index = (int)$xmlState->attributes()->Index;
            $s->actionCount = (int)$xmlState->attributes()->ActionCount;

            $invalidOperation = new Operation();
            $invalidOperation->symbolIndex = -1;
            $invalidOperation->action = -1;
            $invalidOperation->value = -1;
            $s->operations = array_fill(0, $symbolCount, $invalidOperation);

            foreach ($xmlState->LALRAction as $xmlOper) {
                $op = new Operation();
                $op->symbolIndex = (int)$xmlOper->attributes()->SymbolIndex;
                $op->action = (int)$xmlOper->attributes()->Action;
                $op->value = (int)$xmlOper->attributes()->Value;
                $s->operations[$op->symbolIndex] = $op;
            }
            $this->LALRTable[] = $s;
        }

        return 0;
    }

    public function displayTable(): void {
        echo "Displaying table:\n";

        echo "Symbols:\n";
        foreach ($this->symbols as $s) {
            echo $s->index . " / " . $s->name . " / " . $s->type . "\n";
        }

        echo "Productions:\n";
        foreach ($this->productions as $p) {
            echo $p->index . " / " . $p->nonTerminalIndex . " / " . $p->symbolCount . "\n";
        }

        echo "States:\n";
        foreach ($this->LALRTable as $s) {
            echo "State " . $s->index . " has operations:\n";
            foreach ($s->operations as $op) {
                if ($op->action !== -1) {
                    echo "Operation: " . $op->symbolIndex . " / " . $op->action . " / " . $op->value . "\n";
                }
            }
        }
        echo "States number: " . count($this->LALRTable) . "\n";
    }

    private function getSymbolIndex(string $symbolName): int {
        foreach ($this->symbols as $s) {
            if ($s->name === $symbolName) {
                return $s->index;
            }
        }
        return -1;
    }

    private function print_state_stack(array $stateStack): void {
        echo "State stack: ";
        foreach ($stateStack as $item) {
            if (is_int($item)) {
                echo $item . " - ";
            } elseif ($item instanceof Token) {
                echo "[T:" . $item->value . "] - ";
            } elseif ($item instanceof nonTerminal) {
                echo "[NT:" . $item->name . "] - ";
            } else {
                echo "[Unknown] - ";
            }
        }
        echo "\n";
    }

    public function parse(array $tokens): int {
        $semanticActions = new SemanticActions($this->symbols, $this->productions);

        $stateStack = [0];
        $meanState = 0;
        $reducedElements = [];

        for ($j = 0; $j < count($tokens); $j++) {
            $t = $tokens[$j];

            $currentState = end($stateStack);
            if (!is_int($currentState)) {
                cerr("Erro interno: O último elemento da pilha de estados não é um inteiro.\n");
                return 1;
            }

            $currentTokenIndex = $this->getSymbolIndex(getName($t->type));
            // echo "Current token: " . getName($t->type) . "\n";

            if (!isset($this->LALRTable[$currentState]) || !isset($this->LALRTable[$currentState]->operations[$currentTokenIndex])) {
                 cerr("Sentença não reconhecida: Ação não definida para estado " . $currentState . " e token " . getName($t->type) . "\n");
                 cerr("Erro próximo ao token '" . $t->value . "' na linha " . $t->line . "/" . $t->position . "\n");
                 return 1;
            }

            $op = $this->LALRTable[$currentState]->operations[$currentTokenIndex];

            $reducedElements = []; 

            //echo "[" . $currentState . ", " . $currentTokenIndex . "] -> " . $op->action . " / " . $op->value . "\n";

            switch ($op->action) {
                case self::OP_SHIFT:
                    $stateStack[] = $t;
                    // echo "Shift " . getName($t->type) . "\n";
                    $stateStack[] = $op->value;
                    //echo "State " . $op->value . "\n";
                    break;

                case self::OP_REDUCE:
                    for ($i = 0; $i < $this->productions[$op->value]->symbolCount; $i++) {
                        array_pop($stateStack);
                        $reducedElement = array_pop($stateStack);

                        if ($reducedElement === null) {
                            cerr("Erro interno: Tentativa de desempilhar de uma pilha vazia durante a redução.\n");
                            return 1;
                        }
                        array_unshift($reducedElements, $reducedElement);
                    }

                    $meanState = end($stateStack);
                    if (!is_int($meanState)) {
                        cerr("Erro interno: O topo da pilha após desempilhar não é um estado.\n");
                        return 1;
                    }

                    $auxNt = $semanticActions->executeAction($op->value, $reducedElements);

                    // echo "Novo nao terminal: " . $auxNt->name . " Tipo: " . $auxNt->type . "\n";

                    if ($auxNt->type === EX_ERROR) { 
                        cerr("Sentença nao reconhecida\n");
                        cerr("Erro proximo ao token '" . $tokens[$j - 1]->value . "' na linha " . $tokens[$j - 1]->line . "/" . $tokens[$j - 1]->position . "\n");
                        return 1;
                    }
                    $stateStack[] = $auxNt;

                    $gotoState = $this->LALRTable[$meanState]->operations[$this->productions[$op->value]->nonTerminalIndex]->value;
                    $stateStack[] = $gotoState;

                    $j--;
                    //echo "Remove " . $this->productions[$op->value]->index . " e [" . $meanState . ", " . $this->productions[$op->value]->nonTerminalIndex . "] -> " . end($stateStack) . "\n";
                    break;

                case self::OP_ACCEPT:
                    echo "Aceito\n";
                    return 0;

                default:
                    cerr("Sentença nao reconhecida\n");
                    cerr("Erro proximo ao token '" . $t->value . "' na linha " . $t->line . "/" . $t->position . "\n");
                    return 1;
            }
            //$this->print_state_stack($stateStack);
        }
        
        cerr("Sentença nao reconhecida: Fim da entrada sem aceitação.\n");
        return 1;
    }
}

?>