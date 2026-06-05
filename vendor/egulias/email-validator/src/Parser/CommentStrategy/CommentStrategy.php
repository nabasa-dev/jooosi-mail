<?php

namespace OmniMailDeps\Egulias\EmailValidator\Parser\CommentStrategy;

use OmniMailDeps\Egulias\EmailValidator\EmailLexer;
use OmniMailDeps\Egulias\EmailValidator\Result\Result;
use OmniMailDeps\Egulias\EmailValidator\Warning\Warning;
interface CommentStrategy
{
    /**
     * Return "true" to continue, "false" to exit
     */
    public function exitCondition(EmailLexer $lexer, int $openedParenthesis): bool;
    public function endOfLoopValidations(EmailLexer $lexer): Result;
    /**
     * @return Warning[]
     */
    public function getWarnings(): array;
}
