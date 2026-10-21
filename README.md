# laporan-pengiriman-harian

Laporan Pengiriman Harian SAB Online. Aplikasi web satu halaman untuk mencatat resi pengiriman yang discan setiap hari.

## Cara pakai

Buka `index.html` di browser (Chrome atau Edge). Aplikasi ini tidak butuh server. Koneksi internet hanya dipakai untuk memuat font dan pustaka PDF/Excel.

1. Arahkan kursor ke kotak kuning, lalu scan resi. Scanner biasanya otomatis menekan Enter. Di HP, tekan **Scan pakai kamera** lalu arahkan kamera ke barcode resi, atau pakai **Foto barcode**.
2. Resi otomatis masuk ke kelompok kurirnya, lengkap dengan jumlah per kurir dan total.
3. Isi catatan pendingan (Pesanan Shopee, Pendingan, PO, dan catatan bebas).
4. Cetak laporan PDF harian atau rekap sebulan, atau unduh Excel dengan format yang sama seperti file "Pengiriman Harian".

## Fitur

- Deteksi kurir otomatis dari pola nomor resi. Resi yang tidak dikenali masuk ke "Lainnya" dan bisa dipindah ke kurir lain secara manual.
- Resi yang discan dua kali di hari yang sama ditolak. Ada peringatan kalau resi pernah discan di tanggal lain.
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
| J&T Express | `JY` + 10 angka | JY1843706064 |
| ID Express | `TKP` + 10 angka | TKP8023805494 |
| JNE | `CM`/`JY`/`JT` + 11 angka, `TG` + 10 angka | CM37832267208 |
| POS Indonesia | `SHPE` + 18 karakter, `TTSPOS…` | SHPE261001448DF0017145 |
| J&T Cargo | 12 angka berawalan `20` atau `57` | 201833058622 |
| Instan / Sameday | 6 angka tanggal + 8 karakter | 260930DDK8YJNB |

Pola-pola ini diambil dari file Pengiriman Harian Oktober 2026. Untuk mengubahnya, edit daftar `RULES` di `index.html`.

## Penyimpanan data

Data tersimpan di browser (localStorage) pada komputer yang dipakai untuk scan. Kalau data browser dihapus, data ikut hilang. Karena itu, cadangkan data secara rutin lewat tombol "Cadangkan semua data". Gunakan "Buka file cadangan" untuk memulihkan data atau memindahkannya ke komputer lain.
