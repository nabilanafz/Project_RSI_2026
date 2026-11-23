<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> · FILKOM Connect</title><link rel="stylesheet" href="assets/app.css"><script src="assets/app.js" defer></script>
<?php if($currentUser && in_array(query('r'),['lamaran','notifikasi'],true)): ?><meta http-equiv="refresh" content="<?= APP['refresh_seconds'] ?>"><?php endif; ?>
</head><body>
<a class="skip-link" href="#main">Lewati navigasi</a>
<?php if($currentUser): $role=$currentUser['role'];$r=query('r','dashboard'); ?>
<header class="topbar"><a class="brand" href="<?= e(url()) ?>"><span class="brand-icon">F<span>•</span></span><span><strong>FILKOM <span class="brand-light">UB</span></strong><small>Magang &amp; Paruh Waktu Terpusat</small></span></a>
<form class="global-search" action="index.php" method="get"><input type="hidden" name="r" value="<?= $role==='Admin'?'perusahaan':'lowongan' ?>"><?= icon('search') ?><input aria-label="Pencarian global" name="keyword" placeholder="Cari lowongan, perusahaan, atau kata kunci…" value="<?= e(query('keyword')) ?>"><button class="icon-button" aria-label="Cari"><?= icon('arrow') ?></button></form>
<div class="top-actions"><form class="mobile-logout" method="post" action="<?= e(url('logout')) ?>"><?= csrf() ?><button class="icon-button" aria-label="Keluar"><?= icon('logout') ?></button></form><a class="notification-button" href="<?= e(url('notifikasi')) ?>" aria-label="Notifikasi, <?= $unread ?> belum dibaca"><?= icon('bell') ?><?php if($unread):?><span class="counter"><?= $unread>99?'99+':$unread ?></span><?php endif;?></a><div class="user-summary"><span class="avatar"><?= e(initials($currentUser['namaPengguna'])) ?></span><span><strong><?= e(explode(' ',$currentUser['namaPengguna'])[0]) ?></strong><small><?= e($role) ?></small></span></div></div></header>
<aside class="sidebar"><div class="sidebar-label">RUANG KERJA</div><nav aria-label="Navigasi utama">
<?php $nav=[['dashboard','home','Beranda']];
if($role==='Mahasiswa')$nav=[...$nav,['lowongan','briefcase','Lowongan'],['lamaran','file','Lamaran'],['chat','chat','Percakapan']];
if($role==='Rekruter')$nav=[...$nav,['lowongan','briefcase','Lowongan'],['pendaftar','user','Pendaftar'],['chat','chat','Percakapan']];
if($role==='Admin')$nav=[...$nav,['perusahaan','building','Perusahaan']];
$nav[]=['notifikasi','bell','Notifikasi'];$nav[]=['profil','user','Profil'];
if($role!=='Admin')$nav[]=['kalender','calendar','Kalender'];
if($role!=='Mahasiswa')$nav[]=['rekapitulasi','chart','Rekapitulasi'];
foreach($nav as [$link,$ic,$label]):$active=explode('/',$r)[0]===$link||($link==='pendaftar'&&str_starts_with($r,'seleksi')); ?>
<a href="<?= e(url($link)) ?>" class="nav-link <?= $active?'active':'' ?>" <?= $active?'aria-current="page"':'' ?>><?= icon($ic) ?><span><?= e($label) ?></span><?php if($link==='notifikasi'&&$unread):?><span class="nav-count"><?= $unread ?></span><?php endif;?></a>
<?php endforeach;?></nav><div class="sidebar-bottom"><div class="portal-status"><i></i> Portal terpusat aktif<small>Fakultas Ilmu Komputer<br>Universitas Brawijaya</small></div><form method="post" action="<?= e(url('logout')) ?>"><?= csrf() ?><button class="logout"><?= icon('logout') ?> Keluar</button></form></div></aside>
<main id="main" class="main-content">
<?php if($role==='Rekruter'&&$currentUser['statusVerifikasi']!=='Terverifikasi'):?><div class="alert amber"><?= icon('shield') ?><span>Perusahaan berstatus <strong><?= e($currentUser['statusVerifikasi']) ?></strong>. Lengkapi legalitas di <a href="<?= e(url('profil')) ?>">Profil</a>; publikasi menunggu persetujuan admin.</span></div><?php endif; ?>
<?php else: ?><main id="main" class="public-main"><?php endif; ?>
<?php foreach($_SESSION['flash']??[] as $f):if(in_array($f['type'],['error','danger'],true))continue;?><div class="alert <?= e($f['type']) ?>" role="status"><?= icon('check') ?><span><?= e($f['message']) ?></span></div><?php endforeach;unset($_SESSION['flash']); ?>
