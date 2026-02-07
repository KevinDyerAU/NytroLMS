<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLeaderNotification extends Notification
{
    use Queueable;

    protected $role;

    protected $password;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($role, $password)
    {
        $this->role = $role;
        $this->password = $password;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->subject('Welcome to '.env('APP_NAME').' as '.$this->role)
            ->view('emails.leaders.create', ['role' => $this->role, 'password' => $this->password, 'notifiable' => $notifiable]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'role' => $this->role,
            'created_by' => auth()->user()->id,
            'name' => $notifiable->name,
            'email' => $notifiable->email,
            'ip' => request()->ip(),
        ];
    }
}
