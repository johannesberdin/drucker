<?php

namespace Drucker;

class Mail
{
    private $text;

    private $original = [];
    private $quote = [];

    private $forwardPatterns = [
      "Begin forwarded message",
      "Anfang der weitergeleiteten E-Mail",
      "Forwarded [mM]essage",
      "Original [mM]essage",
      "Ursprüngliche Nachricht"
    ];

    private $quotePattern = "/\>$/";
    private $quoteStripPattern = "/\s?\>$/";

    public function __construct($text)
    {
        $this->text = $text;

        $this->extractQuote();
    }

    public function getOriginal()
    {
        return preg_replace("/^\n/", '', strrev(implode("\n", $this->original)));
    }

    public function getQuote()
    {
        return preg_replace("/^\n/", '', strrev(implode("\n", $this->quote)));
    }

    private function extractQuote()
    {
        $lines = explode("\n", strrev($this->text));

        foreach ($lines as $line) {
            $line = rtrim($line, "\n");

            if (!preg_match($this->quotePattern, $line)) {
                $this->original[] = $line;
            } else {
                $strippedLine = preg_replace($this->quoteStripPattern, '', $line);

                $forwardHeader = false;
                foreach ($this->forwardPatterns as $forwardPattern) {
                    if (preg_match("/^$forwardPattern/", strrev($strippedLine))) {
                        $forwardHeader = true;
                    }
                }
                if (!$forwardHeader) {
                    $this->quote[] = $strippedLine;
                }
            }
        }
    }
}
