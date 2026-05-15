<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public $replyMessage;
    public $clientName;

    public function __construct($clientName, $replyMessage)
    {
        $this->clientName = $clientName;
        $this->replyMessage = $replyMessage;
    }

    public function build()
    {
        return $this->subject('In response to your inquiry from Wood Furniture Showroom')
                    ->view('emails.contact_reply'); // هنكريت الصفحة دي دلوقتي
    }
}
