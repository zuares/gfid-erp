# Rencana Split Akun WIP

Status saat ini:
- Semua WIP internal masih memakai akun `1202 Persediaan WIP`.
- Movement `WIP-CUT -> WIP-SEW -> WIP-FIN` tetap dibuat jurnal, tetapi efek netonya nol karena debit dan kredit memakai akun yang sama.
- Flow ini aman untuk saat ini karena tidak mengubah COA, database, atau workflow stok.

Rekomendasi tahap berikutnya saat laporan WIP ingin lebih detail:

| Tahap | Akun Saat Ini | Akun Rekomendasi |
| --- | --- | --- |
| Cutting | 1202 Persediaan WIP | 1202.01 Persediaan WIP Cutting |
| Sewing | 1202 Persediaan WIP | 1202.02 Persediaan WIP Jahit |
| Finishing | 1202 Persediaan WIP | 1202.03 Persediaan WIP Finishing |

Mapping jurnal yang disarankan:

| Movement | Debit | Kredit |
| --- | --- | --- |
| cutting_job | 1202.01 WIP Cutting | 1201 Bahan Baku |
| cutting_wip | 1202.01 WIP Cutting | 1202.01 WIP Cutting |
| sewing_pickup | 1202.02 WIP Jahit | 1202.01 WIP Cutting |
| sewing_return_ok | 1202.03 WIP Finishing | 1202.02 WIP Jahit |
| finishing_job OK | 1203 Barang Jadi | 1202.03 WIP Finishing |
| finishing_job Reject | 1204 Barang Cacat | 1202.03 WIP Finishing |

Catatan implementasi:
- Jangan langsung ubah akun existing `1202` sebelum saldo historis WIP direkonsiliasi.
- Tambahkan akun baru secara additive.
- Setelah akun baru tersedia, update mapping di `JournalService`, lalu backfill ulang di database staging/dev.
- Pastikan laporan trial balance dan profit loss tetap balance setelah split.
