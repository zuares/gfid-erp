src=$1
dst=$2
sips -s format png "$src" --out "$dst" >/dev/null

h=$(sips -g pixelHeight "$dst" | tail -1 | awk '{print $2}')
w=$(sips -g pixelWidth "$dst" | tail -1 | awk '{print $2}')

# The image is 16:9 (e.g. 1792x1008). 
# The actual border content takes up roughly 40% of the height in the middle.
# Let's crop 27% from top and 27% from bottom.
# And 3% from left/right.
crop_top=$((h * 27 / 100))
crop_bottom=$((h * 27 / 100))
crop_side=$((w * 3 / 100))

new_h=$((h - crop_top - crop_bottom))
new_w=$((w - crop_side * 2))

sips --cropOffset $crop_top $crop_side --cropToHeightWidth $new_h $new_w "$dst" --out "$dst"
echo "Cropped to ${new_w}x${new_h}"
