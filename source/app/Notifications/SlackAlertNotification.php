<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class SlackAlertNotification extends Notification
{
    protected string $message;

    protected array $fields;

    protected string $level;

    protected ?string $channel;

    protected string $environment;

    /**
     * Create a new notification instance.
     *
     * @param string $message Main content of the alert
     * @param array $fields Key-value details (optional)
     * @param string $level 'info', 'warning', or 'error'
     * @param string|null $channel Custom Slack channel (optional)
     */
    public function __construct(string $message, array $fields = [], string $level = 'info', ?string $channel = 'key-institute')
    {
        $this->message = trim($message) ?: 'No message provided';
        $this->fields = $fields;
        $this->level = $this->validateLevel($level);
        $this->channel = $channel;
        $this->environment = config('app.env', 'unknown');
    }

    /**
     * Validate and normalize the notification level.
     */
    protected function validateLevel(string $level): string
    {
        $validLevels = ['info', 'warning', 'error'];

        return in_array(strtolower($level), $validLevels) ? strtolower($level) : 'info';
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     */
    public function via($notifiable): array
    {
        return ['slack'];
    }

    /**
     * Build the Slack message.
     *
     * @param mixed $notifiable
     */
    public function toSlack($notifiable): SlackMessage
    {
        $msg = new SlackMessage();

        // Set custom channel if provided
        if ($this->channel) {
            $msg->to($this->channel);
        }

        // Set level-specific styling and emoji
        $emoji = match ($this->level) {
            'error' => ':red_circle:',
            'warning' => ':warning:',
            default => ':information_source:'
        };

        // Apply level styling
        match ($this->level) {
            'error' => $msg->error(),
            'warning' => $msg->warning(),
            default => $msg->info(),
        };

        // Format the message with timestamp and environment
        $timestamp = now()->toDateTimeString();
        $formattedMessage = "*[{$this->environment}] {$this->message}* {$emoji}\n*Timestamp:* {$timestamp}";

        $msg->content($formattedMessage);

        // Add fields as attachment if present
        if (!empty($this->fields)) {
            $msg->attachment(function ($attachment) {
                $attachment
                    ->title('Details')
                    ->fields($this->fields)
                    ->color($this->getAttachmentColor());
            });
        }

        return $msg;
    }

    /**
     * Get the attachment color based on level.
     */
    protected function getAttachmentColor(): string
    {
        return match ($this->level) {
            'error' => '#FF0000',
            'warning' => '#FFA500',
            default => '#36A64F',
        };
    }
}
