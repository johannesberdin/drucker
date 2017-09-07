<?php

namespace Drucker;

class Mail
{
    private $text;
    private $headers = [];

    private $original = [];
    private $quote = [];

    private $forwardPatterns = [
      "Begin forwarded message",
      "Anfang der weitergeleiteten E-Mail",
      "Forwarded [mM]essage",
      "Original [mM]essage",
      "Ursprüngliche Nachricht"
    ];
    private $replyPatterns = [
      "On\s(\d{2}.\s[a-zA-Z]{3}\s\d{4},\sat\s\d{2}\:\d{2})?,?\s(.*)\swrote\:?",
      "Am\s(\d{2}.\d{2}.\d{4}\sum\s\d{2}:\d{2})?\sschrieb\s(.*)\s?\:",
    ];

    private $quotePattern = "/\>$/";
    private $quoteStripPattern = "/\s?\>$/";

    public function __construct($text, $headers = array())
    {
        $this->text = $text;
        $this->headers = $headers;

        $this->extractQuote();
    }

    public function getOriginal()
    {
        return $this->rtfEncoding(preg_replace("/^\n/", '', strrev(implode("\n", $this->original))));
    }

    public function getQuote()
    {
        return $this->rtfEncoding(preg_replace("/^\n/", '', strrev(implode("\n", $this->quote))));
    }

    public function getFrom()
    {
        return $this->getHeader('from');
    }

    public function getTo()
    {
        return $this->getHeader('to');
    }

    public function getSubject()
    {
        return $this->getHeader('subject');
    }

    public function getDate()
    {
        return $this->getHeader('date');
    }

    public function getHeader($name)
    {
        return isset($this->headers[$name]) ? $this->headers[$name] : false;
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

                $replyHeader = false;
                $forwardHeader = false;
                foreach ($this->forwardPatterns as $forwardPattern) {
                    if (preg_match("/^$forwardPattern/", strrev($strippedLine))) {
                        $forwardHeader = true;
                    }
                }
                foreach ($this->replyPatterns as $replyPattern) {
                    if (preg_match("/^$replyPattern/", strrev($strippedLine), $match)) {
                        $replyHeader = true;

                        $replyDate = date_parse($match[1]);
                        $this->quote[] = strrev("Date: " . date('D, d M Y H:i:s O', mktime($replyDate['hour'], $replyDate['minute'], $replyDate['second'], $replyDate['month'], $replyDate['day'], $replyDate['year'])));

                        $this->quote[] = strrev("Subject: " . preg_replace("/^(Re|Aw)\:\s?/", "", $this->getSubject()));
                        $this->quote[] = strrev("To: " . $this->getFrom());
                        $this->quote[] = strrev("From: " . $match[2]);
                    }
                }
                if (!$forwardHeader && !$replyHeader) {
                    $this->quote[] = $strippedLine;
                }
            }
        }
    }

    private function rtfEncoding($text)
    {
        $patterns = [
          "[\xC2-\xDF][\x80-\xBF]",
          "[\xE0-\xEF][\x80-\xBF]{2}",
          "[\xF0-\xF4][\x80-\xBF]{3}"
        ];
        $rtfString = $text;
        foreach ($patterns as $pattern) {
            $rtfString = preg_replace_callback("/($pattern)/", function ($match) {
                return "\u".hexdec(bin2hex(mb_convert_encoding($match[0], 'UTF-16', 'UTF-8')))."?";
            }, $rtfString);
        }
        return $rtfString;
    }
}
