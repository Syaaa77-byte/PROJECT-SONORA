# Sonora - Web Rating Lagu

Platform rating dan ulasan lagu dengan tampilan editorial modern, mode Terang & Gelap, leaderboard Top Rated, filter genre & urutan, detail distribusi bintang, serta dukungan Login Manual & Login via Google.

## Menjalankan proyek
1. Jalankan Apache dan MySQL melalui XAMPP (`C:\NEW`).
2. Import `Backend/database.sql` melalui phpMyAdmin atau MySQL CLI.
3. Jalankan server PHP dari root project (`php -S localhost:8000`) lalu buka `http://localhost:8000/Frontend/`.

## Akun demo
- Email: `admin@sonora.test`
- Password: `password`

Akun demo dapat mengelola CRUD lagu. Pengguna biasa dapat mendaftar via email atau masuk dengan Google untuk memberi rating bintang dan menulis ulasan.

## Mengaktifkan Login via Google (Google Identity Services)
1. Buka [Google Cloud Console](https://console.cloud.google.com/apis/credentials) dan buat **OAuth 2.0 Client ID** bertipe **Web application**.
2. Pada bagian **Authorized JavaScript origins**, tambahkan:
   - `http://localhost:8000`
   - `http://localhost`
3. Salin **Client ID** yang dibuat (contoh: `1234567890-abcdefg.apps.googleusercontent.com`).
4. Tempel ke konstanta `GOOGLE_CLIENT_ID` di `Backend/config.php`.

## Katalog lagu
Katalog awal disinkronkan dari iTunes Search API ke tabel `songs` jika database lagu masih kosong. Admin juga dapat mencari rekomendasi otomatis saat menambahkan lagu baru.
