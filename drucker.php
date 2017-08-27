<?php
set_time_limit(0);

date_default_timezone_set('UTC');

use PhpMimeMailParser\Parser;
use Symfony\Component\Process\Process;
use Drucker\Drucker;

require __DIR__.'/vendor/autoload.php';

$print = new Drucker();

$parser = new Parser();
$parser->setText(file_get_contents('php://stdin'));

echo "[inf] ".'Got mail: '.$parser->getHeader('from').' '.$parser->getHeader('subject') ."\n";

// Print attachments
$attachments = $parser->getAttachments();
foreach ($attachments as $attachment) {
    $print->paper($attachment->getContent());
}