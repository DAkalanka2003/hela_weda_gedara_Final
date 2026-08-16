import os
import subprocess
import sys

# Try to import Pillow, install if missing
try:
    from PIL import Image, ImageDraw, ImageFont
except ImportError:
    print("Installing Pillow for placeholder image generation...")
    subprocess.check_call([sys.executable, "-m", "pip", "install", "Pillow"])
    from PIL import Image, ImageDraw, ImageFont

def create_gradient_image(filepath, text, width=800, height=500, shape="rect"):
    print(f"Creating image: {filepath}")
    os.makedirs(os.path.dirname(filepath), exist_ok=True)
    
    # Create image canvas
    img = Image.new('RGB', (width, height), color='#fafdfa')
    draw = ImageDraw.Draw(img)
    
    # Draw cinematic green gradient background
    for y in range(height):
        # Interpolate from deep forest green to organic green
        r = int(17 + (46 - 17) * (y / height))
        g = int(51 + (125 - 51) * (y / height))
        b = int(21 + (50 - 21) * (y / height))
        draw.line([(0, y), (width, y)], fill=(r, g, b))
        
    # Draw circular emblem if requested
    if shape == "circle":
        draw.ellipse([width//2 - 60, height//2 - 90, width//2 + 60, height//2 + 30], fill='#e8f5e9', outline='#2e7d32', width=4)
    
    # Try to load a clean default font
    try:
        font = ImageFont.load_default()
    except IOError:
        font = None
        
    # Draw text label in white/mint green
    draw.text((width // 2, height // 2 + 50), text, fill='#ffffff', anchor="mm")
    
    # Save image
    img.save(filepath)

# Target images dictionary: filename -> label
images_to_create = {
    'logo.png': ('Weda Gedara Logo', 150, 150, 'circle'),
    'footerlogo.png': ('Weda Gedara Logo', 150, 150, 'circle'),
    'developer_logo.png': ('100 International Universe Logo', 180, 80, 'rect'),
    'hero1.jpg': ('Authentic Ayurveda Center', 1920, 1080, 'rect'),
    'slide1.jpg': ('Holistic Healing & Detoxification', 1920, 1080, 'rect'),
    'slide3.jpg': ('Traditional Medicine Academy', 1920, 1080, 'rect'),
    'about1.jpg': ('Natural Herbal Medicine', 800, 600, 'rect'),
    'about2.jpg': ('Traditional Diagnostics', 600, 450, 'rect'),
    'aboutfirst.png': ('Ayurvedic Massage Oils', 800, 600, 'rect'),
    'aboutsecond.png': ('Healing Native Herbs', 800, 600, 'rect'),
    'consultation_service.jpg': ('Ayurvedic Consultation Tiers', 600, 450, 'rect'),
    'panchakarma_service.jpg': ('Panchakarma Detoxification', 600, 450, 'rect'),
    'education_service.jpg': ('Certified Academy Training', 600, 450, 'rect'),
    'thumbsup.jpg': ('Qualified Native Physicians', 800, 600, 'rect'),
    'bannerback.jpg': ('Traditional Medicine Promo', 1920, 500, 'rect'),
    'aboutpageheader.jpg': ('About Us Header Banner', 1920, 400, 'rect'),
    'aboutabdomain.jpg': ('Specialized Pathology Treatment', 600, 450, 'rect'),
    'aboutconsultation.jpg': ('Specialist Medical Consults', 600, 450, 'rect'),
    'aboutdeteoxy.jpg': ('Natural Rejuvenation Baths', 600, 450, 'rect'),
    'aboutlearning.jpg': ('Educational Study Seminars', 600, 450, 'rect'),
    'aboutbeauty.jpg': ('Organic Beauty Facials', 600, 450, 'rect'),
    'aboutyoga.jpg': ('Yoga & Spiritual Meditation', 600, 450, 'rect'),
    'academicheader.jpg': ('Academy Programs Header Banner', 1920, 400, 'rect'),
    'traningmaindesign.jpg': ('Academy Lecture Hall Session', 600, 450, 'rect'),
    'ayurvedahome.jpg': ('Ayurveda Philosophy Header', 1920, 400, 'rect'),
    'ayrvedaabout.jpg': ('Hela Wedakama History', 800, 600, 'rect'),
    'dosa1.jpg': ('Vata Dosha (Air + Space)', 600, 400, 'rect'),
    'dosa2.jpg': ('Pitta Dosha (Fire + Water)', 600, 400, 'rect'),
    'dosa3.jpg': ('Kapha Dosha (Water + Earth)', 600, 400, 'rect'),
    'wedakama1.jpg': ('Era of King Ravana', 400, 300, 'rect'),
    'wedakama2.jpg': ('Traditional Manuscripts', 400, 300, 'rect'),
    'wedakama3.jpg': ('Mihintale Ruins Hospital', 400, 300, 'rect'),
    'treatementsHome.jpg': ('Treatments Program Header', 1920, 400, 'rect'),
    'treatementmain1.jpg': ('Bio-Cleansing Detox', 600, 450, 'rect'),
    'treatementmain2.jpg': ('Traditional Body Massages', 600, 450, 'rect'),
    'treatementconsult.jpg': ('General Disease Recovery', 600, 450, 'rect'),
    'methods1.jpg': ('Shirodhara Pouring Therapy', 600, 400, 'rect'),
    'methods2.jpg': ('Abhyanga Oil Therapy', 600, 400, 'rect'),
    'method3.jpg': ('Njavarakizhi Sudation Therapy', 600, 400, 'rect'),
    'consultantHeader.jpg': ('Consultation Options Header', 1920, 400, 'rect'),
    'contactHeader.jpg': ('Contact Details Header Banner', 1920, 400, 'rect'),
    'blogHeader.jpg': ('Wellness Blog Articles Header', 1920, 400, 'rect'),
    'blog1.jpg': ('Intro to Ayurveda Basics', 600, 400, 'rect'),
    'blog2.jpg': ('Understanding Body Constitutions', 600, 400, 'rect'),
    'blog3.jpg': ('Native Sri Lankan Herbology', 600, 400, 'rect')
}

def generate_all():
    dest_dir = 'images'
    print(f"Generating core placeholder assets in {dest_dir}...")
    for filename, (text, w, h, shape) in images_to_create.items():
        filepath = os.path.join(dest_dir, filename)
        create_gradient_image(filepath, text, w, h, shape)
    
    # Try copying generated logo if available
    src_logo = '../weda_gedara_logo_1786008640655.jpg'
    if os.path.exists(src_logo):
        try:
            import shutil
            shutil.copy(src_logo, os.path.join(dest_dir, 'logo.png'))
            shutil.copy(src_logo, os.path.join(dest_dir, 'footerlogo.png'))
            print("Successfully copied high-quality generated logo into image assets.")
        except Exception as e:
            print(f"Could not copy generated logo: {e}")

if __name__ == '__main__':
    generate_all()
