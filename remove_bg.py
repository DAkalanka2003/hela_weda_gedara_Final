from PIL import Image
import os

dest_dir = r"F:\Intern\Web Site\hela weda gedara boossa\images"

def make_transparent(img_path, output_path, tolerance=240):
    if not os.path.exists(img_path):
        print(f"Error: {img_path} not found.")
        return
        
    print(f"Removing background from {img_path}...")
    img = Image.open(img_path).convert("RGBA")
    datas = img.getdata()
    
    newData = []
    for item in datas:
        # If the pixel is close to white (R > tolerance, G > tolerance, B > tolerance)
        if item[0] >= tolerance and item[1] >= tolerance and item[2] >= tolerance:
            # Make it fully transparent
            newData.append((255, 255, 255, 0))
        else:
            newData.append(item)
            
    img.putdata(newData)
    img.save(output_path, "PNG")
    print(f"Saved transparent image to {output_path}")

def run_process():
    logo_path = os.path.join(dest_dir, "logo.png")
    dev_logo_path = os.path.join(dest_dir, "developer_logo.png")
    
    # We will process logo.png and developer_logo.png and replace them with transparent PNGs
    # Note: logo.png was copied from a JPG, so we save it as transparent logo.png
    make_transparent(logo_path, os.path.join(dest_dir, "logo.png"), tolerance=240)
    make_transparent(logo_path, os.path.join(dest_dir, "footerlogo.png"), tolerance=240)
    make_transparent(dev_logo_path, os.path.join(dest_dir, "developer_logo.png"), tolerance=240)

if __name__ == '__main__':
    run_process()
