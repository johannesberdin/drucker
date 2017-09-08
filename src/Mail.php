<?php

namespace Drucker;

class Mail
{
    /**
     * Holds the mail plain text.
     * @var string $text
     */
    private $text;

    /**
     * Holds the mail headers.
     * @var array $headers
     */
    private $headers = [];

    /**
     * Holds the extracted original mail text.
     * @var array $original
     */
    private $original = [];

    /**
     * Holds the extracted quoted mail text.
     * @var array $quote
     */
    private $quote = [];

    /**
     * Regular expressions to identify forwards.
     * @var array $forwardPatterns
     */
    private $forwardPatterns = [
      "Begin forwarded message",
      "Anfang der weitergeleiteten E-Mail",
      "Forwarded [mM]essage",
      "Original [mM]essage",
      "Ursprüngliche Nachricht"
    ];

    /**
     * Regular expressions to identify replies.
     * @var array $replyPatterns
     */
    private $replyPatterns = [
      "On\s(\d{2}.\s[a-zA-Z]{3}\s\d{4},\sat\s\d{2}\:\d{2})?,?\s(.*)\swrote\:?",
      "Am\s(\d{2}.\d{2}.\d{4}\sum\s\d{2}:\d{2})?\sschrieb\s(.*)\s?\:",
    ];

    /**
     * Regular expressions to identify quotes.
     * @var string $quotePattern
     */
    private $quotePattern = "/\>$/";

    /**
     * Regular expressions to identify quotes in stripped lines.
     * @var string $quoteStripPattern
     */
    private $quoteStripPattern = "/\s?\>$/";

    /**
     * Extract original and quote from plain text message.
     *
     * @param string $text
     * @param array $headers
     */
    public function __construct($text, $headers = array())
    {
        $this->text = $text;
        $this->headers = $headers;

        $this->extractQuote();
    }

    /**
     * Returns the exctracted mail original.
     *
     * @return string
     */
    public function getOriginal()
    {
        return $this->rtfEncoding(preg_replace("/^\n/", '', strrev(implode("\n", $this->original))));
    }

    /**
     * Returns the exctracted mail quotation.
     *
     * @return string
     */
    public function getQuote()
    {
        return $this->rtfEncoding(preg_replace("/^\n/", '', strrev(implode("\n", $this->quote))));
    }

    /**
     * Gets mail sender.
     *
     * @return mixed
     */
    public function getFrom()
    {
        return $this->getHeader('from');
    }

    /**
     * Gets mail recipient.
     *
     * @return mixed
     */
    public function getTo()
    {
        return $this->getHeader('to');
    }

    /**
     * Gets mail subject.
     *
     * @return mixed
     */
    public function getSubject()
    {
        return $this->getHeader('subject');
    }

    /**
     * Gets date of mail.
     *
     * @return mixed
     */
    public function getDate()
    {
        return $this->getHeader('date');
    }

    /**
     * Gets mail header data.
     *
     * @param string $name
     * @return mixed
     */
    private function getHeader($name)
    {
        return isset($this->headers[$name]) ? $this->headers[$name] : false;
    }

    /**
     * Extracts the original message and quoted message.
     *
     * @return void
     */
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

    /**
     * Encodes utf-8 text into rtf-conform text.
     *
     * From: http://spin.atomicobject.com/2010/08/25/rendering-utf8-characters-in-rich-text-format-with-php/
     *
     * @param string $text
     * @return string
     */
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
