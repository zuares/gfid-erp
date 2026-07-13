with open('resources/views/purchasing/purchase_orders/show.blade.php', 'r') as f:
    lines = f.readlines()

# The GRN block starts at index 983 (line 984) and ends at index 1138 (line 1139)
start_grn = -1
end_grn = -1

for i, line in enumerate(lines):
    if "{{-- GOODS RECEIPTS (GRN) — owner & admin --}}" in line:
        start_grn = i
    if "endif {{-- end isOwner GRN --}}" in line:
        end_grn = i
        break

if start_grn == -1 or end_grn == -1:
    print("GRN Block not found.", start_grn, end_grn)
    exit(1)

grn_lines = lines[start_grn:end_grn+1]

# Remove the lines
lines = lines[:start_grn] + lines[end_grn+1:]

# Now find where to insert it. We want to insert it after the Detail Barang block.
# The Detail Barang block ends near `        </div>\n\n    </div>\n\n    @if ($canSeeMoney)\n    {{-- =========================================================`
insert_idx = -1
for i, line in enumerate(lines):
    if "MODAL: ADD PAYMENT / DP" in line:
        # Go up a few lines to find the end of the page-wrap
        for j in range(i, i-10, -1):
            if "    </div>" in lines[j] and lines[j-1].strip() == "</div>":
                insert_idx = j # Inside the page-wrap
                break
        break

if insert_idx == -1:
    print("Insert index not found")
    exit(1)

# Let's verify by just printing the lines around insert_idx
print("Inserting before:")
for i in range(insert_idx-2, insert_idx+3):
    print(i, lines[i].rstrip())

lines = lines[:insert_idx] + ["\n"] + grn_lines + ["\n"] + lines[insert_idx:]

with open('resources/views/purchasing/purchase_orders/show.blade.php', 'w') as f:
    f.writelines(lines)

print("Success!")
