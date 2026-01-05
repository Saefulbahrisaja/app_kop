<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
body{font-family:DejaVu Sans;font-size:12px}
h2{text-align:center}
table{width:100%;border-collapse:collapse;margin-top:10px}
th,td{border:1px solid #ccc;padding:6px}
th{background:#eee}
.right{text-align:right}
</style>
</head>
<body>
<h2>LPJ Koperasi</h2>
<p>Periode: {{ $from }} s/d {{ $to }}</p>

<table>
<tr><th colspan="2">Pendapatan</th></tr>
<tr><td>Jasa Pinjaman</td><td class="right">Rp {{ number_format($pendapatan['jasa_pinjaman'],0,',','.') }}</td></tr>
<tr><td>Administrasi</td><td class="right">Rp {{ number_format($pendapatan['administrasi'],0,',','.') }}</td></tr>
<tr><th>Total</th><th class="right">Rp {{ number_format($pendapatan['total'],0,',','.') }}</th></tr>
</table>

<table>
<tr><th colspan="2">Biaya</th></tr>
<tr><td>Operasional</td><td class="right">Rp {{ number_format($biaya['operasional'],0,',','.') }}</td></tr>
<tr><th>Total</th><th class="right">Rp {{ number_format($biaya['total'],0,',','.') }}</th></tr>
</table>

<table>
<tr><th colspan="2">SHU</th></tr>
<tr><td>Nilai SHU</td><td class="right">Rp {{ number_format($nilaiSHU,0,',','.') }}</td></tr>
</table>

<table>
<tr><th colspan="2">Pembagian SHU</th></tr>
@foreach($pembagian as $k=>$v)
<tr><td>{{ ucfirst(str_replace('_',' ',$k)) }}</td><td class="right">Rp {{ number_format($v,0,',','.') }}</td></tr>
@endforeach
</table>
</body>
</html>
