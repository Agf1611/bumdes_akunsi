<?php
declare(strict_types=1);
return [
    'name' => 'Sistem Pelaporan Keuangan BUMDes',
    'env' => 'local',
    'debug' => true,
    'timezone' => 'Asia/Jakarta',
    // Kosongkan url agar aplikasi otomatis mengikuti host yang sedang dipakai.
    // Cocok untuk localhost Windows (XAMPP/Laragon) dan shared hosting.
    // Jika ingin hardcode, isi lengkap dengan skema, misalnya:
    // 'http://localhost/bumdes-akuntansi' atau 'https://domainanda.com'
    'url' => '',
    'session_name' => 'BUMDESSESSID',
    'session_lifetime' => 7200,
];
