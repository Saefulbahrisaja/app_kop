<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\ShuService;

class LpjController extends Controller
{
    public function lpj(Request $r, ShuService $shu)
    {
        $from = $r->query('from', now()->startOfYear());
        $to   = $r->query('to',   now()->endOfYear());

        $pendapatan = $shu->pendapatan($from, $to);
        $biaya      = $shu->biaya($from, $to);
        $nilaiSHU   = $shu->hitungSHU($pendapatan, $biaya);
        $pembagian  = $shu->pembagian($nilaiSHU);

        return response()->json([
            'periode'    => ['from'=>$from, 'to'=>$to],
            'pendapatan' => $pendapatan,
            'biaya'      => $biaya,
            'shu'        => $nilaiSHU,
            'pembagian'  => $pembagian,
        ]);
    }

    public function lpjPdf(Request $r, ShuService $shu)
    {
        $from = $r->query('from', now()->startOfYear());
        $to   = $r->query('to',   now()->endOfYear());

        $pendapatan = $shu->pendapatan($from, $to);
        $biaya      = $shu->biaya($from, $to);
        $nilaiSHU   = $shu->hitungSHU($pendapatan, $biaya);
        $pembagian  = $shu->pembagian($nilaiSHU);

        $pdf = Pdf::loadView('pdf.lpj', compact(
            'from','to','pendapatan','biaya','nilaiSHU','pembagian'
        ))->setPaper('A4','portrait');

        return $pdf->download('LPJ-Koperasi.pdf');
    }
}
