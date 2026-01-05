<?php

namespace App\Services;

use App\Models\ModelPayment;
use App\Models\Modeljenissimpanan as SavingType;

class PaymentStatusService
{
    // 🔑 STATUS PER SIMPANAN
   public function getBySimpanan(int $userId, int $simpananId): ?string
    {
        return ModelPayment::where('user_id', $userId)
            ->where('simpanan_id', $simpananId)
            ->orderByDesc('created_at')
            ->value('status');
    }

    // 🔑 STATUS PER CICILAN
    public function getByCicilan(int $installmentId): ?string
    {
        return ModelPayment::where('installment_id', $installmentId)
            ->latest()
            ->value('status');
    }

    // 🔑 CEKLIS AKTIF HANYA JIKA BUKAN PENDING / APPROVED
    public function isSelectable(?string $status): bool
    {
        return !in_array($status, ['PENDING', 'APPROVED']);
    }

    public function getSimpananAmount(string $code): float
    {
        $currentYear = now()->year;

        // 1️⃣ Coba ambil tahun aktif
        $row = SavingType::where('code', $code)
            ->where('periode', $currentYear)
            ->first();

        if ($row) {
            return (float) $row->amount;
        }

        // 2️⃣ Jika tidak ada → ambil tahun TERAKHIR yang tersedia
        $fallback = SavingType::where('code', $code)
            ->whereNotNull('periode')
            ->orderByDesc('periode')
            ->first();

        return $fallback ? (float) $fallback->amount : 0;
    }
}
