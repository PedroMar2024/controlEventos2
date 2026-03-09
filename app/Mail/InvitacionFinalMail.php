<?php
namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvitacionFinalMail extends Mailable
{
    use SerializesModels;

    public $invitado;
    public $url;

    public function __construct($invitado, $url)
    {
        $this->invitado = $invitado;
        $this->url = $url;
    }

    public function build()
    {
        return $this->subject('Tu invitación final al evento')
            ->view('emails.invitacion_final');
    }
}