import zipfile
import os

folders_to_zip = [
    'css',
    'js',
    'admin',
    'images',
    'pages'
]

files_to_zip = [
    'index.html'
]

output_filename = 'hela_weda_gedara_updated.zip'

def create_zip():
    print(f"Starting to create {output_filename}...")
    try:
        with zipfile.ZipFile(output_filename, 'w', zipfile.ZIP_DEFLATED) as zipf:
            # Add files
            for file in files_to_zip:
                if os.path.exists(file):
                    zipf.write(file)
                    print(f"Added file {file} to archive.")
                else:
                    print(f"Warning: {file} not found, skipping.")
            # Add folders recursively
            for folder in folders_to_zip:
                if os.path.exists(folder):
                    for root, dirs, files in os.walk(folder):
                        for file in files:
                            file_path = os.path.join(root, file)
                            zipf.write(file_path, file_path)
                            print(f"Added file {file_path} to archive.")
                else:
                    print(f"Warning: folder {folder} not found, skipping.")
        print(f"Successfully created {output_filename} containing all website source files!")
    except Exception as e:
        print(f"Error zipping files: {e}")

if __name__ == '__main__':
    create_zip()
