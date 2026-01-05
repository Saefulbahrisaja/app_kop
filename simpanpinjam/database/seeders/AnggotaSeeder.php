<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\ModelUser;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AnggotaSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [2013, 'Agung Wibowo'],
            [2013, 'Denny Pribadi'],
            [2013, 'Andi Riyanto'],
            [2013, 'Taufik Hidayatulloh'],
            [2013, 'Rusda Wajhillah'],
            [2013, 'Yusti Farlina'],
            [2023, 'Yuri Rahayu'],
            [2013, 'Lis Saumi Ramdhani'],
            [2014, 'Jamal Maulana Hudin'],
            [2015, 'Risza Chrisyaniardi'],
            [2016, 'Irwan Tanu Kusnadi'],
            [2017, 'Rizal Amegia Saputra'],
            [2017, 'Apip Supiandi'],
            [2017, 'Saeful Bahri'],
            [2018, 'Renny Oktapiani'],
            [2018, 'Shindi Saputri'],
            [2019, 'Desi Susilawati'],
            [2019, 'Dicki Prayudi'],
            [2019, 'Asri Mutiasari'],
            [2019, 'Resti Yulistria'],
            [2019, 'Satia Suhada'],
            [2019, 'Erika Mutiara'],
            [2020, 'Gunawan'],
            [2020, 'Eko Sulistyo'],
            [2020, 'Dede Wintana'],
            [2020, 'Rusli Nugraha'],
            [2021, 'Rifa Nurafifah Syabaniah'],
            [2021, 'Dini Hardiani'],
            [2022, 'M. Ghani'],
            [2022, 'Ita Yulianti'],
            [2022, 'Erni Ermawati'],
            [2023, 'A. Gunawan'],
            [2023, 'Lisaeni'],
            [2023, 'Aira Elzahra'],
            [2023, 'Deni'],
            [2025, 'Dinar Ismunandar'],
            [2025, 'Elah Nurlelah'],
            [2025, 'Jafar'],
            [2025, 'Iyad'],
            [2025, 'Uni Eva'],
            [2025, 'Neni'],
            [2025, 'Habib'],
        ];

        $no = 1;

        foreach ($data as [$tahun, $nama]) {

            $username = Str::slug($nama, '_');

            ModelUser::create([
                'username'       => $username,
                'password'       => Hash::make('123456'),
                'full_name'      => $nama,
                'alamat'         => 'Kantor Koperasi',
                'tanggal_lahir'  => '1980-01-01',
                'agama'          => 'Islam',
                'tanggal_gabung' => Carbon::create($tahun, 1, 1),
                'no_hp'          => '08' . rand(1111111111, 9999999999),
                'email'          => $username . '@koperasi.test',
                'no_anggota'     => str_pad($no++, 5, '0', STR_PAD_LEFT),
                'role'           => 'MEMBER',
            ]);
        }
    }
}
