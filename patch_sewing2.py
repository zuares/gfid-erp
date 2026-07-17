import re

with open('resources/views/production/qc/sewing_edit.blade.php', 'r') as f:
    content = f.read()

# Replace desktop table inputs
content = content.replace(
"""                                    <td style="text-align:right">
                                        <input type="number"
                                               name="results[{{ $i }}][qty_ok]"
                                               class="qcs-qty-input qcs-mono is-ok qty-ok"
                                               value="{{ old("results.{$i}.qty_ok", $row['qty_ok']) }}"
                                               min="0"
                                               max="{{ $row['qty_max'] }}"
                                               step="1"
                                               inputmode="numeric"
                                               pattern="[0-9]*"
                                               onfocus="this.select()"
                                               oninput="syncReject(this, {{ $i }}, {{ $row['qty_max'] }})">
                                    </td>
                                    <td style="text-align:right">
                                        <input type="number"
                                               name="results[{{ $i }}][qty_reject]"
                                               class="qcs-qty-input qcs-mono is-reject qty-reject"
                                               id="reject_{{ $i }}"
                                               value="{{ old("results.{$i}.qty_reject", $row['qty_reject']) }}"
                                               min="0"
                                               max="{{ $row['qty_max'] }}"
                                               step="1"
                                               inputmode="numeric"
                                               pattern="[0-9]*"
                                               onfocus="this.select()"
                                               oninput="syncOk(this, {{ $i }}, {{ $row['qty_max'] }})">
                                    </td>
                                    <td>
                                        <select name="results[{{ $i }}][reject_reason]" class="qcs-reason-input">
                                            <option value="">- Pilih Alasan -</option>
                                            <option value="Reject Jahit" {{ old("results.{$i}.reject_reason", $row['reject_reason']) == 'Reject Jahit' ? 'selected' : '' }}>Reject Jahit</option>
                                            <option value="Reject Bahan" {{ old("results.{$i}.reject_reason", $row['reject_reason']) == 'Reject Bahan' ? 'selected' : '' }}>Reject Bahan</option>
                                            @if(old("results.{$i}.reject_reason", $row['reject_reason']) && !in_array(old("results.{$i}.reject_reason", $row['reject_reason']), ['Reject Jahit', 'Reject Bahan']))
                                                <option value="{{ old("results.{$i}.reject_reason", $row['reject_reason']) }}" selected>{{ old("results.{$i}.reject_reason", $row['reject_reason']) }}</option>
                                            @endif
                                        </select>
                                    </td>""",
"""                                    <td style="text-align:right">
                                        <input type="number"
                                               name="results[{{ $i }}][qty_ok]"
                                               class="qcs-qty-input qcs-mono is-ok qty-ok"
                                               id="ok_{{ $i }}"
                                               value="{{ old("results.{$i}.qty_ok", $row['qty_ok']) }}"
                                               min="0" max="{{ $row['qty_max'] }}" step="1" inputmode="numeric" pattern="[0-9]*"
                                               onfocus="this.select()"
                                               oninput="syncQty('ok', {{ $i }}, {{ $row['qty_max'] }})">
                                    </td>
                                    <td style="text-align:right">
                                        <input type="number"
                                               name="results[{{ $i }}][qty_reject_jahit]"
                                               class="qcs-qty-input qcs-mono is-reject qty-reject-jahit"
                                               id="jahit_{{ $i }}"
                                               value="{{ old("results.{$i}.qty_reject_jahit", $row['qty_reject_jahit'] ?? 0) }}"
                                               min="0" max="{{ $row['qty_max'] }}" step="1" inputmode="numeric" pattern="[0-9]*" placeholder="0"
                                               onfocus="this.select()"
                                               oninput="syncQty('jahit', {{ $i }}, {{ $row['qty_max'] }})">
                                    </td>
                                    <td style="text-align:right">
                                        <input type="number"
                                               name="results[{{ $i }}][qty_reject_bahan]"
                                               class="qcs-qty-input qcs-mono is-reject qty-reject-bahan"
                                               id="bahan_{{ $i }}"
                                               value="{{ old("results.{$i}.qty_reject_bahan", $row['qty_reject_bahan'] ?? 0) }}"
                                               min="0" max="{{ $row['qty_max'] }}" step="1" inputmode="numeric" pattern="[0-9]*" placeholder="0"
                                               onfocus="this.select()"
                                               oninput="syncQty('bahan', {{ $i }}, {{ $row['qty_max'] }})">
                                    </td>""")

# update the table headers
content = content.replace(
"""                                    <th style="width:110px;text-align:right;">Reject</th>
                                    <th style="width:160px;">Alasan Reject</th>""",
"""                                    <th style="width:90px;text-align:right;">Rj Jahit</th>
                                    <th style="width:90px;text-align:right;">Rj Bahan</th>""")

with open('resources/views/production/qc/sewing_edit.blade.php', 'w') as f:
    f.write(content)
