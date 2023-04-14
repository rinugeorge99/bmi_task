<?php
namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
// use App\Models\Bmi_id;

class VerificationMail extends Mailable
 {
    use Queueable, SerializesModels;

    /**
    * Create a new message instance.
    *
    * @return void
    */
    public $details;

    public function __construct( $details )
 {
        $this->details = $details;
    }

    /**
    * Get the message envelope.
    *
    * @return \Illuminate\Mail\Mailables\Envelope
    */

    public function envelope()
 {
        return new Envelope(
            subject: 'BMIP user credentials',
        );
    }

    /**
    * Get the message content definition.
    *
    * @return \Illuminate\Mail\Mailables\Content
    */

    public function content()
 {
        return new Content(
            view: 'emails.UserDetails',

        );

    }

    /**
    * Get the attachments for the message.
    *
    * @return array
    */

    public function attachments()
 {
        return [];
    }
}