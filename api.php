<?php
// Penyimpanan Laporan Pengiriman Harian di server (Hostinger).
// Data per tanggal di data/hari/YYYY-MM-DD.json, cadangan harian di data/cadangan/.
// Semua akses data butuh PIN. PIN dibuat sekali dari aplikasi dan bisa diganti lewat tombol Ganti PIN.
// Kalau lupa PIN, hapus file data/config.json lewat File Manager lalu buat PIN baru dari aplikasi.

date_default_timezone_set('Asia/Jakarta');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$DATA = __DIR__ . '/data';
$HARI = $DATA . '/hari';
$CADANGAN = $DATA . '/cadangan';
$SIMPAN_CADANGAN = 120; // hari

function out($code, $arr) { http_response_code($code); echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }
function bacaJson($f) { if (!is_file($f)) return null; $j = json_decode(file_get_contents($f), true); return is_array($j) ? $j : null; }
function tulis($f, $isi) {
  $tmp = $f . '.tmp' . getmypid();
  if (file_put_contents($tmp, $isi, LOCK_EX) === false) return false;
  return rename($tmp, $f);
}

foreach (array($DATA, $HARI, $CADANGAN) as $d) if (!is_dir($d)) @mkdir($d, 0750, true);
if (!is_dir($HARI) || !is_writable($HARI)) out(500, array('ok' => false, 'error' => 'Folder data tidak bisa ditulis di server.'));
if (!is_file($DATA . '/.htaccess')) {
  file_put_contents($DATA . '/.htaccess', "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nOrder allow,deny\nDeny from all\n</IfModule>\n");
}
if (!is_file($DATA . '/index.html')) file_put_contents($DATA . '/index.html', '');

$a = isset($_GET['a']) ? $_GET['a'] : '';
$post = $_SERVER['REQUEST_METHOD'] === 'POST';
$body = array();
if ($post) {
  $raw = file_get_contents('php://input');
  if (strlen($raw) > 8 * 1024 * 1024) out(413, array('ok' => false, 'error' => 'Data terlalu besar.'));
  $body = json_decode($raw, true);
  if (!is_array($body)) $body = array();
}
$config = bacaJson($DATA . '/config.json');
$pinSet = $config && !empty($config['pin']);

if ($a === 'status') out(200, array('ok' => true, 'app' => 'laporan-pengiriman-harian', 'pinSet' => $pinSet, 'waktu' => date('c')));

if ($a === 'setpin') {
  if (!$post) out(405, array('ok' => false));
  if ($pinSet) out(409, array('ok' => false, 'error' => 'PIN sudah dibuat sebelumnya.'));
  $pin = isset($body['pin']) ? (string)$body['pin'] : '';
  if (strlen($pin) < 4 || strlen($pin) > 32) out(400, array('ok' => false, 'error' => 'PIN minimal 4 karakter.'));
  tulis($DATA . '/config.json', json_encode(array('pin' => password_hash($pin, PASSWORD_DEFAULT), 'dibuat' => date('c'))));
  out(200, array('ok' => true));
}

// ---- semua aksi di bawah ini butuh PIN ----
if (!$pinSet) out(403, array('ok' => false, 'error' => 'PIN belum dibuat.', 'pinSet' => false));
$gagalF = $DATA . '/gagal.json';
$gagal = bacaJson($gagalF);
if (!$gagal) $gagal = array('n' => 0, 'sampai' => 0);
if ($gagal['sampai'] > time()) out(429, array('ok' => false, 'error' => 'Terlalu banyak PIN salah. Coba lagi dalam ' . ceil(($gagal['sampai'] - time()) / 60) . ' menit.'));
$pin = isset($_SERVER['HTTP_X_PIN']) ? (string)$_SERVER['HTTP_X_PIN'] : (isset($body['pin']) ? (string)$body['pin'] : '');
if (!password_verify($pin, $config['pin'])) {
  $gagal['n']++;
  if ($gagal['n'] >= 10) { $gagal = array('n' => 0, 'sampai' => time() + 15 * 60); }
  tulis($gagalF, json_encode($gagal));
  out(401, array('ok' => false, 'error' => 'PIN salah.'));
}
if ($gagal['n'] > 0) @unlink($gagalF);

function semuaHari($HARI) {
  $days = array();
  foreach (glob($HARI . '/*.json') as $f) {
    $k = basename($f, '.json');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $k)) continue;
    $d = bacaJson($f);
    if ($d) $days[$k] = $d;
  }
  ksort($days);
  return $days;
}
function teks($v, $max) { return mb_substr(is_scalar($v) ? (string)$v : '', 0, $max); }
function bersihkan($d) {
  if (!is_array($d) || !isset($d['items']) || !is_array($d['items'])) return null;
  $items = array(); $ada = array();
  foreach ($d['items'] as $it) {
    if (!is_array($it) || !isset($it['r'])) continue;
    $r = teks($it['r'], 60);
    if ($r === '' || isset($ada[$r])) continue;
    $ada[$r] = 1;
    $items[] = array('r' => $r, 'c' => teks(isset($it['c']) ? $it['c'] : '', 12), 't' => isset($it['t']) ? (int)$it['t'] : 0);
  }
  $hasil = array('items' => $items);
  foreach (array('pesanan', 'pending', 'po') as $f) $hasil[$f] = teks(isset($d[$f]) ? $d[$f] : '', 40);
  $hasil['catatan'] = teks(isset($d['catatan']) ? $d['catatan'] : '', 5000);
  if (!$items && $hasil['pesanan'] === '' && $hasil['pending'] === '' && $hasil['po'] === '' && $hasil['catatan'] === '') return null;
  return $hasil;
}

// Cek PIN saja, misalnya sebelum mengosongkan satu tanggal
if ($a === 'cek') out(200, array('ok' => true));

// Ganti PIN: PIN lama dicek di atas (X-PIN), PIN baru di body
if ($a === 'gantipin') {
  if (!$post) out(405, array('ok' => false));
  $baru = isset($body['baru']) ? (string)$body['baru'] : '';
  if (strlen($baru) < 4 || strlen($baru) > 32) out(400, array('ok' => false, 'error' => 'PIN baru minimal 4 karakter.'));
  $config['pin'] = password_hash($baru, PASSWORD_DEFAULT);
  $config['diganti'] = date('c');
  if (!tulis($DATA . '/config.json', json_encode($config))) out(500, array('ok' => false, 'error' => 'PIN tidak bisa disimpan.'));
  out(200, array('ok' => true));
}

if ($a === 'load') {
  // since: hanya kirim tanggal yang berubah sejak waktu itu; keys: semua tanggal yang ada (untuk deteksi tanggal yang dihapus)
  $since = isset($_GET['since']) ? (float)$_GET['since'] : 0;
  $sekarang = time();
  clearstatcache();
  $days = array(); $keys = array();
  foreach (glob($HARI . '/*.json') as $f) {
    $k = basename($f, '.json');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $k)) continue;
    $keys[] = $k;
    if (filemtime($f) >= $since) { $d = bacaJson($f); if ($d) $days[$k] = $d; }
  }
  out(200, array('ok' => true, 'days' => (object)$days, 'keys' => $keys, 'sekarang' => $sekarang));
}

if ($a === 'save') {
  if (!$post) out(405, array('ok' => false));
  // ops per tanggal: add (resi baru / kurir diganti), del (resi dihapus), set (catatan yang diubah).
  // Perubahan digabung ke data server, sehingga beberapa perangkat bisa scan bersamaan tanpa saling menimpa.
  $ops = isset($body['ops']) && is_array($body['ops']) ? $body['ops'] : array();
  if (isset($body['days']) && is_array($body['days'])) {
    // Format lama (seluruh isi tanggal): perlakukan sebagai tambahan saja, tidak pernah menghapus
    foreach ($body['days'] as $k => $d) if (is_array($d) && isset($d['items']) && is_array($d['items'])) {
      $set = array(); foreach (array('pesanan', 'pending', 'po', 'catatan') as $f) if (isset($d[$f]) && $d[$f] !== '') $set[$f] = $d[$f];
      $ops[$k] = array('add' => $d['items'], 'del' => array(), 'set' => $set);
    }
  }
  $kunci = fopen($DATA . '/.kunci', 'c');
  flock($kunci, LOCK_EX);
  $hasil = array();
  foreach ($ops as $k => $op) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $k) || !is_array($op)) continue;
    $f = $HARI . '/' . $k . '.json';
    $d = bacaJson($f);
    if (!$d) $d = array('items' => array(), 'pesanan' => '', 'pending' => '', 'po' => '', 'catatan' => '');
    $pos = array();
    foreach ($d['items'] as $i => $it) $pos[$it['r']] = $i;
    if (isset($op['add']) && is_array($op['add'])) foreach ($op['add'] as $it) {
      if (!is_array($it) || !isset($it['r'])) continue;
      $r = teks($it['r'], 60); if ($r === '') continue;
      $c = teks(isset($it['c']) ? $it['c'] : '', 12);
      if (isset($pos[$r])) $d['items'][$pos[$r]]['c'] = $c;
      else { $pos[$r] = count($d['items']); $d['items'][] = array('r' => $r, 'c' => $c, 't' => isset($it['t']) ? (int)$it['t'] : 0); }
    }
    if (isset($op['del']) && is_array($op['del'])) {
      $hapus = array(); foreach ($op['del'] as $r) if (is_scalar($r)) $hapus[(string)$r] = 1;
      $d['items'] = array_values(array_filter($d['items'], function ($it) use ($hapus) { return !isset($hapus[$it['r']]); }));
    }
    if (isset($op['set']) && is_array($op['set'])) foreach (array('pesanan', 'pending', 'po', 'catatan') as $fld) if (array_key_exists($fld, $op['set'])) $d[$fld] = $op['set'][$fld];
    usort($d['items'], function ($x, $y) { return $x['t'] - $y['t']; });
    $d = bersihkan($d);
    if ($d === null) { if (is_file($f)) unlink($f); }
    else if (!tulis($f, json_encode($d, JSON_UNESCAPED_UNICODE))) { flock($kunci, LOCK_UN); out(500, array('ok' => false, 'error' => 'Gagal menulis data ' . $k)); }
    $hasil[$k] = $d;
  }
  // Cadangan harian otomatis: salinan lengkap semua data, satu file per hari, diperbarui paling sering tiap 10 menit
  $cf = $CADANGAN . '/cadangan-' . date('Y-m-d') . '.json';
  if ($hasil && (!is_file($cf) || filemtime($cf) < time() - 600)) {
    $semua = semuaHari($HARI);
    tulis($cf, json_encode(array('app' => 'laporan-pengiriman-harian', 'versi' => 1, 'dibuat' => date('c'), 'days' => (object)$semua), JSON_UNESCAPED_UNICODE));
    $files = glob($CADANGAN . '/cadangan-*.json');
    sort($files);
    while (count($files) > $SIMPAN_CADANGAN) unlink(array_shift($files));
  }
  flock($kunci, LOCK_UN);
  out(200, array('ok' => true, 'days' => (object)$hasil, 'waktu' => date('c')));
}

if ($a === 'backups') {
  $list = array();
  foreach (glob($CADANGAN . '/cadangan-*.json') as $f) $list[] = array('nama' => basename($f), 'ukuran' => filesize($f), 'waktu' => date('c', filemtime($f)));
  usort($list, function ($x, $y) { return strcmp($y['nama'], $x['nama']); });
  out(200, array('ok' => true, 'cadangan' => $list));
}

if ($a === 'backup') {
  $n = isset($_GET['f']) ? $_GET['f'] : '';
  if (!preg_match('/^cadangan-\d{4}-\d{2}-\d{2}\.json$/', $n) || !is_file($CADANGAN . '/' . $n)) out(404, array('ok' => false, 'error' => 'Cadangan tidak ditemukan.'));
  readfile($CADANGAN . '/' . $n);
  exit;
}

out(400, array('ok' => false, 'error' => 'Aksi tidak dikenal.'));
