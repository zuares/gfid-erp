import sys
sys.path.append('/Users/ariefmuhamad/Library/Python/3.9/lib/python/site-packages')
from PIL import Image

def process(file_path):
    # Open the image
    img = Image.open(file_path)
    
    # 1. Upscale the image by 2x for much higher resolution
    new_size = (img.width * 2, img.height * 2)
    # Use Lanczos for high quality resampling
    upscaled = img.resize(new_size, Image.Resampling.LANCZOS)
    
    # 2. Convert to grayscale and apply a hard threshold for crisp thermal printing
    # Convert to grayscale
    gray = upscaled.convert('L')
    # Anything lighter than dark gray (e.g., 200) becomes white, else black
    threshold = 220
    bw = gray.point(lambda p: 255 if p > threshold else 0, mode='1')
    
    # Save it back
    bw.save(file_path, optimize=True)
    print(f"Processed {file_path} -> {new_size[0]}x{new_size[1]} pure B&W")

if __name__ == '__main__':
    for arg in sys.argv[1:]:
        process(arg)
