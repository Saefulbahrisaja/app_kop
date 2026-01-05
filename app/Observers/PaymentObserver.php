<?php

namespace App\Observers;

use App\Models\ModelPayment;
use Illuminate\Support\Facades\Storage;

class PaymentObserver
{
    /**
     * Saat payment DIHAPUS
     */
    public function deleting(ModelPayment $payment): void
    {
        $this->deleteProof($payment->proof);
    }

    /**
     * Saat payment DIUPDATE
     */
    public function updated(ModelPayment $payment): void
    {
        // 🔁 Proof diganti → hapus proof lama
        if ($payment->isDirty('proof')) {
            $old = $payment->getOriginal('proof');
            $this->deleteProof($old);
        }

        // ❌ Payment ditolak → hapus proof
        if (
            $payment->isDirty('status')
            && $payment->status === 'REJECTED'
        ) {
            $this->deleteProof($payment->proof);
        }
    }

    /**
     * Helper hapus file proof dengan aman
     */
    protected function deleteProof(?string $path): void
    {
        if (!$path) return;

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
