<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EnquiryReceived extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array<string, string|null> $enquiry */
    public function __construct(public array $enquiry)
    {
    }

    public function build(): self
    {
        $mail = $this->subject('Nová poptávka z webu: '.$this->enquiry['name'])
            ->view('emails.enquiry')
            ->with(['enquiry' => $this->enquiry]);

        // "Reply" in the mail client goes straight to the customer.
        if (! empty($this->enquiry['email'])) {
            $mail->replyTo($this->enquiry['email'], $this->enquiry['name']);
        }

        return $mail;
    }
}
