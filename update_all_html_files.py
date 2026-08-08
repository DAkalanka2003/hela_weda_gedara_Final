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

curtain_html = """<body>

    <!-- Immersive Tree Curtain Preloader -->
    <div id="tree-curtain">
        <div class="curtain-panel curtain-left"></div>
        <div class="curtain-panel curtain-right"></div>
    </div>"""

dev_credit_old = """<div class="dev-credit-wrapper">
                <span>Developed by 100 International Universe PVT Ltd web development team</span>
                <img src="images/developer_logo.png" alt="100 International Universe Logo" class="dev-logo-img">
            </div>"""

dev_credit_new = """<div class="dev-credit-wrapper">
                <span>Developed by <img src="images/developer_logo.png" alt="100 Logo" class="dev-logo-img"> International Universe PVT Ltd web development team.</span>
            </div>"""

def update_files():
    print("Updating remaining HTML files with preloader and new inline logo credit...")
    for filename in files_to_update:
        if os.path.exists(filename):
            print(f"Processing {filename}...")
            with open(filename, 'r', encoding='utf-8') as f:
                content = f.read()
            
            # Inject Curtain Div right after <body>
            if 'id="tree-curtain"' not in content:
                content = content.replace("<body>", curtain_html)
                
            # Replace Developer credit line
            content = content.replace(dev_credit_old, dev_credit_new)
            
            with open(filename, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"Updated {filename} successfully.")
        else:
            print(f"Warning: {filename} not found.")
            
if __name__ == '__main__':
    update_files()
