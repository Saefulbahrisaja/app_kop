<?php

namespace App\Observers;

use App\Models\ModelPinjaman;
use App\Models\ModelUser;
use App\Notifications\LoanStatusChanged;

class LoanObserver
{
    public function updated(ModelPinjaman $loan)
    {
        if (!$loan->wasChanged('status')) {
            return;
        }

        $oldStatus = $loan->getOriginal('status');
        $newStatus = $loan->status;

        // ===============================
        // STATUS: APPROVED_BENDAHARA
        // → NOTIFIKASI KE KETUA
        // ===============================
        if ($newStatus === 'APPROVED_BENDAHARA') {

            $ketuas = ModelUser::where('role', 'KETUA')->get();
            foreach ($ketuas as $ketua) {
                $ketua->notify(
                    new LoanStatusChanged($loan, $oldStatus, $newStatus)
                );
            }
        }

        // ===============================
        // STATUS: APPROVED / REJECTED
        // → NOTIFIKASI KE ANGGOTA
        // ===============================
        if (in_array($newStatus, ['APPROVED', 'REJECTED'])) {

            $loan->user->notify(
                new LoanStatusChanged($loan, $oldStatus, $newStatus)
            );
        }
    }
}
