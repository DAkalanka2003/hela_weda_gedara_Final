import os
import shutil

dest_dir = 'images'

# Mappings of failed downloads -> working fallback files
fallbacks = {
    'aboutsecond.png': 'aboutfirst.png',
    'consultation_service.jpg': 'panchakarma_service.jpg',
    'aboutbeauty.jpg': 'panchakarma_service.jpg',
    'ayrvedaabout.jpg': 'about1.jpg',
    'wedakama1.jpg': 'about1.jpg',
    'wedakama3.jpg': 'traningmaindesign.jpg',
    'treatementmain1.jpg': 'panchakarma_service.jpg',
    'consultantHeader.jpg': 'aboutpageheader.jpg'
}

def resolve_fallbacks():
    print("Resolving fallback mappings for failed image assets...")
    for failed_file, source_file in fallbacks.items():
        failed_path = os.path.join(dest_dir, failed_file)
        source_path = os.path.join(dest_dir, source_file)
        
        if not os.path.exists(failed_path):
            if os.path.exists(source_path):
                shutil.copy(source_path, failed_path)
                print(f"Copied {source_file} -> {failed_file} (resolved).")
            else:
                print(f"Error: Source file {source_file} not found for fallback.")
        else:
            print(f"{failed_file} already exists, skipping.")
    print("All image assets finalized successfully!")

if __name__ == '__main__':
    resolve_fallbacks()
