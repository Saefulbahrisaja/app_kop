<?php

namespace App\Services;

class StatusSaldoService
{
    /**
     * Hitung status saldo koperasi
     *
     * @param float $saldo
     * @param float $sisaPiutang
     * @return array
     */
    public function hitung(float $saldo, float $sisaPiutang): array
    {
        // default
        $status = 'AMAN';
        $color  = 'GREEN';

        if ($sisaPiutang > 0) {
            if ($saldo < ($sisaPiutang * 0.5)) {
                $status = 'BAHAYA';
                $color  = 'RED';
            } elseif ($saldo < $sisaPiutang) {
                $status = 'WASPADA';
                $color  = 'ORANGE';
            }
        }

        return [
            'saldo'  => $saldo,
            'status' => $status,
            'color'  => $color,
        ];
    }
}
