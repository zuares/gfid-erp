import re

with open('resources/views/production/qc/index.blade.php', 'r') as f:
    content = f.read()

old_td = """                                        <td>
                                            <div class="d-flex gap-1 flex-wrap">
                                            @if (Route::has('production.qc.sewing.edit'))
                                                <a href="{{ route('production.qc.sewing.edit', $ret) }}"
                                                   class="btn btn-sm {{ $hasQc ? 'btn-outline-secondary' : 'btn-outline-primary' }}"
                                                   onclick="event.stopPropagation();"
                                                   title="Form QC Jahit">
                                                   {{ $hasQc ? 'Lihat' : 'Input QC' }}
                                                </a>
                                            @endif
                                            </div>
                                        </td>"""

new_td = """                                        <td style="white-space:nowrap;">
                                            <div class="d-flex gap-1 flex-wrap">
                                            @if (Route::has('production.qc.sewing.edit'))
                                                @if(!$hasQc && Route::has('production.sewing.returns.show'))
                                                    <a href="{{ route('production.sewing.returns.show', $ret) }}" class="btn btn-sm btn-outline-secondary" title="Detail" style="width:30px;padding:0;display:inline-flex;align-items:center;justify-content:center;" onclick="event.stopPropagation();">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                                    </a>
                                                @endif
                                                <a href="{{ route('production.qc.sewing.edit', $ret) }}"
                                                   class="btn btn-sm {{ $hasQc ? 'btn-outline-secondary' : 'btn-outline-primary' }}"
                                                   onclick="event.stopPropagation();"
                                                   title="Form QC Jahit">
                                                   {{ $hasQc ? 'Lihat' : 'Input QC' }}
                                                </a>
                                            @endif
                                            </div>
                                        </td>"""

if old_td in content:
    content = content.replace(old_td, new_td)
else:
    print("Could not find td block in qc/index.blade.php")

with open('resources/views/production/qc/index.blade.php', 'w') as f:
    f.write(content)

print("done")
