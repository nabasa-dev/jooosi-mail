<?php

namespace OmniMailDeps\Egulias\EmailValidator\Parser\CommentStrategy;

use OmniMailDeps\Egulias\EmailValidator\EmailLexer;
use OmniMailDeps\Egulias\EmailValidator\Result\Result;
use OmniMailDeps\Egulias\EmailValidator\Result\ValidEmail;
use OmniMailDeps\Egulias\EmailValidator\Warning\CFWSNearAt;
use OmniMailDeps\Egulias\EmailValidator\Result\InvalidEmail;
use OmniMailDeps\Egulias\EmailValidator\Result\Reason\ExpectingATEXT;
use OmniMailDeps\Egulias\EmailValidator\Warning\Warning;
class LocalComment implements CommentStrategy
{
    /**
     * @var array<int, Warning>
     */
    private $warnings = [];
    public function exitCondition(EmailLexer $lexer, int $openedParenthesis): bool
    {
        return !$lexer->isNextToken(EmailLexer::S_AT);
    }
    public function endOfLoopValidations(EmailLexer $lexer): Result
    {
        if (!$lexer->isNextToken(EmailLexer::S_AT)) {
            return new InvalidEmail(new ExpectingATEXT('ATEX is not expected after closing comments'), $lexer->current->value);
        }
        $this->warnings[CFWSNearAt::CODE] = new CFWSNearAt();
        return new ValidEmail();
    }
    public function getWarnings(): array
    {
        return $this->warnings;
    }
}
