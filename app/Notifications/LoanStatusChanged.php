<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LoanStatusChanged extends Notification
{
    use Queueable;

    protected $loan;
    protected $oldStatus;
    protected $newStatus;

    public function __construct($loan, $oldStatus, $newStatus)
    {
        $this->loan = $loan;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    public function via($notifiable)
    {
        return ['database']; // TANPA EMAIL / FIREBASE
    }

    public function toDatabase($notifiable)
    {
        return [
            'type'        => 'LOAN_STATUS_CHANGED',
            'loan_id'     => $this->loan->id,
            'old_status'  => $this->oldStatus,
            'new_status'  => $this->newStatus,
            'loan_type'   => $this->loan->loan_type,
            'amount'      => $this->loan->amount,
            'message'     => $this->messageFor($notifiable),
        ];
    }

    private function messageFor($notifiable)
    {
        return match ($this->newStatus) {
            'APPROVED_BENDAHARA' =>
                'Pinjaman disetujui Bendahara dan menunggu persetujuan Ketua.',

            'APPROVED' =>
                'Pinjaman Anda telah disetujui oleh Ketua.',

            'REJECTED' =>
                'Pinjaman Anda ditolak.',

            default =>
                'Status pinjaman Anda diperbarui.',
        };
    }
}
