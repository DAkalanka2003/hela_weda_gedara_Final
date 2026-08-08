import shutil
import os

src_dir = r"C:\Users\Admin\.gemini\antigravity\brain\5614deed-8c01-4450-996c-7b493608c970\.user_uploaded"
dest_dir = r"F:\Intern\Web Site\hela weda gedara boossa\images"

main_logo_src = os.path.join(src_dir, "media_1786009645149.jpg")
dev_logo_src = os.path.join(src_dir, "media_1786009757097.png")

logo_dest = os.path.join(dest_dir, "logo.png")
footer_logo_dest = os.path.join(dest_dir, "footerlogo.png")
dev_logo_dest = os.path.join(dest_dir, "developer_logo.png")

def copy_files():
    print("Copying logo files to destination images directory...")
    
    # Copy main logo
    if os.path.exists(main_logo_src):
        shutil.copy(main_logo_src, logo_dest)
        shutil.copy(main_logo_src, footer_logo_dest)
        print("Successfully copied main logo.")
    else:
        print(f"Error: Main logo source {main_logo_src} not found.")

    # Copy dev logo
    if os.path.exists(dev_logo_src):
        shutil.copy(dev_logo_src, dev_logo_dest)
        print("Successfully copied developer logo.")
    else:
        print(f"Error: Developer logo source {dev_logo_src} not found.")

if __name__ == '__main__':
    copy_files()
