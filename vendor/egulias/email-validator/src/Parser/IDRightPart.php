<?php

namespace OmniMailDeps\Egulias\EmailValidator\Parser;

use OmniMailDeps\Egulias\EmailValidator\EmailLexer;
use OmniMailDeps\Egulias\EmailValidator\Result\Result;
use OmniMailDeps\Egulias\EmailValidator\Result\ValidEmail;
use OmniMailDeps\Egulias\EmailValidator\Result\InvalidEmail;
use OmniMailDeps\Egulias\EmailValidator\Result\Reason\ExpectingATEXT;
class IDRightPart extends DomainPart
{
    protected function validateTokens(bool $hasComments): Result
    {
        $invalidDomainTokens = [EmailLexer::S_DQUOTE => \true, EmailLexer::S_SQUOTE => \true, EmailLexer::S_BACKTICK => \true, EmailLexer::S_SEMICOLON => \true, EmailLexer::S_GREATERTHAN => \true, EmailLexer::S_LOWERTHAN => \true];
        if (isset($invalidDomainTokens[$this->lexer->current->type])) {
            return new InvalidEmail(new ExpectingATEXT('Invalid token in domain: ' . $this->lexer->current->value), $this->lexer->current->value);
        }
        return new ValidEmail();
    }
}
