src=$1
dst=$2
sips -s format png "$src" --out "$dst" >/dev/null

h=$(sips -g pixelHeight "$dst" | tail -1 | awk '{print $2}')
w=$(sips -g pixelWidth "$dst" | tail -1 | awk '{print $2}')

crop_top=$((h * 38 / 100))
crop_bottom=$((h * 38 / 100))
crop_side=$((w * 3 / 100))

new_h=$((h - crop_top - crop_bottom))
new_w=$((w - crop_side * 2))

sips --cropOffset $crop_top $crop_side --cropToHeightWidth $new_h $new_w "$dst" --out "$dst"
echo "Cropped to ${new_w}x${new_h}"
