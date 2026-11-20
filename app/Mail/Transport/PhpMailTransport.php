<?php

namespace App\Mail\Transport;

use RuntimeException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

/**
 * Sends through PHP's mail(), i.e. the hosting's own sendmail.
 *
 * For shared hosting that disables outbound sockets (stream_socket_client, as on
 * Váš Hosting), where Symfony's SMTP transport can't connect. Enable with
 * MAIL_MAILER=phpmail.
 */
class PhpMailTransport extends AbstractTransport
{
    protected function doSend(SentMessage $message): void
    {
        // The fully rendered message (headers, blank line, MIME body), as SMTP would send it.
        [$head, $body] = explode("\r\n\r\n", $message->toString(), 2) + [1 => ''];

        // Unfold headers so each one is a single line, then take out To and Subject:
        // mail() receives them as arguments and writes them itself.
        $lines = preg_split("/\r\n(?![ \t])/", $head);
        $to = $subject = '';
        $headers = [];
        foreach ($lines as $line) {
            [$name] = explode(':', $line, 2);
            $value = ltrim(substr($line, strlen($name) + 1));
            match (strtolower($name)) {
                'to' => $to = preg_replace("/\r\n[ \t]+/", ' ', $value),
                'subject' => $subject = preg_replace("/\r\n[ \t]+/", ' ', $value),
                'bcc' => null, // never put Bcc into the message
                default => $headers[] = $line,
            };
        }

        // Envelope recipients not visible in To/Cc (i.e. Bcc) go into a Bcc header:
        // the hosting's sendmail runs with -t, delivers to it and strips it.
        $visible = strtolower($to.' '.implode(' ', preg_grep('/^cc:/i', $headers)));
        $hidden = array_filter(
            array_map(fn ($a) => $a->getAddress(), $message->getEnvelope()->getRecipients()),
            fn ($addr) => ! str_contains($visible, strtolower($addr)),
        );
        if ($hidden !== []) {
            $headers[] = 'Bcc: '.implode(', ', $hidden);
        }

        $sender = $message->getEnvelope()->getSender()->getAddress();

        if (! mail($to, $subject, $body, implode("\r\n", $headers), '-f'.$sender)) {
            throw new RuntimeException('mail() refused the message (hosting sendmail).');
        }
    }

    public function __toString(): string
    {
        return 'phpmail://default';
    }
}
