<?php
set_time_limit(0);

date_default_timezone_set('UTC');

use PhpMimeMailParser\Parser;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;
use Drucker\Drucker;
use Drucker\Mail;

require __DIR__.'/vendor/autoload.php';

$configPath = getcwd() . '/drucker.yml';
$config = Yaml::parse(file_get_contents($configPath));

$mailPath = getcwd() . DIRECTORY_SEPARATOR .  $config['mail path'];

// Zee Drucker.
$printer = new Drucker($mailPath);

$mail = file_get_contents('php://stdin');

$allowedSender = false;
$checkSender = true;

// Parse mail input.
do {
    $parser = new Parser();
    $parser->setText($mail);

    // Checks if the sender is whitelisted for printing.
    if ($checkSender) {
        foreach ($config['allowed senders'] as $sender) {
            if (preg_match("/$sender/", $parser->getHeader('from'))) {
                $allowedSender = true;
            }
        }
    }

    if ($allowedSender) {
        $checkSender = false;
        // Some nice logging.
        echo "[inf] ".'Got mail: '.$parser->getHeader('from').' '.$parser->getHeader('subject') ."\n";

        // Print attachments
        $attachments = $parser->getAttachments();
        foreach ($attachments as $attachment) {
            //$printer->queue($attachment->getContent());
        }

        // Pipe parsed mail content to our quote extractor.
        $parsedMail = new Mail($parser->getMessageBody('text'));

        // Print the actual mail.
        //$printer->queue($parsedMail->getOriginal());

        // Handle forwarded mail.
        $mail = $parsedMail->getQuote();
    } else {
        $mail = null;
    }
} while (strlen($mail) > 0);
