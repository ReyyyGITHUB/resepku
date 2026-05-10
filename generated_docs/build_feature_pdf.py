from pathlib import Path

from PIL import Image as PilImage, ImageDraw, ImageFont
from reportlab.lib import colors
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import cm
from reportlab.platypus import (
    BaseDocTemplate,
    Frame,
    Image,
    PageBreak,
    PageTemplate,
    Paragraph,
    Spacer,
    Table,
    TableStyle,
)


ROOT = Path(__file__).resolve().parent
SCREENSHOT_DIR = ROOT / "screenshots"
OUTPUT_PDF = ROOT / "Dokumentasi_Halaman_dan_Flow_ResepKu.pdf"
FLOWCHART_IMAGE = ROOT / "sitemap_flowchart.png"


PAGES = [
    {
        "title": "1. Login",
        "image": "01-login.png",
        "desc": "Halaman masuk utama. User bisa login sebagai member, admin, atau masuk sebagai guest untuk browsing tanpa aksi sosial.",
        "links": [
            "Login -> Home untuk member",
            "Login -> Admin Dashboard untuk admin",
            "Login as Guest -> Home guest mode",
            "Create one -> Register",
            "Forgot password -> Lupa Password",
        ],
    },
    {
        "title": "2. Register",
        "image": "02-register.png",
        "desc": "Halaman pendaftaran akun baru dengan input nama, email, password, dan konfirmasi password.",
        "links": [
            "Register Account -> validasi akun -> Login",
            "Back -> Login",
        ],
    },
    {
        "title": "3. Lupa Password",
        "image": "03-forgot-password.png",
        "desc": "Halaman permintaan reset password. User mengisi email lalu sistem mengirim link reset melalui email.",
        "links": [
            "Kirim Link Reset -> proses email reset",
            "Kembali ke login -> Login",
        ],
    },
    {
        "title": "4. Home Guest",
        "image": "04-home-guest.png",
        "desc": "Katalog resep utama untuk pengunjung guest. Bisa browse, search, filter, dan buka detail resep tanpa harus login.",
        "links": [
            "Search bar -> Search",
            "Card resep -> Detail Resep",
            "Add Recipe -> Login",
            "Profile -> Login",
            "Pengaduan -> Login",
        ],
    },
    {
        "title": "5. Search",
        "image": "05-search.png",
        "desc": "Halaman hasil pencarian resep dengan filter kategori, difficulty, dan sorting.",
        "links": [
            "Card resep -> Detail Resep",
            "Clear filter -> Search default",
            "Sidebar menu -> halaman tujuan sesuai menu",
        ],
    },
    {
        "title": "6. Detail Resep",
        "image": "06-detail-recipe.png",
        "desc": "Halaman detail resep berisi foto, author, metadata resep, bahan, peralatan, langkah, komentar, dan aksi sosial.",
        "links": [
            "Back -> Home",
            "Author -> Profile author",
            "Like/Favorite/Rate -> update status sosial",
            "Laporkan -> buat pengaduan",
            "Komentar -> kirim komentar",
            "Resep terkait -> Detail Resep lain",
        ],
    },
    {
        "title": "7. Home User",
        "image": "07-home-user.png",
        "desc": "Versi home setelah user login. Navigasi utama dan aksi pribadi sudah aktif.",
        "links": [
            "Profile -> Profile",
            "My Recipes -> My Recipes",
            "Add Recipe -> Add Recipe",
            "Favorite -> Favorite",
            "Pengaduan Saya -> Pengaduan Saya",
        ],
    },
    {
        "title": "8. Profile",
        "image": "08-profile.png",
        "desc": "Halaman profil user yang menampilkan identitas, statistik akun, dan resep terbaru milik user.",
        "links": [
            "Edit Profile -> Edit Profile",
            "Recent recipe -> Detail Resep",
            "Home -> Home",
        ],
    },
    {
        "title": "9. Edit Profile",
        "image": "09-profile-edit.png",
        "desc": "Halaman pengaturan profil untuk update nama, bio, dan password.",
        "links": [
            "Simpan Perubahan -> Profile",
            "Cancel -> Profile",
        ],
    },
    {
        "title": "10. Add Recipe",
        "image": "10-add-recipe.png",
        "desc": "Form pembuatan resep baru. User mengisi informasi inti, upload foto, bahan, alat, dan langkah memasak.",
        "links": [
            "Simpan Resep -> Detail Resep baru",
            "Back -> Home",
        ],
    },
    {
        "title": "11. My Recipes",
        "image": "11-my-recipes.png",
        "desc": "Halaman pengelolaan resep pribadi. User dapat melihat, edit, membuka detail, atau menghapus resep miliknya.",
        "links": [
            "Edit -> Edit Recipe",
            "View -> Detail Resep",
            "Delete -> hapus resep",
        ],
    },
    {
        "title": "12. Favorite",
        "image": "12-favorite.png",
        "desc": "Daftar resep yang sudah disimpan user ke favorit.",
        "links": [
            "View -> Detail Resep",
            "Remove -> keluarkan dari favorit",
        ],
    },
    {
        "title": "13. Pengaduan Saya",
        "image": "13-my-reports.png",
        "desc": "Inbox laporan milik user. Menampilkan daftar laporan dan panel detail untuk status tiket.",
        "links": [
            "Item laporan -> Detail laporan di panel kanan",
            "Target link -> Detail Resep / Profile target",
        ],
    },
    {
        "title": "14. Admin Dashboard",
        "image": "14-admin-dashboard.png",
        "desc": "Ringkasan statistik platform untuk admin, termasuk total user, resep, komentar, likes, rating, dan pengaduan.",
        "links": [
            "Kelola pengguna -> Admin Pengguna",
            "Kelola resep -> Admin Resep",
            "Kelola pengaduan -> Admin Pengaduan",
        ],
    },
    {
        "title": "15. Admin Pengguna",
        "image": "15-admin-users.png",
        "desc": "Halaman admin untuk memfilter, melihat, mengaktifkan, menonaktifkan, dan menghapus akun user.",
        "links": [
            "Profil -> Profile publik user",
            "Aktifkan/Nonaktifkan -> ubah status akun",
            "Hapus -> hapus user",
        ],
    },
    {
        "title": "16. Admin Resep",
        "image": "16-admin-recipes.png",
        "desc": "Halaman admin untuk moderasi resep berdasarkan kategori, difficulty, popularitas, komentar, dan rating.",
        "links": [
            "Lihat -> Detail Resep",
            "Hapus -> hapus resep",
        ],
    },
    {
        "title": "17. Admin Pengaduan",
        "image": "17-admin-reports.png",
        "desc": "Halaman admin untuk memproses tiket pengaduan user berdasarkan status, target, dan kategori.",
        "links": [
            "Selesai -> update status tiket",
            "Tolak -> update status tiket",
            "Target -> Detail Resep / Profile target",
        ],
    },
]


FLOW_SECTIONS = [
    ("Auth Flow", [
        "Login -> Home / Admin Dashboard",
        "Login -> Register",
        "Login -> Lupa Password",
        "Register -> Login",
        "Lupa Password -> Reset Password -> Login",
    ]),
    ("Guest Flow", [
        "Home Guest -> Search",
        "Home Guest -> Detail Resep",
        "Home Guest -> Login jika ingin aksi sosial",
    ]),
    ("User Flow", [
        "Home User -> Profile",
        "Home User -> My Recipes",
        "Home User -> Add Recipe",
        "Home User -> Favorite",
        "Home User -> Pengaduan Saya",
        "Profile -> Edit Profile",
        "My Recipes -> Detail Resep / Edit Recipe / Delete",
        "Favorite -> Detail Resep",
        "Detail Resep -> Like / Favorite / Rate / Share / Komentar / Lapor",
    ]),
    ("Admin Flow", [
        "Admin Dashboard -> Admin Pengguna",
        "Admin Dashboard -> Admin Resep",
        "Admin Dashboard -> Admin Pengaduan",
        "Admin Pengguna -> Profile publik / ubah status / hapus",
        "Admin Resep -> Detail Resep / hapus",
        "Admin Pengaduan -> selesai / tolak / buka target",
    ]),
]


def make_styles():
    styles = getSampleStyleSheet()
    styles.add(ParagraphStyle(
        name="DocTitle",
        parent=styles["Title"],
        fontName="Helvetica-Bold",
        fontSize=22,
        leading=28,
        textColor=colors.HexColor("#183153"),
        spaceAfter=12,
    ))
    styles.add(ParagraphStyle(
        name="SectionTitle",
        parent=styles["Heading1"],
        fontName="Helvetica-Bold",
        fontSize=16,
        leading=20,
        textColor=colors.HexColor("#1F3B64"),
        spaceBefore=12,
        spaceAfter=8,
    ))
    styles.add(ParagraphStyle(
        name="Body",
        parent=styles["BodyText"],
        fontName="Helvetica",
        fontSize=10,
        leading=14,
        spaceAfter=6,
    ))
    styles.add(ParagraphStyle(
        name="Small",
        parent=styles["BodyText"],
        fontName="Helvetica",
        fontSize=9,
        leading=12,
        spaceAfter=4,
    ))
    return styles


def generate_flowchart_image():
    width, height = 1800, 2200
    image = PilImage.new("RGB", (width, height), "#FFF9EF")
    draw = ImageDraw.Draw(image)

    try:
        title_font = ImageFont.truetype("arialbd.ttf", 44)
        box_title_font = ImageFont.truetype("arialbd.ttf", 30)
        box_font = ImageFont.truetype("arial.ttf", 22)
    except OSError:
        title_font = ImageFont.load_default()
        box_title_font = ImageFont.load_default()
        box_font = ImageFont.load_default()

    draw.text((80, 40), "Sitemap dan Flow Halaman ResepKu", fill="#183153", font=title_font)
    draw.text((80, 100), "Panah menunjukkan tujuan halaman ketika menu atau tombol utama ditekan.", fill="#516173", font=box_font)

    nodes = {
        "login": (700, 170, 400, 90, "Login"),
        "register": (180, 340, 340, 90, "Register"),
        "forgot": (600, 340, 340, 90, "Lupa Password"),
        "home_guest": (1020, 340, 340, 90, "Home Guest"),
        "home_user": (1420, 340, 300, 90, "Home User"),
        "search": (1020, 560, 300, 90, "Search"),
        "detail": (1020, 780, 300, 90, "Detail Resep"),
        "profile": (620, 1000, 300, 90, "Profile"),
        "edit_profile": (620, 1220, 300, 90, "Edit Profile"),
        "my_recipes": (960, 1000, 300, 90, "My Recipes"),
        "add_recipe": (1300, 1000, 300, 90, "Add Recipe"),
        "favorite": (960, 1220, 300, 90, "Favorite"),
        "reports": (1300, 1220, 300, 90, "Pengaduan Saya"),
        "admin_dash": (180, 780, 340, 90, "Admin Dashboard"),
        "admin_users": (80, 1000, 300, 90, "Admin Pengguna"),
        "admin_recipes": (80, 1220, 300, 90, "Admin Resep"),
        "admin_reports": (80, 1440, 300, 90, "Admin Pengaduan"),
    }

    groups = [
        ("AUTH", (60, 150, 1660, 320), "#FFF0CC"),
        ("USER FLOW", (560, 500, 1120, 900), "#F7F0FF"),
        ("ADMIN FLOW", (60, 720, 480, 860), "#EFF7FF"),
    ]

    for label, rect, fill in groups:
        x, y, w, h = rect
        draw.rounded_rectangle((x, y, x + w, y + h), radius=32, outline="#D9C59A", width=3, fill=fill)
        draw.text((x + 22, y + 16), label, fill="#7A5C1E", font=box_title_font)

    def draw_box(key, fill="#FFFFFF", outline="#D6A235"):
        x, y, w, h, label = nodes[key]
        draw.rounded_rectangle((x, y, x + w, y + h), radius=24, fill=fill, outline=outline, width=4)
        bbox = draw.textbbox((0, 0), label, font=box_title_font)
        tw = bbox[2] - bbox[0]
        th = bbox[3] - bbox[1]
        draw.text((x + (w - tw) / 2, y + (h - th) / 2 - 4), label, fill="#183153", font=box_title_font)

    for key in nodes:
        fill = "#FFFFFF"
        if key.startswith("admin"):
            fill = "#F8FCFF"
        elif key in {"home_guest", "home_user", "search", "detail", "profile", "edit_profile", "my_recipes", "add_recipe", "favorite", "reports"}:
            fill = "#FFFEFB"
        draw_box(key, fill=fill)

    def bottom_center(key):
        x, y, w, h, _ = nodes[key]
        return (x + w / 2, y + h)

    def top_center(key):
        x, y, w, _, _ = nodes[key]
        return (x + w / 2, y)

    def left_center(key):
        x, y, _, h, _ = nodes[key]
        return (x, y + h / 2)

    def right_center(key):
        x, y, w, h, _ = nodes[key]
        return (x + w, y + h / 2)

    def arrow(start, end, via=None, color="#5F6F86", width=5):
        points = [start]
        if via:
            points.extend(via)
        points.append(end)
        draw.line(points, fill=color, width=width)
        x1, y1 = points[-2]
        x2, y2 = points[-1]
        size = 16
        if abs(x2 - x1) > abs(y2 - y1):
            if x2 > x1:
                head = [(x2, y2), (x2 - size, y2 - size / 2), (x2 - size, y2 + size / 2)]
            else:
                head = [(x2, y2), (x2 + size, y2 - size / 2), (x2 + size, y2 + size / 2)]
        else:
            if y2 > y1:
                head = [(x2, y2), (x2 - size / 2, y2 - size), (x2 + size / 2, y2 - size)]
            else:
                head = [(x2, y2), (x2 - size / 2, y2 + size), (x2 + size / 2, y2 + size)]
        draw.polygon(head, fill=color)

    arrow(bottom_center("login"), top_center("register"))
    arrow(bottom_center("login"), top_center("forgot"))
    arrow(bottom_center("login"), top_center("home_guest"))
    arrow(bottom_center("login"), top_center("home_user"))
    arrow(left_center("home_user"), right_center("admin_dash"), via=[(1300, 385), (1300, 825), (520, 825)])
    arrow(bottom_center("home_guest"), top_center("search"))
    arrow(bottom_center("search"), top_center("detail"))
    arrow(bottom_center("home_user"), top_center("profile"), via=[(1570, 530), (770, 530), (770, 1000)])
    arrow(bottom_center("home_user"), top_center("my_recipes"), via=[(1570, 530), (1110, 530), (1110, 1000)])
    arrow(bottom_center("home_user"), top_center("add_recipe"), via=[(1570, 530), (1450, 530), (1450, 1000)])
    arrow(bottom_center("profile"), top_center("edit_profile"))
    arrow(bottom_center("my_recipes"), top_center("favorite"))
    arrow(bottom_center("add_recipe"), top_center("reports"), via=[(1450, 1090), (1450, 1220)])
    arrow(left_center("detail"), right_center("profile"), via=[(980, 825), (980, 1045), (920, 1045)])
    arrow(bottom_center("detail"), top_center("favorite"), via=[(1170, 870), (1110, 870), (1110, 1220)])
    arrow(right_center("admin_dash"), top_center("admin_users"), via=[(520, 825), (520, 1045), (230, 1045)])
    arrow(right_center("admin_dash"), top_center("admin_recipes"), via=[(520, 825), (520, 1265), (230, 1265)])
    arrow(right_center("admin_dash"), top_center("admin_reports"), via=[(520, 825), (520, 1485), (230, 1485)])

    legend_x, legend_y = 80, 1740
    draw.text((legend_x, legend_y), "Keterangan:", fill="#183153", font=box_title_font)
    legends = [
        ("Auth entry point", "#FFF0CC"),
        ("User pages", "#FFFEFB"),
        ("Admin pages", "#F8FCFF"),
    ]
    current_y = legend_y + 60
    for text, fill in legends:
        draw.rounded_rectangle((legend_x, current_y, legend_x + 40, current_y + 30), radius=8, fill=fill, outline="#D6A235", width=2)
        draw.text((legend_x + 60, current_y + 2), text, fill="#516173", font=box_font)
        current_y += 50

    image.save(FLOWCHART_IMAGE)


def fit_image(image_path, max_width, max_height):
    img = Image(str(image_path))
    img._restrictSize(max_width, max_height)
    return img


def box_paragraph(text, styles):
    return Table(
        [[Paragraph(text, styles["Small"])]],
        colWidths=[17.2 * cm],
        style=TableStyle([
            ("BACKGROUND", (0, 0), (-1, -1), colors.HexColor("#F4F7FB")),
            ("BOX", (0, 0), (-1, -1), 0.75, colors.HexColor("#D6E0EE")),
            ("LEFTPADDING", (0, 0), (-1, -1), 8),
            ("RIGHTPADDING", (0, 0), (-1, -1), 8),
            ("TOPPADDING", (0, 0), (-1, -1), 6),
            ("BOTTOMPADDING", (0, 0), (-1, -1), 6),
        ]),
    )


def build_story():
    styles = make_styles()
    story = []
    generate_flowchart_image()

    story.append(Paragraph("Dokumentasi Halaman dan Flow ResepKu", styles["DocTitle"]))
    story.append(Paragraph(
        "Dokumen ini menjelaskan halaman utama aplikasi ResepKu dengan screenshot aktual, "
        "fungsi setiap halaman, dan sitemap flow untuk menjelaskan jika sebuah menu atau tombol ditekan akan menuju ke mana.",
        styles["Body"],
    ))
    story.append(box_paragraph(
        "Akses yang didokumentasikan: guest, user login, dan admin. Screenshot diambil dari aplikasi lokal yang berjalan.",
        styles,
    ))
    story.append(Spacer(1, 0.4 * cm))

    for item in PAGES:
        story.append(Paragraph(item["title"], styles["SectionTitle"]))
        story.append(Paragraph(item["desc"], styles["Body"]))
        image_path = SCREENSHOT_DIR / item["image"]
        if image_path.exists():
            story.append(fit_image(image_path, 17.5 * cm, 13.0 * cm))
            story.append(Spacer(1, 0.25 * cm))
        link_lines = "<br/>".join([f"&bull; {line}" for line in item["links"]])
        story.append(box_paragraph(f"<b>Flow klik dari halaman ini:</b><br/>{link_lines}", styles))
        story.append(PageBreak())

    story.append(Paragraph("Sitemap dan Flow Antar Halaman", styles["DocTitle"]))
    story.append(Paragraph(
        "Bagian ini merangkum alur utama navigasi agar customer bisa cepat memahami hubungan antar layar.",
        styles["Body"],
    ))

    for title, lines in FLOW_SECTIONS:
        story.append(Spacer(1, 0.15 * cm))
        story.append(Paragraph(title, styles["SectionTitle"]))
        flow_text = "<br/>".join([f"&bull; {line}" for line in lines])
        story.append(box_paragraph(flow_text, styles))

    story.append(Spacer(1, 0.5 * cm))
    story.append(Paragraph("Diagram Wireframe Flowchart", styles["SectionTitle"]))
    story.append(Paragraph(
        "Diagram berikut memperlihatkan sitemap utama dan arah perpindahan halaman ketika menu atau tombol utama ditekan.",
        styles["Body"],
    ))
    if FLOWCHART_IMAGE.exists():
        story.append(fit_image(FLOWCHART_IMAGE, 18.0 * cm, 23.0 * cm))

    return story


def add_page_number(canvas, doc):
    canvas.saveState()
    canvas.setFont("Helvetica", 9)
    canvas.setFillColor(colors.HexColor("#6B778C"))
    canvas.drawRightString(19.2 * cm, 1.2 * cm, f"Page {doc.page}")
    canvas.restoreState()


def build_pdf():
    doc = BaseDocTemplate(
        str(OUTPUT_PDF),
        pagesize=A4,
        leftMargin=1.5 * cm,
        rightMargin=1.5 * cm,
        topMargin=1.5 * cm,
        bottomMargin=1.5 * cm,
    )
    frame = Frame(doc.leftMargin, doc.bottomMargin, doc.width, doc.height, id="normal")
    template = PageTemplate(id="main", frames=[frame], onPage=add_page_number)
    doc.addPageTemplates([template])
    doc.build(build_story())


if __name__ == "__main__":
    build_pdf()
