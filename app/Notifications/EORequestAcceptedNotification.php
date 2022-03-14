<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EORequestAcceptedNotification extends Notification
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
        return ['database'];
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
            'id' => $this->details['id'],
            'game_accounts_id' => $this->details['game_accounts_id'],
            'nickname' => $this->details['nickname'],
            'avatar' => $this->details['avatar'],
            'organization_name' => $this->details['organization_name'],
            'organization_email' => $this->details['organization_email'],
            'organization_phone' => $this->details['organization_phone'],
            'provinsi' => $this->details['provinsi'],
            'kabupaten' => $this->details['kabupaten'],
            'kecamatan' => $this->details['kecamatan'],
            'address' => $this->details['address'],
            'verified_at' => $this->details['verified_at'],
            'status' => $this->details['status'],
            'message' => $this->details['message'],
        ];
    }
}
