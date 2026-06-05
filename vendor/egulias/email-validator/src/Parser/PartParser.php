<?php

namespace OmniMailDeps\Egulias\EmailValidator\Parser;

use OmniMailDeps\Egulias\EmailValidator\EmailLexer;
use OmniMailDeps\Egulias\EmailValidator\Result\InvalidEmail;
use OmniMailDeps\Egulias\EmailValidator\Result\Reason\ConsecutiveDot;
use OmniMailDeps\Egulias\EmailValidator\Result\Result;
use OmniMailDeps\Egulias\EmailValidator\Result\ValidEmail;
use OmniMailDeps\Egulias\EmailValidator\Warning\Warning;
abstract class PartParser
{
    /**
     * @var Warning[]
     */
    protected $warnings = [];
    /**
     * @var EmailLexer
     */
    protected $lexer;
    public function __construct(EmailLexer $lexer)
    {
        $this->lexer = $lexer;
    }
    abstract public function parse(): Result;
    /**
     * @return Warning[]
     */
    public function getWarnings()
    {
        return $this->warnings;
    }
    protected function parseFWS(): Result
    {
        $foldingWS = new FoldingWhiteSpace($this->lexer);
        $resultFWS = $foldingWS->parse();
        $this->warnings = [...$this->warnings, ...$foldingWS->getWarnings()];
        return $resultFWS;
    }
    protected function checkConsecutiveDots(): Result
    {
        if ($this->lexer->current->isA(EmailLexer::S_DOT) && $this->lexer->isNextToken(EmailLexer::S_DOT)) {
            return new InvalidEmail(new ConsecutiveDot(), $this->lexer->current->value);
        }
        return new ValidEmail();
    }
    protected function escaped(): bool
    {
        $previous = $this->lexer->getPrevious();
        return $previous->isA(EmailLexer::S_BACKSLASH) && !$this->lexer->current->isA(EmailLexer::GENERIC);
    }
}
