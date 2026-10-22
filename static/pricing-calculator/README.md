# Pricing & ROAS Calculator — Static

Versi standalone dari `/tools/pricing-calculator`.

## Cara menjalankan

Buka `index.html` langsung di browser, atau sajikan folder ini dengan static web server.

Contoh:

```bash
cd static/pricing-calculator
python3 -m http.server 8080
```

Lalu buka <http://localhost:8080>.

## Catatan

- Tidak membutuhkan Laravel, PHP, database, route, controller, atau API.
- Seluruh kalkulasi dilakukan di browser menggunakan vanilla JavaScript.
- Ikon memakai Bootstrap Icons dari CDN. Kalkulator tetap berjalan tanpa ikon jika koneksi internet tidak tersedia.
- File ini tidak mengubah route/UI Laravel yang sudah ada di project.
