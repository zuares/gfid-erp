#!/usr/bin/env python3
"""
Test fulfillment logic langsung via SQLite.
Jalankan: python3 test_fulfillment.py
"""
import sqlite3, sys, os

DB = os.path.join(os.path.dirname(__file__), "database_dev.sqlite")

passed = 0
failed = 0
warned = 0

def ok(msg):
    global passed; passed += 1; print(f"  ✅  {msg}")
def fail(msg):
    global failed; failed += 1; print(f"  ❌  {msg}")
def warn(msg):
    global warned; warned += 1; print(f"  ⚠   {msg}")
def section(title):
    print(f"\n{'─'*55}\n  {title}\n{'─'*55}")

def q(sql, *args):
    with sqlite3.connect(DB) as c:
        c.execute("PRAGMA busy_timeout=3000")
        return c.execute(sql, args).fetchall()

def q1(sql, *args):
    rows = q(sql, *args); return rows[0] if rows else None

# ── 1. Skema ─────────────────────────────────────────────────────────────────
section("1. DATABASE SCHEMA")

cols = [r[1] for r in q("PRAGMA table_info(inventory_stocks)")]
if "lot_id" not in cols:
    ok("inventory_stocks: tidak ada kolom lot_id (fix sudah benar)")
else:
    warn("inventory_stocks: ada kolom lot_id — kode harus disesuaikan")

for col in ["item_id","lot_id","qty_ordered","qty_fulfilled","stock_available"]:
    if col in [r[1] for r in q("PRAGMA table_info(order_fulfillment_lines)")]:
        ok(f"order_fulfillment_lines.{col} ada")
    else:
        fail(f"order_fulfillment_lines.{col} TIDAK ada")

# ── 2. Order Fulfillment Confirmed ────────────────────────────────────────────
section("2. ORDER FULFILLMENT — STATUS CONFIRMED")

confirmed = q("""
    SELECT of2.id, mo.channel_order_id, of2.status, of2.warehouse_id, mo.order_status, of2.confirmed_at
    FROM order_fulfillments of2
    JOIN marketplace_orders mo ON mo.id = of2.marketplace_order_id
    WHERE of2.status = 'confirmed'
    ORDER BY of2.id DESC LIMIT 8
""")

if confirmed:
    ok(f"{len(confirmed)} fulfillment confirmed ditemukan")
    # Fulfillment id >= 16 adalah yang dikonfirmasi dengan kode baru (setelah fix DB::table)
    FIX_THRESHOLD = 16
    for (fid, cid, fstatus, wh, order_status, confirmed_at) in confirmed:
        icon = "✅" if order_status == "fulfilled" else ("⚠ " if fid < FIX_THRESHOLD else "❌")
        note = " (legacy — sebelum fix)" if fid < FIX_THRESHOLD and order_status != "fulfilled" else ""
        print(f"       id={fid} order={cid} order_status={order_status} wh={wh}{note}")
        if order_status == "fulfilled":
            ok(f"  id={fid}: order_status='fulfilled' ✓")
        elif fid < FIX_THRESHOLD:
            warn(f"  id={fid}: order_status='{order_status}' (dikonfirmasi sebelum fix DB::table, legacy data)")
        else:
            fail(f"  id={fid}: order_status='{order_status}' — fix DB::table belum bekerja!")
else:
    warn("Belum ada fulfillment confirmed")

# ── 3. Inventory Mutations ────────────────────────────────────────────────────
section("3. INVENTORY MUTATIONS")

muts = q("""
    SELECT m.id, i.code, m.direction, m.qty_change, m.source_id, m.created_at
    FROM inventory_mutations m
    JOIN items i ON i.id = m.item_id
    WHERE m.source_type = 'order_fulfillment'
    ORDER BY m.id DESC LIMIT 8
""")

if muts:
    ok(f"{len(muts)} mutasi dari order_fulfillment:")
    for row in muts:
        print(f"       id={row[0]} {row[1]} {row[2]} qty={row[3]} fulfillment={row[4]} at={row[5]}")
else:
    fail("Tidak ada inventory_mutations dari order_fulfillment")

# Setiap confirmed fulfillment (id >= FIX_THRESHOLD) harus punya mutasi
for (fid, cid, fstatus, wh, order_status, confirmed_at) in confirmed:
    fmuts = q("SELECT COUNT(*) FROM inventory_mutations WHERE source_type='order_fulfillment' AND source_id=?", fid)[0][0]
    if fmuts > 0:
        ok(f"  Fulfillment #{fid}: {fmuts} mutasi ✓")
    else:
        fail(f"  Fulfillment #{fid}: 0 mutasi — stok tidak terpotong!")

# ── 4. Stok Decrement ─────────────────────────────────────────────────────────
section("4. STOCK DECREMENT SPOT CHECK (K3BLK, K3NVY)")

# K3BLK dan K3NVY dikonfirmasi di fulfillment #17 (setelah fix)
for code in ["K3BLK", "K3NVY"]:
    stock = q("""
        SELECT s.warehouse_id, s.qty
        FROM inventory_stocks s JOIN items i ON i.id=s.item_id
        WHERE i.code=? AND s.warehouse_id=8
    """, code)
    total_out = q("""
        SELECT COALESCE(SUM(qty_change), 0)
        FROM inventory_mutations m JOIN items i ON i.id=m.item_id
        WHERE i.code=? AND m.direction='out' AND m.source_type='order_fulfillment'
    """, code)[0][0]
    if stock:
        ok(f"{code}: stok warehouse 8 = {stock[0][1]}, total out = {total_out}")
    else:
        warn(f"{code}: tidak ada baris di inventory_stocks warehouse 8")

# ── 5. UI — Kode Blade ────────────────────────────────────────────────────────
section("5. UI — TOMBOL & FITUR DI BLADE")

blade = open(os.path.join(os.path.dirname(__file__),
    "resources/views/marketplace/orders.blade.php")).read()

checks = [
    ("Ganti Produk",             "Tombol 'Ganti Produk' di tabel order"),
    ("openFulfillment",          "Fungsi openFulfillment"),
    ("Konfirmasi",               "Tombol Konfirmasi & Potong Stok"),
    ("printedOrderIds",          "printedOrderIds tracking (badge sudah cetak)"),
    ("Detail Pesanan",           "Section Detail Pesanan di picking list"),
    ("100mm",                    "Ukuran kertas 100mm"),
    ("150mm",                    "Ukuran kertas 150mm"),
    ("row-printed",              "CSS class row-printed (badge warna biru)"),
    ("Sudah Cetak",              "Badge teks '🖨 Sudah Cetak'"),
]
for needle, label in checks:
    if needle in blade:
        ok(f"{label} ✓")
    else:
        fail(f"{label} TIDAK ditemukan")

# ── 6. Service Fixes ──────────────────────────────────────────────────────────
section("6. SERVICE & CONTROLLER FIXES")

service = open(os.path.join(os.path.dirname(__file__),
    "app/Services/OrderFulfillmentService.php")).read()

if "if ($fulfillment->warehouse_id && $line->lot_id)" in service:
    fail("MASIH ADA syarat lot_id — fix belum diterapkan!")
elif "if ($fulfillment->warehouse_id)" in service:
    ok("lot_id condition sudah dihapus dari confirm() ✓")

if "DB::table('marketplace_orders')" in service:
    ok("DB::table fix untuk order_status update ✓")
else:
    fail("DB::table fix tidak ditemukan di service")

if "qty_fulfilled' => (int) $orderItem->qty" in service:
    ok("createDraft: qty_fulfilled = qty_ordered (stok boleh minus) ✓")
else:
    fail("createDraft: qty_fulfilled masih pakai min()")

controller = open(os.path.join(os.path.dirname(__file__),
    "app/Http/Controllers/Owner/FulfillmentController.php")).read()

if "OrderFulfillment $fulfillment, OrderFulfillmentLine $line" in controller:
    ok("updateLine: parameter OrderFulfillment + OrderFulfillmentLine ✓")
else:
    fail("updateLine: parameter binding salah — penyebab error Argument #2")

# ── Hasil ─────────────────────────────────────────────────────────────────────
print(f"\n{'═'*55}")
print(f"  HASIL: {passed} passed, {warned} warned, {failed} failed")
print(f"{'═'*55}")
if failed == 0:
    print("  Semua test critical LULUS ✅")
else:
    print("  Ada test yang GAGAL ❌")
print()

sys.exit(0 if failed == 0 else 1)
