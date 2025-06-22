<?php


require_once 'parser.php';

const EX_NUM = 0;
const EX_BOOL = 1;
const EX_ATRIBUICAO = 2;
const EX_NADA = 3;
const EX_ERROR = 4; 

class Variable {
    public Token $token; 
    public int $firstUseType;

    public function __construct(Token $token, int $firstUseType) {
        $this->token = $token;
        $this->firstUseType = $firstUseType;
    }
}

class SemanticActions {
    private array $symbols;
    private array $productions;
    private array $variables = [];

    public function __construct(array $symbols, array $productions) {
        $this->symbols = $symbols;
        $this->productions = $productions;
    }

    private function getSymbolName(int $index): string {
        foreach ($this->symbols as $s) {
            if ($s->index === $index) {
                return $s->name;
            }
        }
        return "erro";
    }

    private function verifyVar(string $varName): ?Variable {
        foreach ($this->variables as $variable) {
            if ($variable->token->value === $varName) {
                return $variable;
            }
        }
        return null;
    }

    private function addVariable(Token $token, int $type): void {
        $v = new Variable($token, $type);
        $this->variables[] = $v;
    }

    public function executeAction(int $productionIndex, array $reducedElements): nonTerminal {
        $nt = new nonTerminal(
            $this->productions[$productionIndex]->nonTerminalIndex,
            "<" . $this->getSymbolName($this->productions[$productionIndex]->nonTerminalIndex) . ">",
            EX_NADA 
        );

        $auxVar = null;
    

        switch ($productionIndex) {
            case 3:
                $varToken = $reducedElements[0];
                $expNt = $reducedElements[2];

                $auxVar = $this->verifyVar($varToken->value);

                if ($auxVar === null) {
                    $this->addVariable($varToken, $expNt->type);
                } else {
                    if ($auxVar->firstUseType !== $expNt->type) {
                        cerr("Erro: Atribuição de tipos diferentes\n");
                        $nt->type = EX_ERROR;
                        return $nt;
                    }
                }
                $nt->type = EX_ATRIBUICAO;
                return $nt;

            case 6:   // <Exp> ::= <Exp> TOKEN_EQ <Exp>
            case 7:   // <Exp> ::= <Exp> TOKEN_NEQ <Exp>
            case 8:   // <Exp> ::= <Exp> TOKEN_GT <Exp>
            case 9:   // <Exp> ::= <Exp> TOKEN_LT <Exp>
            case 10:  // <Exp> ::= <Exp> TOKEN_GTE <Exp>
            case 11:  // <Exp> ::= <Exp> TOKEN_LTE <Exp>
                $exp1 = $reducedElements[0];
                $exp2 = $reducedElements[2];

                if ($exp1->type !== $exp2->type) {
                    cerr("Erro: Operação de comparação com tipos errados\n");
                    $nt->type = EX_ERROR;
                    return $nt;
                }
                $nt->type = EX_BOOL;
                return $nt;

            case 12: // <Exp> ::= TOKEN_LPAREN <Exp> TOKEN_RPAREN
                $innerExp = $reducedElements[1];
                $nt->type = $innerExp->type;
                return $nt;

            case 13: // <Exp> ::= TOKEN_TRUE
            case 14: // <Exp> ::= TOKEN_FALSE
                $nt->type = EX_BOOL;
                return $nt;

            case 15: // <Exp> ::= TOKEN_NUMBER
                $nt->type = EX_NUM;
                return $nt;

            case 16: // <Exp> ::= TOKEN_VAR
                $varToken = $reducedElements[0];
                $auxVar = $this->verifyVar($varToken->value);

                if ($auxVar === null) {
                    cerr("Erro: Variável '" . $varToken->value . "' não declarada\n");
                    $nt->type = EX_ERROR;
                    return $nt;
                }
                $nt->type = $auxVar->firstUseType;
                return $nt;

            default:
                $nt->type = EX_NADA;
                return $nt;
        }
    }
}

?>