import re

with open('resources/views/production/qc/sewing_index.blade.php', 'r') as f:
    content = f.read()

# CSS replace
content = content.replace(
    ".sew-row-action{\n            margin-top:.55rem;\n        }\n        .sew-row-action .btn{\n            width:100%;\n            min-height:38px;\n        }",
    ".sew-row-action{ margin-top:.55rem; display:flex; gap:0.4rem; justify-content:flex-end; }\n        .sew-row-action .btn{ flex:1; min-height:38px; display:inline-flex; align-items:center; justify-content:center; }\n        .sew-row-action .btn-icon{ flex:0 0 44px; padding:0; }"
)

# Button replace
old_td = """                                    <td class="text-end sew-row-action">
                                        @if ($actionHref)
                                            <a href="{{ $actionHref }}"
                                               class="btn btn-sm {{ $isQcDone ? 'btn-outline-secondary' : 'btn-outline-primary' }} btn-pill">
                                                {{ $actionLabel }}
                                            </a>
                                        @endif
                                    </td>"""

new_td = """                                    <td class="text-end sew-row-action" style="white-space:nowrap;">
                                        @if ($actionHref)
                                            @if($actionLabel === 'Input QC' && $detailHref)
                                                <a href="{{ $detailHref }}" class="btn btn-sm btn-outline-secondary btn-pill btn-icon" title="Detail" style="width:30px;padding:0;display:inline-flex;align-items:center;justify-content:center;">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                                </a>
                                            @endif
                                            <a href="{{ $actionHref }}"
                                               class="btn btn-sm {{ $isQcDone ? 'btn-outline-secondary' : 'btn-outline-primary' }} btn-pill">
                                                {{ $actionLabel }}
                                            </a>
                                        @endif
                                    </td>"""

if old_td in content:
    content = content.replace(old_td, new_td)
else:
    print("Could not find td block in sewing_index.blade.php")

with open('resources/views/production/qc/sewing_index.blade.php', 'w') as f:
    f.write(content)

print("done")
