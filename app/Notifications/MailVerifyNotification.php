<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MailVerifyNotification extends Notification
{
    use Queueable;

    public $details;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(array $details)
    {
        $this->details = $details;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('Email Verification Notification')
                    ->greeting('Hello, '.$this->details['name'])
                    ->line('Click the button below to verify your email address.')
                    ->action('Verification', $this->details['url'])
                    ->line('This email verification link will expire in 60 minutes.')
                    ->line('Thank you for registering our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'name' => $this->details['name'],
            'email' => $this->details['email']
        ];
    }
}
