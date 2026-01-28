<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class testEmail extends Mailable
{
    use Queueable;
    use SerializesModels;

    private User $user;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user)
    {
        //
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.test.email', [
            'to' => $this->user,
            'cc' => 'mohsinadeel@gmail.com',
        ])->cc(['mohsinadeel@gmail.com']);
    }
}
