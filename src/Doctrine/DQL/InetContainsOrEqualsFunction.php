<?php

namespace App\Doctrine\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

final class InetContainsOrEqualsFunction extends FunctionNode {
    private $firstOperand;
    private $secondOperand;

    public function getSql(SqlWalker $sqlWalker): string {
        $firstOperand = $sqlWalker->walkStringPrimary($this->firstOperand);
        $secondOperand = $sqlWalker->walkStringPrimary($this->secondOperand);

        return sprintf('(%s >>= %s)', $firstOperand, $secondOperand);
    }

    public function parse(Parser $parser): void {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->firstOperand = $parser->StringPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->secondOperand = $parser->StringPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
