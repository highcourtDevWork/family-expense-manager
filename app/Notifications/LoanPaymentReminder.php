<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanPaymentReminder extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Loan $loan,
    ) {}

    /**
     * Deliver in-app (database) and by email.
     *
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
            'type' => 'loan-reminder',
            'loan_id' => $this->loan->id,
            'title' => 'Loan payment due soon',
            'message' => sprintf(
                '%s has ₹%s outstanding, due by %s.',
                $this->loan->loan_name,
                number_format((float) $this->loan->remaining_amount, 2),
                $this->loan->end_date?->format('d M Y') ?? 'soon',
            ),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Loan payment reminder: '.$this->loan->loan_name)
            ->line($this->toArray($notifiable)['message'])
            ->action('View loan', route('loans.show', $this->loan));
    }
}
