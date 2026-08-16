import os

files_to_update = [
    'about.html',
    'academy.html',
    'ayurveda.html',
    'treatements.html',
    'consultation.html',
    'contact.html',
    'blog.html'
]

curtain_blocks = [
    """<!-- Immersive Tree Curtain Preloader -->
    <div id="tree-curtain">
        <div class="curtain-panel curtain-left"></div>
        <div class="curtain-panel curtain-right"></div>
    </div>""",
    """<!-- Immersive Tree Curtain Preloader -->
    <div id="tree-curtain">
        <div class="curtain-panel curtain-left"></div>
        <div class="curtain-panel curtain-right"></div>
    </div>""",
    """<div id="tree-curtain">
        <div class="curtain-panel curtain-left"></div>
        <div class="curtain-panel curtain-right"></div>
    </div>"""
]

def clean_files():
    print("Cleaning remaining HTML files (removing preloader curtain as requested)...")
    for filename in files_to_update:
        if os.path.exists(filename):
            print(f"Cleaning {filename}...")
            with open(filename, 'r', encoding='utf-8') as f:
                content = f.read()
            
            # Remove any occurrence of the curtain div
            for block in curtain_blocks:
                content = content.replace(block, "")
                
            # Replace developer credit row in case of line breaks
            old_credit_block = """<div class="dev-credit-wrapper">
                <span>Developed by <img src="images/developer_logo.png" alt="100 Logo" class="dev-logo-img"> International Universe PVT Ltd web development team.</span>
            </div>"""
            
            new_credit_block = """<div class="dev-credit-wrapper">
                <span>Developed by <img src="images/developer_logo.png" alt="100 Logo" class="dev-logo-img"> International Universe PVT Ltd web development team.</span>
            </div>"""
            
            content = content.replace(old_credit_block, new_credit_block)
            
            with open(filename, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"Finished cleaning {filename}.")
        else:
            print(f"Warning: {filename} not found.")

if __name__ == '__main__':
    clean_files()
