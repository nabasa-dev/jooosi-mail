<?php

namespace OmniMailDeps\Egulias\EmailValidator\Parser\CommentStrategy;

use OmniMailDeps\Egulias\EmailValidator\EmailLexer;
use OmniMailDeps\Egulias\EmailValidator\Result\Result;
use OmniMailDeps\Egulias\EmailValidator\Result\ValidEmail;
use OmniMailDeps\Egulias\EmailValidator\Result\InvalidEmail;
use OmniMailDeps\Egulias\EmailValidator\Result\Reason\ExpectingATEXT;
class DomainComment implements CommentStrategy
{
    public function exitCondition(EmailLexer $lexer, int $openedParenthesis): bool
    {
        return !($openedParenthesis === 0 && $lexer->isNextToken(EmailLexer::S_DOT));
    }
    public function endOfLoopValidations(EmailLexer $lexer): Result
    {
        //test for end of string
        if (!$lexer->isNextToken(EmailLexer::S_DOT)) {
            return new InvalidEmail(new ExpectingATEXT('DOT not found near CLOSEPARENTHESIS'), $lexer->current->value);
        }
        //add warning
        //Address is valid within the message but cannot be used unmodified for the envelope
        return new ValidEmail();
    }
    public function getWarnings(): array
    {
        return [];
    }
}
