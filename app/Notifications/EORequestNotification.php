<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EORequestNotification extends Notification
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
            'games_id' => $this->details['games_id'],
            'users_id' => $this->details['users_id'],
            'message' => $this->details['message'],
            'organization_name' => $this->details['organization_name'],
            'organization_email' => $this->details['organization_email'],
            'organization_phone' => $this->details['organization_phone'],
            'provinsi' => $this->details['provinsi'],
            'kabupaten' => $this->details['kabupaten'],
            'kecamatan' => $this->details['kecamatan'],
            'address' => $this->details['address'],
            'status' => $this->details['status'],
            'avatar' => $this->details['avatar'],
            'created_at' => $this->details['created_at'],
            'updated_at' => $this->details['updated_at'],
        ];
    }
}
