<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamNotification extends Notification
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
            'games_id' => $this->details['games_id'],
            'name' => $this->details['name'],
            'logo' => $this->details['logo'],
            'ranks_id' => $this->details['ranks_id'],
            'won' => $this->details['won'],
            'lose' => $this->details['lose'],
            'total_match_scrim' => $this->details['total_match_scrim'],
            'total_match_tournament' => $this->details['total_match_tournament'],
            'point' => $this->details['point'],
            'master' => $this->details['master'],
            'created_at' => $this->details['created_at'],
            'message' => $this->details['message'],
            'member-team' => $this->details['member-team'],
        ];
    }
}
