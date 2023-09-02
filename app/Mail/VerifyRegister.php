<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use stdClass;

class VerifyRegister extends Mailable
{
    use Queueable, SerializesModels;

    private $name;
    private $email;
    private $password;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($name, $email, $password)
    {
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function content(): Content
    {
        $new_user = new stdClass();
        $new_user->name = $this->name;
        $new_user->email = $this->email;
        $new_user->password = $this->password;

        return new Content(
            view: 'emails.confirmation',
            with: [
                'name' => $this->name,
                'email' => $this->email,
                'code' => Crypt::encryptString(json_encode($new_user, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))
            ],
        );
    }
}
