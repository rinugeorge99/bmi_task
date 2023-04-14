<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class forgotpassword extends Mailable {
    use Queueable, SerializesModels;

    /**
    * Create a new message instance.
    *
    * @return void
    */
    public $otpdetails;

    public function __construct( $otpdetails ) {
        $this->otpdetails = $otpdetails;
    }

    /**
    * Get the message envelope.
    *
    * @return \Illuminate\Mail\Mailables\Envelope
    */

    public function envelope() {
        return new Envelope(
            subject: 'Forgotpassword',
        );
    }

    /**
    * Get the message content definition.
    *
    * @return \Illuminate\Mail\Mailables\Content
    */

    public function content() {
        return new Content(
            view: 'emails.forgotpassword',

        );
    }

    /**
    * Get the attachments for the message.
    *
    * @return array
    */

    public function attachments() {
        return [];
    }
}
