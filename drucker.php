<?php
set_time_limit(0);

date_default_timezone_set('UTC');

use PhpMimeMailParser\Parser;
use Symfony\Component\Process\Process;
use Drucker\Drucker;
use Drucker\Mail;

require __DIR__.'/vendor/autoload.php';

$mailPath = getcwd() . '/mails';

// Zee Drucker.
$printer = new Drucker($mailPath);

$mail = file_get_contents('php://stdin');

// Parse mail input.
do {
    $parser = new Parser();
    $parser->setText($mail);

    // Some nice logging.
    echo "[inf] ".'Got mail: '.$parser->getHeader('from').' '.$parser->getHeader('subject') ."\n";

    // Print attachments
    $attachments = $parser->getAttachments();
    foreach ($attachments as $attachment) {
        $printer->queue($attachment->getContent());
    }

    // Pipe parsed mail content to our quote extractor.
    $parsedMail = new Mail($parser->getMessageBody('text'));

    // Print the actual mail.
    $printer->queue($parsedMail->getOriginal());

    // Handle forwarded mail.
    $mail = $parsedMail->getQuote();
} while (strlen($mail) > 0);
