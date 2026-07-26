<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class MonthlyExpenseWarning extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Carbon $month,
        public readonly float $income,
        public readonly float $expense,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'expense-warning',
            'title' => 'Spending exceeds income',
            'message' => sprintf(
                'In %s your family spent ₹%s against income of ₹%s.',
                $this->month->format('F Y'),
                number_format($this->expense, 2),
                number_format($this->income, 2),
            ),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Expense warning — '.$this->month->format('F Y'))
            ->line($this->toArray($notifiable)['message'])
            ->action('View dashboard', route('dashboard'));
    }
}
