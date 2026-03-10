<?php
namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvitacionMail extends Mailable
{
    use SerializesModels;

    public $invitado;
    public $link;

    public function __construct($invitado, $link)
    {
        $this->invitado = $invitado;
        $this->link = $link;
    }

    public function build()
    {
        return $this->subject('Confirmación de invitación al evento')
            ->view('emails.invitacion_mail');
    }
}