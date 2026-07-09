import sys, os, subprocess

src = sys.argv[1]
dst = sys.argv[2]
if not os.path.exists(src):
    sys.exit(1)

# we convert to png
subprocess.run(['sips', '-s', 'format', 'png', src, '--out', dst], capture_output=True)

# get dimensions
h_str = subprocess.check_output(['sips', '-g', 'pixelHeight', dst]).decode('utf-8').strip().split('\n')[-1].split()[-1]
w_str = subprocess.check_output(['sips', '-g', 'pixelWidth', dst]).decode('utf-8').strip().split('\n')[-1].split()[-1]
h = int(h_str)
w = int(w_str)

# Instead of blindly cropping 5%, I will crop top and bottom heavily. The border is usually around 15% from top and bottom.
crop_top = int(h * 0.12)
crop_bottom = int(h * 0.12)
crop_side = int(w * 0.05)
new_h = h - crop_top - crop_bottom
new_w = w - crop_side * 2

subprocess.run(['sips', '--cropOffset', str(crop_top), str(crop_side), '--cropToHeightWidth', str(new_h), str(new_w), dst, '--out', dst])
print(f"Cropped to {new_w}x{new_h}")
