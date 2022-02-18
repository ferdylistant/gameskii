<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SocialRejectFriendNotification extends Notification
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
            'id_game_account' => $this->details['id_game_account'],
            'nickname' => $this->details['nickname'],
            'game_id' => $this->details['game_id'],
            'game_name' => $this->details['game_name'],
            'user_id' => $this->details['user_id'],
            'user_name' => $this->details['user_name'],
            'user_email' => $this->details['user_email'],
            'user_avatar' => $this->details['user_avatar'],
            'following_date' => $this->details['following_date'],
            'message' => $this->details['message'],
        ];
    }
}
