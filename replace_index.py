import re

with open('resources/views/production/cutting_jobs/index.blade.php', 'r') as f:
    content = f.read()

content = content.replace(
    ".cj-row-action{ margin-top:.55rem; }\n        .cj-row-action .btn{ width:100%; min-height:38px; }",
    ".cj-row-action{ margin-top:.55rem; display:flex; gap:0.4rem; justify-content:flex-end; }\n        .cj-row-action .btn{ flex:1; min-height:38px; display:inline-flex; align-items:center; justify-content:center; }\n        .cj-row-action .btn-icon{ flex:0 0 44px; padding:0; }"
)

old_td = """                                    <td class="text-end cj-row-action">
                                        <a href="{{ $actionUrl }}" class="btn btn-sm {{ $actionLabel === 'Input QC' ? 'btn-ship-primary' : 'btn-ship-outline' }} btn-pill">
                                            {{ $actionLabel }}
                                        </a>
                                    </td>"""

new_td = """                                    <td class="text-end cj-row-action" style="white-space:nowrap;">
                                        @if($actionLabel === 'Input QC')
                                            <a href="{{ $detailUrl }}" class="btn btn-sm btn-ship-outline btn-pill btn-icon" title="Detail" style="width:30px;padding:0;display:inline-flex;align-items:center;justify-content:center;">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                            </a>
                                        @endif
                                        <a href="{{ $actionUrl }}" class="btn btn-sm {{ $actionLabel === 'Input QC' ? 'btn-ship-primary' : 'btn-ship-outline' }} btn-pill">
                                            {{ $actionLabel }}
                                        </a>
                                    </td>"""

if old_td in content:
    content = content.replace(old_td, new_td)
else:
    print("Could not find td block to replace")

with open('resources/views/production/cutting_jobs/index.blade.php', 'w') as f:
    f.write(content)

print("done")
