# Panduan pengguna

Quality MS menyimpan semua rekod kualiti anda di satu tempat: sijil pembekal, pemeriksaan, penentukuran, ketakakuran, tindakan pembetulan, dokumen terkawal dan audit dalaman. Setiap bahagian dipautkan ke bahagian seterusnya, jadi anda boleh menjejak mana-mana lot daripada sijil yang dibawanya semasa tiba hingga tindakan pembetulan yang menyelesaikan masalah.

[TOC]

## Bermula

### Log masuk dan akaun anda

Log masuk dengan e-mel dan kata laluan yang diberikan oleh pentadbir anda. Di bawah **Tetapan** (di bahagian bawah bar sisi, dalam menu pengguna anda) anda boleh menukar nama, e-mel dan kata laluan, serta mendayakan pengesahan dua faktor atau kunci laluan.

### Bahasa

Pilih **English**, **Bahasa Melayu** atau **中文** daripada menu pengguna anda. Pilihan anda disimpan pada akaun anda dan mengikut anda ke mana-mana peranti.

### Mencari jalan

Bar sisi mengumpulkan kerja mengikut kumpulan:

- **Masuk**: sijil, carian lot, pembekal dan bahan.
- **Kualiti**: pemeriksaan, pelan pemeriksaan, bahagian dan tolok.
- **Penambahbaikan**: ketakakuran, CAPA, audit dalaman dan dokumen.
- **Pentadbiran**: pengguna (pentadbir sahaja).

Anda hanya melihat halaman yang dibenarkan oleh peranan anda.

### Senarai

Setiap senarai berfungsi dengan cara yang sama. Taip dalam kotak carian untuk menapis, gunakan senarai juntai bawah dan tab status untuk mengecilkan senarai, klik tajuk lajur untuk mengisih, dan pilih bilangan baris yang hendak dipaparkan. Bar alamat menyimpan carian dan penapis anda, jadi anda boleh menanda buku atau berkongsi senarai yang telah ditapis. **Eksport** memuat turun senarai, mengikut penapis semasa, sebagai fail CSV yang boleh dibuka dalam Excel.

### Medan wajib

Tanda **\*** merah selepas nama medan bermaksud medan itu mesti diisi. Jika ada maklumat yang tertinggal atau salah, mesej akan muncul di bawah medan tersebut semasa anda menyimpan.

## Peranan

| Peranan | Apa yang mereka lakukan |
|---|---|
| Pentadbir | Semua perkara, termasuk mengurus pengguna dan peranan. |
| Pengurus Kualiti | Meluluskan dan membuat keputusan: mengesahkan sijil, meluluskan pelan dan dokumen, merekodkan penentukuran, meluluskan pelupusan NCR, menutup NCR dan CAPA. |
| Pemeriksa | Merekodkan kerja: sijil dan keputusannya, pemeriksaan, NCR dan tindakan CAPA. |
| Juruaudit | Membaca semua rekod dan menjalankan audit dalaman. |
| Pembaca | Membaca semua rekod. |

Pentadbir boleh menambah **peranan tersuai** di bawah **Pentadbiran → Peranan** dan menanda dengan tepat apa yang boleh dilakukan oleh setiap peranan. Peranan terbina dalam di atas adalah tetap.

## Papan pemuka

Papan pemuka menunjukkan perkara yang perlu diberi perhatian hari ini: NCR terbuka, CAPA tertunggak, tolok yang tiba tempoh atau tertunggak penentukuran, sijil yang menunggu pengesahan, pemeriksaan sedang berjalan, dokumen yang perlu dikaji semula, dan dokumen yang anda diminta baca. Klik pada nombor untuk membuka senarai tersebut.

Di bawah nombor-nombor itu:

- **Ketakakuran mengikut sumber** (90 hari lepas), yang terbesar dahulu. Membetulkan satu atau dua sumber teratas biasanya menghapuskan kebanyakan masalah.
- **Bukti ISO 9001** menyenaraikan setiap klausa bersama dokumen dan rekod yang meliputinya. Klausa yang ditanda **Jurang** tidak mempunyai kedua-duanya; semak klausa itu sebelum audit.

## Bahan masuk

### Pembekal

Tambah setiap pembekal dengan kod dan nama. Tandakan **Pembekal diluluskan** dan masukkan tarikh kelulusan setelah pembekal diluluskan. Sijil daripada pembekal yang belum diluluskan tidak boleh disahkan.

Senarai ini juga menunjukkan prestasi setiap pembekal: peratusan sijil mereka yang diterima, dan bilangan NCR yang dibangkitkan terhadap mereka dalam 12 bulan lepas.

### Bahan dan had spesifikasi

Bahan ialah sesuatu yang anda beli mengikut spesifikasi, contohnya *keluli S355JR mengikut EN 10025-2* atau *resin PA66-GF30*. Buka bahan untuk menambah **had spesifikasi**: satu baris bagi setiap sifat (C, Yield, Moisture…) dengan nilai minimum, maksimum atau kedua-duanya. Nilai yang tepat pada had dikira lulus.

Jika had bergantung pada saiz (contohnya kekuatan alah keluli mengikut ketebalan plat), isi **Dimensi saiz** pada bahan, kemudian berikan julat saiz bagi setiap had. Julat bagi sifat yang sama tidak boleh bertindih.

### Sijil

Apabila bahan tiba, tambah sijilnya:

1. Pergi ke **Sijil** dan pilih **Tambah sijil**. Pilih pembekal, masukkan nombor sijil, jenis dan tarikh keluaran, dan lampirkan PDF atau imbasan.
2. Pada halaman sijil, tambah setiap **lot** (nombor leburan, nombor kelompok atau kod tarikh) bersama bahan, saiz dan kuantitinya.
3. Pilih **Masukkan keputusan** bagi setiap lot dan salin nilai daripada sijil. Biarkan kotak kosong jika nilai tidak dilaporkan.

Halaman ini menyemak setiap keputusan berbanding had bahan dan menyenaraikan apa-apa yang menghalang pengesahan: nilai di luar had, keputusan yang tiada, pembekal yang belum diluluskan, atau sijil 3.2 tanpa pemeriksa pihak ketiga.

- **EN 10204 2.2, 3.1, 3.2 dan sijil analisis** melaporkan keputusan ujian, jadi setiap sifat yang mempunyai had memerlukan keputusan.
- **EN 10204 2.1 dan sijil pematuhan** hanya mengisytiharkan pematuhan; ia memerlukan lot tetapi tidak memerlukan keputusan.

Pengurus kualiti kemudian memilih **Sahkan** (apabila senarai semak sudah lengkap) atau **Tolak** dengan sebab. Kedua-duanya memerlukan tandatangan elektronik. Sijil yang ditolak akan membuka NCR secara automatik. Sijil yang telah diputuskan tidak boleh diubah lagi.

### Membaca fail sijil secara automatik

Jika pentadbir anda telah mendayakannya, **Baca daripada fail** membaca PDF atau imbasan yang dilampirkan dan mengisi lot serta keputusan untuk anda. Tiada apa-apa disimpan sehingga anda menyemaknya:

1. Pilih **Baca daripada fail** dan tunggu beberapa saat.
2. Bandingkan setiap nilai dengan sijil dan betulkan apa-apa yang salah. Baca nota; nota itu menunjukkan apa-apa yang kurang jelas.
3. Pilih bahan bagi lot baharu, kemudian **Gunakan keputusan**.

Sentiasa semak nilai berbanding sijil sebelum anda mengesahkannya.

### Carian lot

Taip nombor leburan, nombor kelompok, sijil atau pembekal untuk mencari lot dan sijil yang dibawanya semasa tiba.

## Penentukuran

### Tolok

Setiap alat pengukur ialah tolok dengan kod, selang penentukuran dan, sebaik-baiknya, pemilik. Statusnya ditentukan daripada tarikh akhirnya:

| Status | Maksud | Boleh digunakan? |
|---|---|---|
| Ditentukur | Tarikh akhir lebih daripada 14 hari lagi | Ya |
| Tiba tempoh | Perlu ditentukur dalam 14 hari | Ya, sehingga tarikh akhir |
| Tertunggak | Melepasi tarikh akhir, atau tidak pernah ditentukur | Tidak |
| Luar Servis | Gagal penentukuran atau dikeluarkan daripada penggunaan | Tidak |
| Ditamatkan | Tidak lagi digunakan | Tidak |

Pemilik tolok menerima e-mel peringatan setiap pagi apabila tolok mereka tiba tempoh atau tertunggak.

### Merekodkan penentukuran

Buka tolok dan pilih **Rekodkan penentukuran**. Masukkan tarikh, siapa yang menentukurnya dan keputusannya, dan lampirkan sijil penentukuran.

- **Lulus** menetapkan tarikh akhir seterusnya dan mengembalikan tolok ke servis.
- **Lulus selepas pelarasan** melakukan perkara yang sama; terangkan apa yang ditemui dan keadaan tolok selepas itu.
- **Gagal** mengeluarkan tolok daripada servis dan membuka NCR.

Rekod penentukuran tidak boleh disunting. Untuk membetulkannya, rekodkan penentukuran baharu.

### Apabila tolok gagal

Halaman tolok menyenaraikan **pemeriksaan yang disyaki**: setiap pemeriksaan yang menggunakan tolok itu sejak penentukuran baik yang terakhir. Semak semula pemeriksaan itu; keputusannya mungkin salah. Senarai ini dikosongkan setelah tolok lulus penentukuran semula.

## Pemeriksaan

### Bahagian

Tambah bahagian yang anda hasilkan dengan nombor bahagian, semakan lukisan dan, jika diketahui, bahan yang digunakan untuk membuatnya.

### Pelan pemeriksaan

Pelan menyenaraikan perkara yang perlu diperiksa bagi sesuatu bahagian atau bahan yang dibeli pada satu peringkat (penerimaan, dalam proses atau akhir).

1. Pilih **Tambah pelan**, pilih bahagian atau bahan, peringkat dan tajuk.
2. Tambah **ciri-ciri**: nilai terukur dengan hadnya (contohnya 9.95 hingga 10.05 mm), atau semakan OK / tidak OK. Tetapkan bilangan sampel, dan tandakan ciri kritikal.
3. Pengurus kualiti meluluskan pelan dengan tandatangan elektronik.

Pelan yang diluluskan dikunci supaya pemeriksaan lepas mengekalkan pelan tepat yang digunakan. Untuk mengubahnya, pilih **Semakan baharu**; meluluskan semakan baharu menjadikan semakan lama usang.

### Menjalankan pemeriksaan

1. Pilih **Mulakan pemeriksaan**, pilih pelan yang diluluskan dan, bagi pemeriksaan penerimaan, lot tersebut (hanya lot daripada sijil yang disahkan dan belum tamat tempoh ditawarkan).
2. Masukkan setiap bacaan. Nilai terukur memerlukan tolok yang anda gunakan; hanya tolok yang masih dalam tempoh penentukuran ditawarkan.
3. **Simpan bacaan** sambil anda bekerja. Apabila semuanya telah dimasukkan, pilih **Selesaikan pemeriksaan** dan tandatangan.

Jika mana-mana bacaan di luar spesifikasi, pemeriksaan gagal dan NCR dibuka secara automatik. Ciri kritikal yang gagal menjadikan NCR itu major.

## Ketakakuran (NCR)

NCR dibuka secara automatik sebagai draf apabila pemeriksaan gagal, sijil ditolak, penentukuran gagal, atau audit dalaman menemui ketakakuran. Tambah aduan pelanggan, masalah pembekal dan penemuan lain dengan **Tambah NCR**.

1. **Draf**: semak butiran dan terangkan masalah, kemudian pilih **Buka NCR**. Draf yang dibangkitkan secara silap boleh dibatalkan dengan sebab.
2. **Buka**: tentukan apa yang berlaku kepada bahan yang terjejas (guna seadanya, kerja semula, pembaikan, skrap atau pulangkan kepada pembekal) dan simpan pelupusan. "Guna seadanya" memerlukan justifikasi.
3. Pengurus kualiti **meluluskan pelupusan** dengan tandatangan elektronik.
4. Pengurus kualiti **menutup** NCR. NCR tidak boleh ditutup selagi CAPA yang dipautkan masih terbuka.

Jika punca perlu dibetulkan, bukan sekadar bahan ini, pilih **Mulakan CAPA**. Gunakan **Cetak** untuk laporan yang boleh dicetak; pelayar anda boleh menyimpannya sebagai PDF.

## Tindakan pembetulan (CAPA, 8D)

CAPA dijalankan melalui lapan disiplin:

| Peringkat | Sebelum meneruskan, isi |
|---|---|
| Buka | D1 Pasukan, D2 Penerangan masalah |
| Sedang Disiasat | D3 Pembendungan, D4 Punca utama, D5 Tindakan pembetulan dipilih |
| Sedang Dilaksanakan | D6 Pelaksanaan, dan tandakan setiap tindakan sebagai selesai |
| Sedang Disahkan | Semakan keberkesanan, D7 Cegah berulang, D8 Penutupan dan pengiktirafan |
| Ditutup | Tiada lagi; CAPA dikunci |

- Tambah **tindakan** dengan pemilik dan tarikh akhir, dan tandakan apabila selesai.
- Pautkan NCR lain jika masalah yang sama terus berulang.
- Dalam D7, **ubah dokumen terkawal** memulakan semakan draf baharu bagi prosedur yang perlu diubah, dipautkan kembali kepada CAPA.
- Pengurus kualiti mengesahkan keberkesanan ("masalah tidak berulang") dan menutup CAPA, kedua-duanya dengan tandatangan elektronik.

**Laporan 8D** memberikan versi yang boleh dicetak.

## Dokumen

Dokumen terkawal (polisi, prosedur, arahan kerja, borang) disimpan di bawah **Dokumen**.

1. **Tambah dokumen** menciptanya dengan semakan draf A. Muat naik fail (PDF, Word, Excel atau imbasan) dan terangkan apa yang berubah.
2. **Hantar untuk kajian semula**.
3. Seseorang selain penulis **meluluskan** dokumen itu dengan tandatangan elektronik. Ia berkuat kuasa serta-merta dan menggantikan semakan sebelumnya.
4. Pilih siapa yang mesti membacanya. Mereka akan melihatnya dalam **Senarai bacaan saya** dan mengesahkan setelah membacanya.

Setiap dokumen mempunyai selang kajian semula. Apabila kajian semula tiba tempoh, pemiliknya menerima peringatan; pilih **Sahkan kajian semula** jika tiada apa-apa perlu diubah, atau **Semakan baharu** jika perlu.

Tag dokumen dengan klausa ISO 9001 yang diliputinya; papan pemuka menggunakan tag tersebut.

## Audit dalaman

1. **Rancang audit**: tajuk, skop, ketua juruaudit, tarikh dan klausa ISO dalam skop.
2. **Mulakan audit** pada hari tersebut.
3. Rekodkan **penemuan**: ketakakuran major atau minor, pemerhatian dan peluang penambahbaikan. Setiap ketakakuran terus membuka NCR. Penemuan tidak boleh diubah selepas itu.
4. Tulis ringkasan dan **Selesaikan audit**.

## Tandatangan elektronik

Kelulusan, pengesahan, penolakan, penutupan dan pengesahan akhir pemeriksaan meminta kata laluan anda sekali lagi. Nama anda, masa dan perkara yang anda tandatangani disimpan bersama rekod dan dipaparkan padanya, dan tidak boleh diubah atau dipadam. Jangan sekali-kali berkongsi kata laluan anda; tandatangan atas nama anda bermaksud anda yang membuat keputusan itu.

## Jejak audit

Setiap perubahan pada rekod kualiti dilog bersama siapa yang membuatnya, bila, dan nilai sebelum dan selepas. Tiada sesiapa boleh menyunting atau memadam log ini. Setiap halaman rekod memaparkan **Sejarah**nya di bahagian bawah. Rekod yang penting untuk audit (penentukuran, penemuan, tandatangan) juga tidak boleh dipadam; ia dibetulkan dengan menambah rekod baharu.

## Soalan

**Mengapa saya tidak dapat melihat halaman atau butang?** Peranan anda tidak merangkuminya. Tanya pentadbir anda.

**Mengapa Sahkan berwarna kelabu?** Senarai semak pada halaman sijil menyatakan apa yang masih tiada.

**Mengapa saya tidak dapat memilih tolok?** Hanya tolok yang masih dalam tempoh penentukuran ditawarkan. Semak status tolok di bawah **Tolok**.

**Mengapa saya tidak dapat mengubah pelan yang diluluskan atau sijil yang disahkan?** Rekod yang diluluskan dan telah diputuskan dikunci supaya sejarahnya kekal boleh dipercayai. Cipta semakan baharu, atau bangkitkan NCR.
