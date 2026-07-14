import re

with open('/Users/ariefmuhamad/Herd/gfid-dev/resources/views/purchasing/purchase_returns/index.blade.php', 'r') as f:
    content = f.read()

# Add button
button_html = """        <button type="button" class="btn btn-sm btn-ship-primary btn-pill" data-bs-toggle="modal" data-bs-target="#modalCreateReturn">
            <i class="bi bi-plus-lg me-1"></i> Tambah Retur
        </button>
        <button type="button" class="btn btn-sm btn-ship-outline btn-pill" data-bs-toggle="modal" data-bs-target="#modalSearchItem">
            <i class="bi bi-search me-1"></i> Cari Barang
        </button>"""

content = content.replace("""        <button type="button" class="btn btn-sm btn-ship-primary btn-pill" data-bs-toggle="modal" data-bs-target="#modalCreateReturn">
            <i class="bi bi-plus-lg me-1"></i> Tambah Retur
        </button>""", button_html)

# Add Modal
modal_html = """<!-- Modal Search Item -->
<div class="modal fade" id="modalSearchItem" tabindex="-1" aria-labelledby="modalSearchItemLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius: 14px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="modalSearchItemLabel">Cari Barang untuk Diretur</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formSearchItem" method="POST" action="">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-bold mb-1" style="font-size:.82rem;">
                    Scan / Ketik Nama Barang
                </label>
                <select class="form-control" id="itemSearchSelect" name="grn_id" required style="width:100%;">
                    <option value=""></option>
                </select>
                <div class="form-text" style="font-size:.74rem;">Ketik barang untuk mencari GRN (Penerimaan) yang memuat barang ini.</div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold mb-1" style="font-size:.82rem;">
                    Tanggal Retur
                </label>
                <input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}">
            </div>

            <button type="submit" class="btn btn-ship-primary btn-pill w-100 fw-bold" id="btnSubmitItemReturn" disabled>Lanjut Buat Retur</button>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection"""

content = content.replace('@endsection', modal_html)


# Add JS
js_html = """
      // ── Flow Search by Item ──
      const $modalSearch = $('#modalSearchItem');
      const $itemSearch = $('#itemSearchSelect');
      const $btnSearch = $('#btnSubmitItemReturn');
      const $formSearch = $('#formSearchItem');
      const searchItemUrl = '{{ route("purchasing.purchase_returns.search_by_item") }}';

      $itemSearch.select2({
          theme: 'default',
          placeholder: 'Ketik nama / kode barang...',
          dropdownParent: $modalSearch,
          ajax: {
              url: searchItemUrl,
              dataType: 'json',
              delay: 350,
              data: function (params) {
                  return { q: params.term };
              },
              processResults: function (data) { return { results: data.results }; },
              cache: true
          }
      });

      $itemSearch.on('change', function () {
          const val = $(this).val();
          if (val) {
              $btnSearch.prop('disabled', false);
              $formSearch.attr('action', '/purchasing/purchase-receipts/' + val + '/returns/create');
          } else {
              $btnSearch.prop('disabled', true);
              $formSearch.attr('action', '');
          }
      });

      $modalSearch.on('hidden.bs.modal', function () {
          $itemSearch.val(null).trigger('change');
          $itemSearch.empty().append(new Option('', '', true, true));
      });

      $modalSearch.on('shown.bs.modal', function () {
          $itemSearch.select2('open');
      });
  } else {
"""

content = content.replace('  } else {\n      console.error(\'jQuery is required for Select2.\');', js_html + '      console.error(\'jQuery is required for Select2.\');')

with open('/Users/ariefmuhamad/Herd/gfid-dev/resources/views/purchasing/purchase_returns/index.blade.php', 'w') as f:
    f.write(content)

