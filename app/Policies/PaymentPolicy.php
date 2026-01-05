<?php

namespace App\Policies;

use App\Models\ModelUser;
use App\Models\ModelPayment;

class PaymentPolicy
{
    /**
     * Cek apakah user boleh approve pembayaran.
     */
    public function approvePayment(ModelUser $user, ModelPayment $payment = null)
    {
        return $user->role === 'BENDAHARA' || $user->role === 'ADMIN';
    }

    /**
     * Cek apakah user boleh reject pembayaran.
     */
    public function rejectPayment(ModelUser $user, ModelPayment $payment = null)
    {
        return $user->role === 'BENDAHARA' || $user->role === 'ADMIN';
    }
}
