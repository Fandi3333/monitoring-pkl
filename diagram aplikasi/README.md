# Diagram Aplikasi Monitoring PKL

Folder ini berisi lima diagram Mermaid yang menggambarkan alur dan skema aplikasi Monitoring PKL.

## Membuka diagram di draw.io

1. Buka [draw.io / diagrams.net](https://app.diagrams.net/).
2. Buat diagram kosong, lalu pilih **Arrange > Insert > Mermaid** (pada beberapa versi tersedia melalui **Insert > Advanced > Mermaid**).
3. Buka salah satu berkas `.mmd` dengan editor teks, salin seluruh isinya, lalu tempelkan ke dialog Mermaid.
4. Pilih **Insert** untuk membuat diagram. Simpan hasilnya sebagai berkas `.drawio` bila ingin mengeditnya nanti.

## Isi folder

- `usecase-diagram.mmd` — fitur yang tersedia untuk Admin, Guru, dan Siswa.
- `usecase-diagram.graphml` — versi use case yang bisa dibuka langsung di yEd Graph Editor.
- `activity-diagram.mmd` — alur pencatatan presensi siswa, termasuk validasi lokasi.
- `sequence-diagram.mmd` — interaksi siswa, browser, aplikasi, penyimpanan foto, dan database saat presensi.
- `class-diagram.mmd` — model kelas konseptual yang mengikuti entitas database aplikasi, bukan deklarasi kelas PHP.
- `entity-relationship-diagram.mmd` — tabel, kunci, dan relasi pada skema database.

Diagram dibuat berdasarkan kode aplikasi dan skema `database/db_monitoring_pkl.sql`. Status presensi `luar_radius` tetap tersimpan sebagai presensi; aplikasi hanya menampilkan peringatan untuk status tersebut.

## Membuka use case di yEd Graph Editor

1. Buka yEd Graph Editor.
2. Pilih **File > Open...**.
3. Pilih `usecase-diagram.graphml` dari folder ini.
4. Diagram memakai aktor UML, use case berbentuk oval, batas sistem, dan garis relasi tanpa panah. Untuk mengatur ulang tampilannya, pilih **Layout > Hierarchical** atau geser elemen secara manual.
5. Untuk menyimpan hasil edit, pilih **File > Save As...** dan simpan sebagai `.graphml` atau format yEd yang diinginkan.
