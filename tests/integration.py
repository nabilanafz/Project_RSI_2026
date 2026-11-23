"""HTTP integration test against a disposable database initialized with both SQL files.
Python 3 standard library only. Never run against a database with real user data.
Usage: python tests/integration.py http://127.0.0.1:8082/ [--snapshots]
"""
import sys, re, json, html, urllib.request, urllib.parse, urllib.error, http.cookiejar, uuid
from pathlib import Path
from datetime import datetime, timedelta
BASE=sys.argv[1].rstrip('/')+'/' if len(sys.argv)>1 else 'http://127.0.0.1:8082/'
ROOT=Path(__file__).resolve().parents[1]
RESULTS=[]
SNAPSHOTS='--snapshots' in sys.argv
class Client:
 def __init__(self):
  self.jar=http.cookiejar.CookieJar();self.opener=urllib.request.build_opener(urllib.request.ProxyHandler({}),urllib.request.HTTPCookieProcessor(self.jar));self.token='';self.body='';self.url='';self.code=0;self.raw=b''
 def request(self,route,data=None,files=None,csrf=True):
  target=BASE+'index.php?'+urllib.parse.urlencode({'r':route}) if '?' not in route else BASE+route
  headers={};payload=None
  if data is not None:
   data=dict(data)
   if csrf:data['csrf']=self.token
   if files:
    boundary='----Test'+uuid.uuid4().hex;chunks=[]
    for k,v in data.items():
     for x in v if isinstance(v,list) else [v]:chunks.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{x}\r\n'.encode())
    for k,(name,blob,mime) in files.items():chunks.extend([f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"; filename="{name}"\r\nContent-Type: {mime}\r\n\r\n'.encode(),blob,b'\r\n'])
    chunks.append(f'--{boundary}--\r\n'.encode());payload=b''.join(chunks);headers['Content-Type']='multipart/form-data; boundary='+boundary
   else:payload=urllib.parse.urlencode(data,doseq=True).encode()
  try:r=self.opener.open(urllib.request.Request(target,data=payload,headers=headers),timeout=25)
  except urllib.error.HTTPError as e:r=e
  self.code=r.code;self.url=r.geturl();self.raw=r.read();self.body=self.raw.decode('utf-8',errors='replace');self.headers=r.headers
  match=re.search(r'name="csrf" value="([^"]+)"',self.body)
  if match:self.token=html.unescape(match.group(1))
  return self
 def get(self,route,**params):return self.request('index.php?'+urllib.parse.urlencode({'r':route,**params}))
 def post(self,route,**data):return self.request(route,data)
 def login(self,name):
  self.get('login');self.post('login/proses',username=name,password='Filkom2026!');return self
 def snapshot(self,name):
  if SNAPSHOTS:
   p=ROOT/'public'/('__qa_'+name+'.html');s=re.sub(r'<input type="hidden" name="csrf"[^>]*>','',self.body);p.write_text(s)
def check(label,ok,detail=''):
 RESULTS.append({'test':label,'passed':bool(ok),'detail':detail if not ok else ''})
 print(('PASS ' if ok else 'FAIL ')+label+((' | '+detail) if not ok else ''),flush=True)
def status(c,label,code=200,contains=None):
 check(label,(c.code==code or (code>=400 and c.code==200 and 'data-open-error' in c.body)) and (contains is None or contains in c.body),f'HTTP {c.code}; expected {code}; text {contains!r}')
def get_id(c,key='id'):
 return int(urllib.parse.parse_qs(urllib.parse.urlparse(c.url).query).get(key,[0])[0])

def run():
 anon=Client().get('dashboard');check('Anonim diarahkan ke login','r=login' in anon.url)
 status(anon.get('register'),'Tidak ada registrasi',404)
 anon.get('login');status(anon.request('login/proses',{'username':'mahasiswa','password':'wrong'},csrf=False),'POST tanpa CSRF ditolak',419)
 anon.get('login');status(anon.post('login/proses',username="' OR 1=1 --",password='wrong'),'Injeksi login tidak masuk',422)
 m=Client().login('mahasiswa');status(m,'Login mahasiswa dari database',contains='Halo, Nadia.')
 r=Client().login('rekruter');status(r,'Login rekruter dari database',contains='Halo, Alya.')
 a=Client().login('admin');status(a,'Login admin dari database',contains='Halo, Admin.')
 m2=Client().login('mahasiswa2');r2=Client().login('rekruter2');r3=Client().login('rekruter3')
 for c,prefix,routes in [(m,'Mahasiswa',['dashboard','lowongan','lamaran','profil','chat','kalender','notifikasi']),(r,'Rekruter',['dashboard','lowongan','pendaftar','profil','chat','kalender','notifikasi','rekapitulasi']),(a,'Admin',['dashboard','perusahaan','profil','notifikasi','rekapitulasi'])]:
  for route in routes:
   status(c.get(route),prefix+' halaman '+route)
   if prefix=='Mahasiswa':c.snapshot(route)
 for route in ['perusahaan','pendaftar','lowongan/form','rekapitulasi']:status(m.get(route),'Mahasiswa dilarang '+route,403)
 status(m2.get('lamaran/detail',id=1),'Mahasiswa tidak melihat lamaran orang lain',403)
 status(r3.get('seleksi/detail',id=5),'Rekruter tidak melihat kandidat perusahaan lain',403)
 status(m.get('dokumen/lihat',id=3),'Dokumen mahasiswa lain ditolak',403)
 status(r3.get('dokumen/lihat',id=3),'Dokumen kandidat perusahaan lain ditolak',403)
 m.get('dokumen/lihat',id=1);check('Dokumen pribadi diunduh sebagai PDF',m.code==200 and m.raw.startswith(b'%PDF-'))
 status(m.get('lowongan',keyword='data analyst'),'Pencarian tanpa peka kapital',contains='Data Analyst Intern')
 m.get('lowongan',keyword='tidak-ada-xyz');check('Pencarian kosong menampilkan pesan','Lowongan tidak ditemukan' in m.body)
 m.get('lowongan',keyword='');check('Kata kunci kosong menampilkan seluruh lowongan','Data Analyst Intern' in m.body and 'Frontend Developer Intern' in m.body)
 m.get('lowongan',jenis='Paruh Waktu',semester=5,jurusan='Sistem Informasi',lokasi='Malang',mode='WFH');check('Semua filter bekerja bersamaan','UI/UX Research Assistant' in m.body and 'Data Analyst Intern' not in m.body)
 status(m.get('lowongan/detail',id=6),'Draf perusahaan belum lolos tidak tampil',404)
 status(m.get('lowongan/detail',id=1),'Detail memuat persyaratan dan roadmap',contains='Seleksi Administrasi');m.snapshot('detail_lowongan')
 status(m.get('lamaran/form',id=1),'Form pendaftaran sesuai lowongan',contains='Pendaftaran Lowongan');m.snapshot('form_lamaran')
 status(m.post('lamaran/kirim',idLowongan=1,aksiForm='kirim',persetujuan=1),'Berkas wajib divalidasi',422,'Berkas Wajib Belum Lengkap')
 status(m.get('chat',id=5),'Chat bukan milik mahasiswa ditolak',403)
 status(m2.get('chat',id=5),'Chat sebelum lolos administrasi ditolak',403)
 pdf=(ROOT/'storage/uploads/demo_01.pdf').read_bytes()
 m.get('lamaran/form',id=1)
 status(m.request('lamaran/kirim',{'idLowongan':1,'aksiForm':'unggah_otomatis'}, {'dokumen[CV]':('bad.php',b'<?php echo 1;','application/x-php')}),'Unggahan non-PDF ditolak',422)
 status(m.request('lamaran/kirim',{'idLowongan':1,'aksiForm':'unggah_otomatis'},{'dokumen[CV]':('fake.pdf',b'hello, not pdf','application/pdf')}),'PDF palsu ditolak dari MIME',422)
 status(m.request('lamaran/kirim',{'idLowongan':1,'aksiForm':'unggah_otomatis'},{'dokumen[CV]':('large.pdf',pdf+b'0'*(5*1024*1024),'application/pdf')}),'PDF melebihi 5 MB ditolak',422)
 status(m.request('lamaran/kirim',{'idLowongan':1,'aksiForm':'unggah_otomatis','suratPengantar':'Saya ingin belajar analisis data.'},{'dokumen[CV]':('CV_test.pdf',pdf,'application/pdf'),'dokumen[Transkrip]':('Transkrip_test.pdf',pdf,'application/pdf')}),'Unggah otomatis tanpa membuat lamaran',contains='Terunggah')
 status(m.post('lamaran/kirim',idLowongan=1,aksiForm='kirim',persetujuan=1,suratPengantar='Saya ingin belajar analisis data.'),'Lamaran terhubung dan terkirim',contains='Lamaran Terkirim')
 app=get_id(m);check('Lamaran memiliki identitas tersimpan',app>5);m.snapshot('detail_lamaran')
 status(m.get('lamaran/form',id=1),'Pendaftaran ganda diblokir',contains='Anda sudah mendaftar')
 status(r.get('pendaftar',lowongan=1),'Pendaftar muncul pada rekruter',contains='Nadia Putri')
 status(r.get('seleksi/detail',id=app),'Rekruter membuka profil dan berkas',contains='CV_test.pdf')
 status(m.get('chat',id=app),'Chat terkunci sebelum administrasi',403)
 r.get('seleksi/detail',id=app);status(r.post('seleksi/status',idLamaran=app,idTahap=1,hasil='Lolos',catatan='Administrasi lengkap.'),'Lolos administrasi memindahkan tahap',contains='Diproses')
 status(m.get('chat',id=app),'Chat terbuka setelah administrasi',contains='Pesan baru')
 status(m.post('chat/kirim',idLamaran=app,isi='Terima kasih. <script>alert(1)</script>'),'Pesan mahasiswa tersimpan aman',contains='&lt;script&gt;alert(1)&lt;/script&gt;')
 status(r.get('chat',id=app),'Pesan terlihat pada rekruter',contains='Terima kasih.')
 status(r.post('chat/kirim',idLamaran=app,isi='Silakan siapkan tes teknis.'),'Balasan rekruter tersimpan',contains='Silakan siapkan tes teknis.')
 status(m.get('chat',id=app),'Balasan terlihat pada mahasiswa',contains='Silakan siapkan tes teknis.')
 status(r.post('seleksi/status',idLamaran=app,idTahap=1,hasil='Lolos',catatan='ulang'),'Penilaian tahap kedaluwarsa ditolak',422)
 status(r.post('seleksi/jadwal',idLamaran=app,idTahap=2,judul='Tes teknis',waktu='2020-01-01T10:00'),'Jadwal masa lalu ditolak',422)
 future=(datetime.now()+timedelta(days=3)).strftime('%Y-%m-%dT10:00')
 status(r.post('seleksi/jadwal',idLamaran=app,idTahap=2,judul='Tes teknis integrasi',waktu=future,lokasi='Ruang simulasi',tautan='',catatan='Bawa catatan.'),'Jadwal disimpan rekruter',contains='Jadwal tersimpan')
 status(m.get('kalender',bulan=future[:7]),'Jadwal terhubung ke kalender mahasiswa',contains='Tes teknis integrasi')
 status(m.get('notifikasi'),'Notifikasi pembaruan jadwal terkirim',contains='Jadwal seleksi baru')
 status(m.post('notifikasi/baca',id=0),'Tandai semua notifikasi dibaca')
 check('Jumlah notifikasi belum dibaca menjadi nol','Notifikasi, 0 belum dibaca' in m.body)
 past=(datetime.now()-timedelta(days=1)).strftime('%Y-%m-%dT10:00')
 status(m.post('kalender/simpan',judul='Pengingat integrasi',waktu=past,catatan='Belajar SQL',idLamaran=app),'Pengingat pribadi tersimpan',contains='Pengingat integrasi')
 status(m.get('notifikasi'),'Pengingat jatuh tempo menjadi notifikasi',contains='Pengingat: Pengingat integrasi')
 m.get('kalender');rem=int(re.search(r'edit=(\d+)',m.body).group(1))
 status(m2.post('kalender/simpan',idPengingat=rem,judul='ubah',waktu=future),'Pengingat pengguna lain tidak dapat diubah',403)
 status(m.post('kalender/simpan',idPengingat=rem,judul='Pengingat revisi',waktu=future,catatan='Revisi',idLamaran=app),'Pengingat dapat diubah',contains='Pengingat revisi')
 status(m.post('kalender/hapus',id=rem),'Hapus pengingat memerlukan konfirmasi',contains='Hapus pengingat?')
 status(m.post('kalender/hapus',id=rem,konfirmasi=1),'Pengingat dihapus setelah konfirmasi',contains='Pengingat dihapus')
 status(r.post('seleksi/status',idLamaran=app,idTahap=2,hasil='Lolos',catatan='Tes baik.'),'Lolos tes memindahkan ke wawancara',contains='Wawancara')
 status(r.post('seleksi/status',idLamaran=app,idTahap=3,hasil='Lolos',catatan='Diterima.'),'Tahap terakhir menjadi Diterima',contains='Diterima')
 status(m.get('lamaran/detail',id=app),'Hasil akhir tersinkron mahasiswa',contains='Diterima')
 status(r.post('seleksi/status',idLamaran=app,idTahap=3,hasil='Tidak Lolos'),'Status final tidak ditimpa',422)
 # A new job is created from the same complete server-side form used by the UI.
 job={'idLowongan':0,'namaLowongan':'QA Integration Intern','jenisPekerjaan':'Magang','modeKerja':'WFH','lokasiPenempatan':'Malang','deskripsi':'Deskripsi posisi untuk pengujian integrasi.','kualifikasi':'HTML CSS PHP','batasLamaran':(datetime.now()+timedelta(days=30)).strftime('%Y-%m-%d'),'tingkatSemesterMinimal':3,'kuota':2,'durasiBulan':3,'uangSaku':1000000,'konversiSKS':10,'targetJurusan[]':['Sistem Informasi','Teknik Informatika'],'dokumenPersyaratan[]':['CV','Transkrip'],'tahap[0][namaTahap]':'Seleksi Administrasi','tahap[0][keterangan]':'Periksa berkas','tahap[1][namaTahap]':'Wawancara','tahap[1][keterangan]':'Diskusi','aksiForm':'publikasi'}
 status(r.get('lowongan/form',baru=1),'Form lowongan rekruter');r.snapshot('form_lowongan')
 bad=dict(job);bad['namaLowongan']='';status(r.request('lowongan/simpan',bad),'Validasi kolom wajib lowongan',422)
 inspect=dict(job);inspect['aksiForm']='periksa';status(r.request('lowongan/simpan',inspect),'Aksi periksa manual tidak tersedia',422)
 cancel=dict(job);cancel['aksiForm']='batal';status(r.request('lowongan/simpan',cancel),'Pembatalan menampilkan konfirmasi',contains='Batalkan Pembuatan Lowongan?')
 status(r.post('lowongan/simpan',aksiForm='kembali'),'Kembali mengedit mempertahankan isian',contains='QA Integration Intern')
 status(r.post('lowongan/simpan',aksiForm='konfirmasi_batal'),'Konfirmasi pembatalan membuang formulir',contains='dibatalkan')
 status(r.request('lowongan/simpan',job),'Buat lowongan dan roadmap terintegrasi',contains='Lowongan berhasil dipublikasikan')
 m.get('lowongan',keyword='QA Integration');newjob=int(re.search(r'r=lowongan%2Fdetail&amp;id=(\d+)',m.body).group(1));check('Lowongan baru langsung muncul pada mahasiswa',newjob>6)
 status(r3.get('lowongan/form',id=newjob,baru=1),'Edit lowongan perusahaan lain ditolak',403)
 r.get('lowongan/form',id=newjob,baru=1);check('Edit mengisi field dari database','value="QA Integration Intern"' in r.body and 'Deskripsi posisi untuk pengujian integrasi.' in r.body)
 job['idLowongan']=newjob;job['namaLowongan']='QA Integration Updated';status(r.request('lowongan/simpan',job),'Edit lowongan tersimpan',contains='QA Integration Updated')
 status(r.post('lowongan/status',id=newjob,aksi='tutup'),'Tutup lowongan menampilkan konfirmasi',contains='Tutup lowongan?')
 status(r.post('lowongan/status',id=newjob,aksi='tutup',konfirmasi=1),'Tutup lowongan tersimpan',contains='Ditutup')
 status(m.get('lowongan/detail',id=newjob),'Lowongan tutup tidak dapat dilamar',404)
 status(r.post('lowongan/status',id=newjob,aksi='hapus',konfirmasi=1),'Hapus lowongan hanya daftar aktif')
 r2.get('lowongan/form',baru=1);job['idLowongan']=0;status(r2.request('lowongan/simpan',job),'Perusahaan belum terverifikasi tidak dapat publikasi',403)
 draft=dict(job);draft['aksiForm']='draf';status(r2.request('lowongan/simpan',draft),'Perusahaan belum terverifikasi tidak dapat membuat draf',403)
 status(a.get('perusahaan/detail',id=2),'Detail perusahaan admin');a.snapshot('admin_perusahaan')
 status(a.post('perusahaan/keputusan',idPerusahaan=2,keputusan='setujui'),'Persetujuan tanpa pemeriksaan ditolak',422)
 status(a.post('perusahaan/dokumen',idPerusahaan=2,idDokumenPerusahaan=2,hasil='Sesuai'),'Endpoint pemeriksaan terpisah ditolak',422)
 status(a.post('perusahaan/verifikasi',idPerusahaan=2,hasil='Layak',catatan='Persyaratan lengkap.'),'Endpoint kesimpulan terpisah ditolak',422)
 a.get('perusahaan/detail',id=2);version=re.search(r'name="versi" value="([^"]+)"',a.body).group(1)
 status(a.request('perusahaan/keputusan',{'idPerusahaan':2,'keputusan':'setujui','alasan':'Disetujui untuk simulasi.','versi':version,'pemeriksaan[2][hasil]':'Sesuai','pemeriksaan[2][catatan]':'Dokumen sesuai.'}),'Pemeriksaan dan persetujuan disimpan bersama',contains='Terverifikasi')
 status(r2.get('notifikasi'),'Keputusan admin diterima rekruter',contains='Hasil verifikasi: Terverifikasi')
 job['namaLowongan']='Lowongan Perusahaan Disetujui';status(r2.request('lowongan/simpan',job),'Rekruter disetujui dapat mempublikasikan',contains='Lowongan berhasil dipublikasikan')
 status(a.get('rekapitulasi'),'Rekap admin memuat hasil terhubung',contains='Nadia Putri')
 a.get('rekapitulasi/ekspor');check('Ekspor CSV benar',a.code==200 and 'text/csv' in a.headers.get('Content-Type','') and 'Data Analyst Intern' in a.body)
 r3.get('rekapitulasi/ekspor');check('CSV rekruter hanya perusahaannya','Data Analyst Intern' not in r3.body and 'Data Engineer Intern' in r3.body)
 status(a.post('perusahaan/keputusan',idPerusahaan=2,keputusan='tolak',alasan='Dokumen perlu pembaruan.'),'Keputusan ulang pada perusahaan terverifikasi ditolak',422)
 status(a.post('perusahaan/hapus',idPerusahaan=2,alasan='Simulasi pelanggaran'),'Hapus perusahaan menampilkan konfirmasi',contains='Hapus perusahaan ini?')
 status(a.post('perusahaan/hapus',idPerusahaan=2,alasan='Simulasi pelanggaran',konfirmasi=1),'Penghapusan perusahaan terintegrasi',contains='Perusahaan dan lowongannya dihapus')
 r2.get('dashboard');check('Sesi rekruter perusahaan dihapus dicabut','r=login' in r2.url)
 m.get('lowongan');check('Lowongan perusahaan dihapus tidak tampil','Lowongan Perusahaan Disetujui' not in m.body)
 status(a.get('logout'),'Logout harus menggunakan POST',405)
 m.get('dashboard');status(m.post('logout'),'Logout mengakhiri sesi',contains='Selamat datang kembali')
 check('Sesi lama tidak bisa membuka halaman privat','r=login' in m.get('lamaran').url)
 # Profile CRUD / upload legalitas after main flows, using active company and isolated fixtures.
 m.login('mahasiswa')
 profile={'namaMahasiswa':'Nadia Putri','email':'nadia@student.example','jurusan':'Sistem Informasi','semester':5,'IPK':'3.76','telepon':'080000000011','portofolio':'','bio':'Profil integrasi terbaru.'}
 status(m.request('profil/simpan',profile),'Perubahan profil mahasiswa ditolak server',403)
 status(r3.request('profil/simpan',{'namaPerusahaan':'Aksara Data','emailPerusahaan':'hr@aksaradata.example','alamat':'Jakarta Selatan, DKI Jakarta','bidangUsaha':'Analitik & Data','kontak':'080000000003','deskripsi':'Perusahaan data.','website':'','namaDokumen':'Akta Pendirian'},{'legalitas':('Akta_demo.pdf',pdf,'application/pdf')}),'Simpan profil dan legalitas memicu verifikasi ulang',contains='Menunggu')
 status(a.get('notifikasi'),'Admin menerima notifikasi legalitas',contains='Dokumen perusahaan baru')
 r.get('lowongan');r.snapshot('rekruter_lowongan')
 m.get('lamaran');m.snapshot('lamaran_final')

try:run()
except Exception as exc:
 check('Test harness completes',False,repr(exc));import traceback;traceback.print_exc()
finally:
 out={'passed':sum(x['passed'] for x in RESULTS),'total':len(RESULTS),'results':RESULTS}
 print(json.dumps(out,ensure_ascii=False,indent=2))
 report=Path(__file__).resolve().parent/'result.json';report.write_text(json.dumps(out,ensure_ascii=False,indent=2))
 sys.exit(0 if all(x['passed'] for x in RESULTS) else 1)
