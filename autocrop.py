import sys, subprocess, os
try:
    from PIL import Image, ImageOps
except ImportError:
    subprocess.run([sys.executable, '-m', 'pip', 'install', 'Pillow'], capture_output=True)
    from PIL import Image, ImageOps

src = sys.argv[1]
dst = sys.argv[2]
if not os.path.exists(src): sys.exit(1)

# convert and crop
img = Image.open(src).convert('L')
# Invert so content is white, background is black
inv = ImageOps.invert(img)
# Get bounding box of non-black pixels (content)
bbox = inv.getbbox()
if bbox:
    # Add a tiny bit of padding (e.g. 5 pixels)
    pad = 5
    bbox = (max(0, bbox[0]-pad), max(0, bbox[1]-pad), min(img.width, bbox[2]+pad), min(img.height, bbox[3]+pad))
    cropped = img.crop(bbox)
    cropped.save(dst, format="PNG")
    print(f"Auto-cropped to {cropped.width}x{cropped.height}")
else:
    print("Could not find bounding box")
