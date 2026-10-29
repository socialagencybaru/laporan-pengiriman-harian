# laporan-pengiriman-harian

Laporan Pengiriman Harian SAB Online. Aplikasi web satu halaman untuk mencatat resi pengiriman yang discan setiap hari.

## Cara pakai

Buka `index.html` di browser (Chrome atau Edge). Aplikasi ini tidak butuh server. Koneksi internet hanya dipakai untuk memuat font dan pustaka PDF/Excel.

1. Arahkan kursor ke kotak kuning, lalu scan resi. Scanner biasanya otomatis menekan Enter. Di HP, tekan **Scan pakai kamera** lalu arahkan kamera ke barcode resi, atau pakai **Foto barcode**.
2. Resi otomatis masuk ke kelompok kurirnya, lengkap dengan jumlah per kurir dan total.
3. Isi catatan pendingan (Pesanan Shopee, Pendingan, PO, dan catatan bebas).
4. Cetak laporan PDF harian atau rekap sebulan, atau unduh Excel dengan format yang sama seperti file "Pengiriman Harian".

## Fitur

- Logo SAB Online di samping judul aplikasi dan di laporan PDF.
- Deteksi kurir otomatis dari pola nomor resi. Resi yang tidak dikenali masuk ke "Lainnya" dan bisa dipindah ke kurir lain secara manual.
- Pengecekan jumlah karakter resi sesuai kurirnya (misalnya J&T Express `JY` harus 12 karakter). Resi yang kurang atau kelebihan karakter ditolak dengan pesan SALAH dan tidak disimpan, jadi bisa langsung discan ulang.
- Satu resi hanya bisa dipakai sekali. Resi yang sudah ada di tanggal mana pun ditolak dan tidak disimpan.
- Bisa menempel banyak resi sekaligus (satu resi per baris).
- Scan barcode resi pakai kamera HP, langsung dari kamera atau dari foto label. Untuk kamera langsung, halaman harus dibuka lewat https (misalnya GitHub Pages). Kalau tidak, pakai tombol "Foto barcode".
- Riwayat per tanggal.
- Cetak PDF "Laporan Pengiriman SAB Online", baik harian maupun rekap sebulan.
- Simpan daftar harian ke CSV dan cadangkan semua data ke JSON. Tombol "Buka file laporan" bisa membuka file cadangan (.json), daftar harian (.csv), Excel dari aplikasi ini, maupun file Excel bulanan "Pengiriman Harian" yang lama (satu sheet per tanggal, misalnya "1 OKT").
- Unduh Excel per hari atau sebulan (satu sheet per tanggal), atau salin tabel untuk ditempel ke Excel.

## Pola resi

| Kurir | Pola | Contoh |
|---|---|---|
| Shopee Express | `SPXID…` | SPXID06916034017A |
| J&T Express | `JY` + 10 angka (12 karakter) | JY1810645074 |
| ID Express | `TKP` + 10 angka | TKP8023805494 |
| JNE | `CM`/`JY`/`JT` + 11 angka, `TG` + 10 angka | CM37832267208 |
| POS Indonesia | `SHPE` + 18 karakter, `TTSPOS…` | SHPE261001448DF0017145 |
| J&T Cargo | 12 angka berawalan `20` atau `57` | 201833058622 |
| Instan / Sameday | 6 angka tanggal + 8 karakter | 260930DDK8YJNB |
| Sentral Cargo, Indah Kargo, kurir lain | pilih manual di "Kurir" (atau "+ Kurir lain" untuk mengetik nama) | |

Pola-pola ini diambil dari file Pengiriman Harian Oktober 2026. Untuk mengubahnya, edit daftar `RULES` (pola kurir) dan `LEN_RULES` (jumlah karakter) di `index.html`.

## Penyimpanan data

Data tersimpan otomatis per tanggal di browser (localStorage) pada komputer yang dipakai untuk scan, setiap kali ada resi atau catatan yang berubah. Data juga disimpan otomatis setiap 5 menit, dan saat tab ditutup aplikasi menyimpan dulu lalu menampilkan peringatan. Tombol **Simpan sekarang** menyimpan manual dan memastikan datanya sudah tersimpan. Lewat tengah malam, aplikasi otomatis pindah ke tanggal baru. Kalau data browser dihapus, data ikut hilang. Karena itu, cadangkan data secara rutin lewat tombol "Cadangkan semua data". Gunakan "Buka file cadangan" untuk memulihkan data atau memindahkannya ke komputer lain.

## Penyimpanan di server (Hostinger)

Kalau `api.php` dan `.htaccess` diupload ke folder yang sama dengan `index.html` di hosting yang mendukung PHP (misalnya Hostinger), aplikasi otomatis menyimpan data ke server:

- Saat pertama dibuka, buat PIN (minimal 4 karakter). PIN yang sama dipakai di semua komputer dan HP.
- Beberapa komputer dan HP bisa scan bersamaan. Yang dikirim ke server hanya perubahannya (resi ditambah, dihapus, atau dipindah kurir, dan catatan), lalu server menggabungkannya, jadi resi dari perangkat lain tidak tertimpa. Resi dari perangkat lain muncul dalam sekitar 15 detik.
- Setiap perubahan dikirim ke server dalam beberapa detik, ditambah setiap 5 menit dan saat tab ditutup. Data di browser tetap disimpan sebagai cadangan kalau internet putus, lalu dikirim ulang otomatis.
- Data per tanggal ada di `data/hari/`, dan cadangan lengkap harian di `data/cadangan/` (disimpan 120 hari). Tombol **Cadangan di server** menampilkan dan mengunduh cadangan itu. Folder `data` ditutup dari akses web.
- PIN bisa diganti lewat tombol **Ganti PIN** (perlu PIN lama). Lupa PIN: hapus `data/config.json` lewat File Manager, lalu buat PIN baru dari aplikasi. Datanya tidak ikut terhapus.

## Cadangan file bulanan

Mulai tanggal 1 setiap bulan, aplikasi mengecek apakah komputer ini sudah membuat file cadangan (.json) di bulan berjalan. Kalau belum, muncul pop-up sebelum resi baru diinput: OK mengunduh file cadangan, Batal melanjutkan tanpa cadangan dan pop-up muncul lagi besok.
