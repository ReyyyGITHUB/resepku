from __future__ import annotations

import math
import os
from pathlib import Path
from typing import Iterable, Sequence

from PIL import Image, ImageDraw, ImageFilter, ImageFont
from reportlab.lib.utils import ImageReader
from reportlab.pdfgen import canvas


ROOT = Path(__file__).resolve().parent
OUT_DIR = ROOT / "generated_docs" / "resepku_fitur"
PREVIEW_DIR = OUT_DIR / "preview"
PDF_PATH = OUT_DIR / "ResepKu_Panduan_Fitur.pdf"

DPI = 240
PAGE_W = int(8.27 * DPI)
PAGE_H = int(11.69 * DPI)

COLORS = {
    "bg": "#f7f2e8",
    "panel": "#ffffff",
    "panel_soft": "#fff8ec",
    "orange": "#fb9b00",
    "orange_dark": "#d77f00",
    "gold": "#d4af37",
    "black": "#151515",
    "gray": "#5c5c5c",
    "soft_gray": "#8d8d8d",
    "line": "#e7dcc8",
    "accent": "#fff0cf",
    "mint": "#e9f5ef",
    "red_soft": "#fce7e5",
}

ASSET_LOGO = ROOT / "assets" / "img" / "resepku-logo.png"
ASSET_CHEF = ROOT / "assets" / "img" / "chef-illustration.png"


def load_font(size: int, bold: bool = False) -> ImageFont.FreeTypeFont:
    windir = os.environ.get("WINDIR", r"C:\Windows")
    font_path = Path(windir) / "Fonts" / ("arialbd.ttf" if bold else "arial.ttf")
    try:
        return ImageFont.truetype(str(font_path), size=size)
    except Exception:
        return ImageFont.load_default()


def text_size(draw: ImageDraw.ImageDraw, text: str, font: ImageFont.ImageFont) -> tuple[int, int]:
    bbox = draw.textbbox((0, 0), text, font=font)
    return bbox[2] - bbox[0], bbox[3] - bbox[1]


def wrap_text(draw: ImageDraw.ImageDraw, text: str, font: ImageFont.ImageFont, max_width: int) -> list[str]:
    lines: list[str] = []
    for raw_para in text.split("\n"):
        if not raw_para.strip():
            lines.append("")
            continue
        words = raw_para.split()
        current = words[0]
        for word in words[1:]:
            trial = f"{current} {word}"
            if draw.textlength(trial, font=font) <= max_width:
                current = trial
            else:
                lines.append(current)
                current = word
        lines.append(current)
    return lines


def draw_wrapped_text(
    draw: ImageDraw.ImageDraw,
    xy: tuple[int, int],
    text: str,
    font: ImageFont.ImageFont,
    fill: str,
    max_width: int,
    line_gap: int = 14,
    paragraph_gap: int = 18,
    bullet_prefix: str | None = None,
) -> int:
    x, y = xy
    total = 0
    paras = text.split("\n")
    for idx, para in enumerate(paras):
        if not para.strip():
            y += paragraph_gap
            total += paragraph_gap
            continue
        lines = wrap_text(draw, para, font, max_width)
        for line_index, line in enumerate(lines):
            prefix = bullet_prefix if bullet_prefix and line_index == 0 else ""
            draw.text((x, y), f"{prefix}{line}", fill=fill, font=font)
            h = text_size(draw, line, font)[1]
            y += h + line_gap
            total += h + line_gap
        y += paragraph_gap - line_gap
        total += paragraph_gap - line_gap
        if idx != len(paras) - 1:
            y += 0
    return total


def rounded_rect(draw: ImageDraw.ImageDraw, box: tuple[int, int, int, int], fill: str, outline: str | None = None, width: int = 1, radius: int = 28):
    draw.rounded_rectangle(box, radius=radius, fill=fill, outline=outline, width=width)


def make_canvas() -> Image.Image:
    img = Image.new("RGB", (PAGE_W, PAGE_H), COLORS["bg"])
    d = ImageDraw.Draw(img, "RGBA")
    d.rectangle((0, 0, PAGE_W, 260), fill=(251, 155, 0, 34))
    d.ellipse((-220, -160, 880, 760), fill=(251, 155, 0, 70))
    d.ellipse((PAGE_W - 940, -240, PAGE_W + 260, 840), fill=(212, 175, 55, 66))
    d.ellipse((PAGE_W - 760, PAGE_H - 820, PAGE_W + 180, PAGE_H + 140), fill=(251, 155, 0, 34))
    d.ellipse((-200, PAGE_H - 760, 760, PAGE_H + 160), fill=(212, 175, 55, 42))
    return img


def paste_fit(base: Image.Image, asset: Path, box: tuple[int, int, int, int], contain: bool = True):
    try:
        src = Image.open(asset).convert("RGBA")
    except Exception:
        return
    x1, y1, x2, y2 = box
    w = x2 - x1
    h = y2 - y1
    if contain:
        src.thumbnail((w, h), Image.Resampling.LANCZOS)
        px = x1 + (w - src.width) // 2
        py = y1 + (h - src.height) // 2
    else:
        src = src.resize((w, h), Image.Resampling.LANCZOS)
        px, py = x1, y1
    base.alpha_composite(src, (px, py))


def draw_header(draw: ImageDraw.ImageDraw, page_no: int, title: str, subtitle: str | None = None):
    logo = Image.open(ASSET_LOGO).convert("RGBA")
    logo.thumbnail((96, 96), Image.Resampling.LANCZOS)
    page_img.alpha_composite(logo, (72, 60))

    f_small = load_font(28, bold=True)
    f_title = load_font(50, bold=True)
    f_sub = load_font(24, bold=False)
    draw.text((184, 68), "ResepKu", font=f_small, fill=COLORS["orange_dark"])
    draw.text((72, 150), title, font=f_title, fill=COLORS["black"])
    if subtitle:
        draw.text((72, 210), subtitle, font=f_sub, fill=COLORS["gray"])
    draw.rounded_rectangle((PAGE_W - 284, 58, PAGE_W - 76, 124), radius=26, fill=COLORS["panel"], outline=COLORS["line"])
    draw.text((PAGE_W - 240, 82), f"Halaman {page_no}", font=load_font(24, bold=True), fill=COLORS["orange_dark"])


def draw_footer(draw: ImageDraw.ImageDraw, text: str = "Dokumen ringkas fitur yang tersedia saat ini"):
    draw.line((72, PAGE_H - 124, PAGE_W - 72, PAGE_H - 124), fill=COLORS["line"], width=3)
    draw.text((72, PAGE_H - 98), text, font=load_font(20), fill=COLORS["soft_gray"])
    draw.text((PAGE_W - 230, PAGE_H - 98), "ResepKu", font=load_font(20, bold=True), fill=COLORS["soft_gray"])


def card(draw: ImageDraw.ImageDraw, box: tuple[int, int, int, int], title: str, body: str | Sequence[str], fill: str = COLORS["panel"], outline: str = COLORS["line"], accent: str = COLORS["orange"]):
    rounded_rect(draw, box, fill=fill, outline=outline, width=3, radius=30)
    x1, y1, x2, y2 = box
    draw.rounded_rectangle((x1 + 24, y1 + 22, x1 + 84, y1 + 84), radius=20, fill=accent)
    draw.text((x1 + 40, y1 + 34), "•", font=load_font(32, bold=True), fill="#ffffff")
    draw.text((x1 + 102, y1 + 30), title, font=load_font(30, bold=True), fill=COLORS["black"])
    if isinstance(body, str):
        draw_wrapped_text(draw, (x1 + 36, y1 + 100), body, load_font(23), COLORS["gray"], x2 - x1 - 72, line_gap=12, paragraph_gap=12)
    else:
        yy = y1 + 100
        f = load_font(23)
        for item in body:
            lines = wrap_text(draw, item, f, x2 - x1 - 104)
            draw.text((x1 + 36, yy), "•", font=load_font(26, bold=True), fill=COLORS["orange_dark"])
            draw_wrapped_text(draw, (x1 + 62, yy), item, f, COLORS["gray"], x2 - x1 - 96, line_gap=10, paragraph_gap=8)
            yy += (len(lines) * 38) + 8


def badge(draw: ImageDraw.ImageDraw, box: tuple[int, int, int, int], text: str, fill: str, text_fill: str = COLORS["black"]):
    rounded_rect(draw, box, fill=fill, outline=None, radius=999)
    x1, y1, x2, y2 = box
    f = load_font(20, bold=True)
    tw, th = text_size(draw, text, f)
    draw.text((x1 + (x2 - x1 - tw) // 2, y1 + (y2 - y1 - th) // 2 - 2), text, font=f, fill=text_fill)


def pill_row(draw: ImageDraw.ImageDraw, x: int, y: int, labels: Sequence[str], fill: str = COLORS["panel"]):
    f = load_font(20, bold=True)
    cur = x
    for label in labels:
        w = int(draw.textlength(label, font=f)) + 54
        rounded_rect(draw, (cur, y, cur + w, y + 44), fill=fill, outline=COLORS["line"], width=2, radius=999)
        draw.text((cur + 22, y + 10), label, font=f, fill=COLORS["black"])
        cur += w + 16


def draw_simple_table(
    draw: ImageDraw.ImageDraw,
    box: tuple[int, int, int, int],
    headers: Sequence[str],
    rows: Sequence[Sequence[str]],
    col_widths: Sequence[int],
):
    x1, y1, x2, y2 = box
    header_h = 74
    row_pad_y = 16
    f_head = load_font(22, bold=True)
    f_body = load_font(21)
    y = y1
    total_width = sum(col_widths)
    table_w = min(total_width, x2 - x1)
    col_widths = list(col_widths)
    scale = table_w / total_width
    col_widths = [int(w * scale) for w in col_widths[:-1]] + [table_w - sum(int(w * scale) for w in col_widths[:-1])]
    rounded_rect(draw, (x1, y1, x1 + table_w, y2), fill=COLORS["panel"], outline=COLORS["line"], width=3, radius=28)
    draw.rounded_rectangle((x1, y1, x1 + table_w, y1 + header_h), radius=28, fill=COLORS["accent"])
    cur_x = x1
    for i, head in enumerate(headers):
        draw.text((cur_x + 18, y1 + 22), head, font=f_head, fill=COLORS["black"])
        cur_x += col_widths[i]
        if i != len(headers) - 1:
            draw.line((cur_x, y1, cur_x, y2), fill=COLORS["line"], width=2)

    y = y1 + header_h
    for row in rows:
        cell_lines = []
        max_lines = 1
        for i, cell in enumerate(row):
            lines = wrap_text(draw, cell, f_body, col_widths[i] - 36)
            cell_lines.append(lines)
            max_lines = max(max_lines, len(lines))
        row_h = max(78, max_lines * 36 + row_pad_y * 2)
        draw.line((x1, y, x1 + table_w, y), fill=COLORS["line"], width=2)
        cur_x = x1
        for i, lines in enumerate(cell_lines):
            tx = cur_x + 18
            ty = y + row_pad_y
            for line in lines:
                draw.text((tx, ty), line, font=f_body, fill=COLORS["gray"])
                ty += 34
            cur_x += col_widths[i]
            if i != len(cell_lines) - 1:
                draw.line((cur_x, y, cur_x, y + row_h), fill=COLORS["line"], width=2)
        y += row_h
        if y > y2:
            break


def page_one() -> Image.Image:
    global page_img
    page_img = make_canvas().convert("RGBA")
    d = ImageDraw.Draw(page_img)
    draw_header(d, 1, "Panduan Fitur ResepKu", "Ringkasan fitur yang sudah tersedia saat ini")

    rounded_rect(d, (72, 300, PAGE_W - 72, 1240), fill=COLORS["panel"], outline=COLORS["line"], width=3, radius=42)
    rounded_rect(d, (104, 332, 870, 1208), fill=COLORS["panel_soft"], outline=None, radius=36)
    rounded_rect(d, (920, 332, PAGE_W - 104, 1208), fill="#fffdf7", outline=None, radius=36)

    badge(d, (132, 366, 442, 416), "PDF fitur", COLORS["orange"], "#ffffff")
    d.text((132, 448), "Untuk pembaca awam", font=load_font(54, bold=True), fill=COLORS["black"])
    title = "Apa saja yang bisa dilakukan di ResepKu?"
    draw_wrapped_text(d, (132, 540), title, load_font(76, bold=True), COLORS["orange_dark"], 620, line_gap=10, paragraph_gap=12)
    body = (
        "Dokumen ini menjelaskan fitur yang sudah aktif di aplikasi, "
        "mulai dari melihat resep, mencari resep, berinteraksi dengan komunitas, "
        "sampai mengelola akun dan panel admin."
    )
    draw_wrapped_text(d, (132, 760), body, load_font(28), COLORS["gray"], 630, line_gap=12, paragraph_gap=14)
    pill_row(d, 132, 1028, ["Lihat resep", "Cari cepat", "Interaksi komunitas", "Kelola akun"])

    paste_fit(page_img, ASSET_CHEF, (990, 376, PAGE_W - 138, 1138))
    d.rounded_rectangle((980, 350, PAGE_W - 120, 1160), radius=40, outline=COLORS["line"], width=3)
    d.text((1006, 1020), "ResepKu hadir untuk memudahkan orang\nmenemukan dan membagikan resep.", font=load_font(32, bold=True), fill=COLORS["black"], spacing=8)
    d.text((1006, 1100), "Simple, ramah, dan mudah dipakai.", font=load_font(24), fill=COLORS["gray"])

    card_y = 1310
    card_h = 560
    gap = 32
    w = (PAGE_W - 72 * 2 - gap * 2) // 3
    card(d, (72, card_y, 72 + w, card_y + card_h), "Baca resep", "Buka halaman utama, lihat foto resep, lalu lanjut ke detail resep.")
    card(d, (72 + w + gap, card_y, 72 + w * 2 + gap, card_y + card_h), "Interaksi", "Like, komentar, rating, favorit, follow, dan share tersedia setelah login.")
    card(d, (72 + (w + gap) * 2, card_y, PAGE_W - 72, card_y + card_h), "Kelola akun", "Daftar, masuk, reset sandi, edit profil, dan buka panel admin sesuai role.")

    rounded_rect(d, (72, 1950, PAGE_W - 72, 2190), fill=COLORS["accent"], outline=COLORS["gold"], width=3, radius=32)
    d.text((118, 2014), "Catatan penting:", font=load_font(28, bold=True), fill=COLORS["black"])
    d.text((360, 2014), "Fitur yang ditulis di sini hanya yang sudah tersedia pada kode saat ini.", font=load_font(26), fill=COLORS["gray"])

    draw_footer(d)
    return page_img.convert("RGB")


def page_two() -> Image.Image:
    global page_img
    page_img = make_canvas().convert("RGBA")
    d = ImageDraw.Draw(page_img)
    draw_header(d, 2, "Gambaran Produk", "Siapa yang memakai ResepKu dan apa manfaatnya")

    rounded_rect(d, (72, 300, 1330, 1220), fill=COLORS["panel"], outline=COLORS["line"], width=3, radius=36)
    rounded_rect(d, (1360, 300, PAGE_W - 72, 1220), fill=COLORS["panel_soft"], outline=COLORS["line"], width=3, radius=36)
    d.text((116, 360), "Apa itu ResepKu?", font=load_font(42, bold=True), fill=COLORS["black"])
    body = (
        "ResepKu adalah platform berbagi resep yang membantu orang menemukan resep "
        "masakan, menyimpan resep favorit, dan berinteraksi dengan komunitas. "
        "Pengguna bisa menjadi pengunjung, pengguna login, atau admin."
    )
    draw_wrapped_text(d, (116, 450), body, load_font(28), COLORS["gray"], 1160, line_gap=12, paragraph_gap=14)
    badge(d, (116, 760, 408, 814), "Mudah dipahami", COLORS["mint"])
    badge(d, (428, 760, 760, 814), "Fokus ke resep", COLORS["accent"])
    badge(d, (772, 760, 1024, 814), "Untuk semua usia", COLORS["panel_soft"])
    rounded_rect(d, (116, 884, 1240, 1128), fill="#fdfbf4", outline=COLORS["line"], width=2, radius=30)
    d.text((148, 920), "Manfaat utamanya", font=load_font(30, bold=True), fill=COLORS["black"])
    for i, txt in enumerate([
        "Mencari resep jadi lebih cepat.",
        "Komunitas bisa saling memberi masukan.",
        "Akun pribadi membantu pengguna menyimpan jejak resep.",
        "Admin punya alat untuk menjaga konten tetap rapi.",
    ]):
        d.text((148, 976 + i * 46), "✓", font=load_font(26, bold=True), fill=COLORS["orange_dark"])
        d.text((188, 976 + i * 46), txt, font=load_font(24), fill=COLORS["gray"])

    d.text((1408, 360), "Siapa bisa apa?", font=load_font(38, bold=True), fill=COLORS["black"])
    role_cards = [
        ("Pengunjung", "Lihat resep, cari/filter, buka detail, share, dan kirim pengaduan.", "Tidak perlu login untuk membaca."),
        ("Pengguna login", "Semua fitur pengunjung plus like, komentar, rating, favorit, follow, dan buat resep.", "Harus masuk akun lebih dulu."),
        ("Admin", "Kelola pengguna, resep, dan laporan dari panel admin.", "Hanya akun dengan role admin."),
    ]
    ry = 430
    for role, action_text, note_text in role_cards:
        rounded_rect(d, (1408, ry, PAGE_W - 116, ry + 204), fill="#fffaf0", outline=COLORS["line"], width=2, radius=26)
        badge(d, (1430, ry + 18, 1600, ry + 62), role, COLORS["orange"], "#ffffff")
        draw_wrapped_text(d, (1430, ry + 82), action_text, load_font(22), COLORS["gray"], 360, line_gap=10, paragraph_gap=10)
        draw_wrapped_text(d, (1430, ry + 152), note_text, load_font(20), COLORS["soft_gray"], 360, line_gap=10, paragraph_gap=10)
        ry += 220

    rounded_rect(d, (72, 1330, PAGE_W - 72, 1500), fill=COLORS["panel"], outline=COLORS["line"], width=3, radius=30)
    d.text((118, 1378), "Intinya", font=load_font(30, bold=True), fill=COLORS["orange_dark"])
    d.text((278, 1378), "Pengunjung tetap bisa membaca resep, tetapi beberapa aksi butuh login.", font=load_font(26), fill=COLORS["gray"])

    draw_footer(d)
    return page_img.convert("RGB")


def page_three() -> Image.Image:
    global page_img
    page_img = make_canvas().convert("RGBA")
    d = ImageDraw.Draw(page_img)
    draw_header(d, 3, "Fitur untuk Pengunjung", "Fitur yang bisa dipakai tanpa login")

    boxes = [
        ((72, 320, 1188, 820), "Jelajah resep", [
            "Lihat daftar resep di beranda.",
            "Buka detail resep untuk membaca foto, bahan, alat, dan langkah memasak.",
            "Cocok untuk orang yang sedang mencari ide masakan."
        ], COLORS["panel"]),
        ((1248, 320, PAGE_W - 72, 820), "Cari dan filter", [
            "Cari berdasarkan nama resep.",
            "Filter berdasarkan kategori, tingkat kesulitan, dan waktu memasak.",
            "Memudahkan pengguna menemukan resep yang pas."
        ], COLORS["panel_soft"]),
        ((72, 880, 1188, 1380), "Resep detail", [
            "Tampilkan foto utama resep.",
            "Tampilkan bahan, alat, langkah, rating, komentar, dan resep terkait.",
            "Halaman ini adalah pusat informasi resep."
        ], COLORS["panel"]),
        ((1248, 880, PAGE_W - 72, 1380), "Bagikan dan laporkan", [
            "Salin link resep untuk dibagikan ke orang lain.",
            "Laporkan resep atau profil jika ada konten yang bermasalah.",
            "Pengaduan akan diteruskan ke admin."
        ], COLORS["panel_soft"]),
    ]
    for box, title, items, fill in boxes:
        rounded_rect(d, box, fill=fill, outline=COLORS["line"], width=3, radius=34)
        x1, y1, x2, y2 = box
        badge(d, (x1 + 34, y1 + 34, x1 + 176, y1 + 82), "Fitur", COLORS["orange"], "#ffffff")
        d.text((x1 + 34, y1 + 110), title, font=load_font(34, bold=True), fill=COLORS["black"])
        yy = y1 + 178
        for item in items:
            d.text((x1 + 36, yy), "•", font=load_font(28, bold=True), fill=COLORS["orange_dark"])
            draw_wrapped_text(d, (x1 + 74, yy), item, load_font(24), COLORS["gray"], x2 - x1 - 120, line_gap=10, paragraph_gap=10)
            yy += 104

    rounded_rect(d, (72, 1448, PAGE_W - 72, 1700), fill=COLORS["accent"], outline=COLORS["gold"], width=3, radius=32)
    d.text((116, 1510), "Ringkasnya", font=load_font(30, bold=True), fill=COLORS["black"])
    d.text((338, 1510), "Tanpa login, ResepKu tetap berguna untuk membaca dan menelusuri resep.", font=load_font(27), fill=COLORS["gray"])

    draw_footer(d)
    return page_img.convert("RGB")


def page_four() -> Image.Image:
    global page_img
    page_img = make_canvas().convert("RGBA")
    d = ImageDraw.Draw(page_img)
    draw_header(d, 4, "Fitur untuk Pengguna Login", "Akun pribadi, resep sendiri, dan aksi komunitas")

    rounded_rect(d, (72, 320, 1210, 1450), fill=COLORS["panel"], outline=COLORS["line"], width=3, radius=34)
    rounded_rect(d, (1270, 320, PAGE_W - 72, 1450), fill=COLORS["panel_soft"], outline=COLORS["line"], width=3, radius=34)
    d.text((116, 378), "Akun dan profil", font=load_font(40, bold=True), fill=COLORS["black"])
    account_items = [
        "Register dengan nama, email, dan kata sandi.",
        "Login untuk masuk ke akun.",
        "Lupa sandi dan reset lewat email.",
        "Edit nama, foto profil, bio, dan password.",
        "Lihat profil sendiri dan daftar pengaduan saya.",
    ]
    yy = 478
    for item in account_items:
        d.text((116, yy), "✓", font=load_font(28, bold=True), fill=COLORS["orange_dark"])
        draw_wrapped_text(d, (158, yy), item, load_font(25), COLORS["gray"], 980, line_gap=10, paragraph_gap=10)
        yy += 128

    d.text((1314, 378), "Resep dan aktivitas", font=load_font(40, bold=True), fill=COLORS["black"])
    recipe_items = [
        "Buat resep baru dengan foto, bahan, alat, langkah, waktu, porsi, kategori, dan tingkat kesulitan.",
        "Edit atau hapus resep milik sendiri.",
        "Like, komentar, rating, favorit, dan follow akun lain.",
        "Buka profil publik pengguna lain.",
        "Simpan resep yang disukai untuk dibuka lagi nanti.",
    ]
    yy = 478
    for item in recipe_items:
        d.text((1314, yy), "✓", font=load_font(28, bold=True), fill=COLORS["orange_dark"])
        draw_wrapped_text(d, (1356, yy), item, load_font(25), COLORS["gray"], 980, line_gap=10, paragraph_gap=10)
        yy += 128

    rounded_rect(d, (72, 1542, PAGE_W - 72, 1768), fill=COLORS["accent"], outline=COLORS["gold"], width=3, radius=32)
    d.text((118, 1604), "Aturan penting", font=load_font(30, bold=True), fill=COLORS["black"])
    d.text((380, 1604), "Aksi sosial hanya muncul setelah login. Resep hanya bisa diubah oleh pemiliknya atau admin.", font=load_font(26), fill=COLORS["gray"])

    draw_footer(d)
    return page_img.convert("RGB")


def page_five() -> Image.Image:
    global page_img
    page_img = make_canvas().convert("RGBA")
    d = ImageDraw.Draw(page_img)
    draw_header(d, 5, "Interaksi dan Profil", "Fitur yang membuat ResepKu terasa seperti komunitas")

    rounded_rect(d, (72, 320, 1240, 1460), fill=COLORS["panel"], outline=COLORS["line"], width=3, radius=34)
    rounded_rect(d, (1288, 320, PAGE_W - 72, 1460), fill=COLORS["panel_soft"], outline=COLORS["line"], width=3, radius=34)
    d.text((116, 378), "Aksi komunitas", font=load_font(40, bold=True), fill=COLORS["black"])
    action_cards = [
        ("Like", "Tandai resep yang disukai."),
        ("Komentar", "Tulis pendapat atau pertanyaan di bawah resep."),
        ("Rating", "Beri nilai bintang 1 sampai 5."),
        ("Favorit", "Simpan resep agar mudah dibuka lagi."),
        ("Follow", "Ikuti akun pembuat resep yang menarik."),
        ("Share", "Bagikan link resep ke orang lain."),
    ]
    x0, y0 = 116, 478
    card_w = 320
    card_h = 176
    gap_x = 24
    gap_y = 22
    for idx, (title, desc) in enumerate(action_cards):
        col = idx % 3
        row = idx // 3
        x = x0 + col * (card_w + gap_x)
        y = y0 + row * (card_h + gap_y)
        rounded_rect(d, (x, y, x + card_w, y + card_h), fill="#fffdf8", outline=COLORS["line"], width=2, radius=24)
        pill_w = max(116, int(d.textlength(title, font=load_font(22, bold=True))) + 42)
        badge(d, (x + 18, y + 18, x + 18 + pill_w, y + 60), title, COLORS["orange"], "#ffffff")
        draw_wrapped_text(d, (x + 18, y + 78), desc, load_font(22), COLORS["gray"], card_w - 36, line_gap=10, paragraph_gap=8)

    d.text((1314, 378), "Apa yang terlihat di profil?", font=load_font(40, bold=True), fill=COLORS["black"])
    rounded_rect(d, (1314, 476, PAGE_W - 116, 710), fill="#fffdf8", outline=COLORS["line"], width=2, radius=28)
    stats = [("Resep", "Jumlah resep yang diunggah"), ("Follower", "Orang yang mengikuti akun"), ("Following", "Akun yang diikuti")]
    sw = (PAGE_W - 116 - 1314 - 48) // 3
    for i, (label, desc) in enumerate(stats):
        x = 1340 + i * (sw + 8)
        rounded_rect(d, (x, 510, x + sw, 640), fill=COLORS["accent"], outline=None, radius=22)
        d.text((x + 22, 532), label, font=load_font(28, bold=True), fill=COLORS["black"])
        draw_wrapped_text(d, (x + 22, 576), desc, load_font(21), COLORS["gray"], sw - 40, line_gap=8, paragraph_gap=8)

    rounded_rect(d, (1314, 770, PAGE_W - 116, 1450), fill=COLORS["panel"], outline=COLORS["line"], width=2, radius=28)
    d.text((1340, 820), "Profil publik dan pribadi", font=load_font(30, bold=True), fill=COLORS["black"])
    profile_items = [
        "Profil sendiri menampilkan resep, favorit, follower, dan following.",
        "Profil orang lain bisa dibuka untuk melihat resep dan menekan tombol follow.",
        "Pengguna juga dapat melihat resep komunitas dan akun yang disarankan.",
    ]
    yy = 900
    for item in profile_items:
        d.text((1340, yy), "•", font=load_font(26, bold=True), fill=COLORS["orange_dark"])
        draw_wrapped_text(d, (1380, yy), item, load_font(23), COLORS["gray"], 470, line_gap=10, paragraph_gap=10)
        yy += 164

    rounded_rect(d, (72, 1548, PAGE_W - 72, 1768), fill=COLORS["accent"], outline=COLORS["gold"], width=3, radius=32)
    d.text((118, 1610), "Ingat", font=load_font(30, bold=True), fill=COLORS["black"])
    d.text((248, 1610), "One user, one rating per resep. Like dan follow bisa ditoggle, jadi bisa dinyalakan atau dimatikan.", font=load_font(25), fill=COLORS["gray"])

    draw_footer(d)
    return page_img.convert("RGB")


def page_six() -> Image.Image:
    global page_img
    page_img = make_canvas().convert("RGBA")
    d = ImageDraw.Draw(page_img)
    draw_header(d, 6, "Fitur Admin dan Pengaduan", "Alat untuk menjaga isi platform tetap tertib")

    rounded_rect(d, (72, 320, PAGE_W - 72, 820), fill=COLORS["panel"], outline=COLORS["line"], width=3, radius=34)
    d.text((116, 378), "Dashboard admin", font=load_font(40, bold=True), fill=COLORS["black"])
    stats = [
        ("Total pengguna", "Lihat ukuran komunitas"),
        ("Total resep", "Pantau jumlah konten"),
        ("Komentar dan likes", "Baca aktivitas pengguna"),
        ("Pengaduan menunggu", "Fokus pada laporan yang belum selesai"),
    ]
    sw = (PAGE_W - 72 * 2 - 72) // 4
    for i, (label, desc) in enumerate(stats):
        x = 116 + i * (sw + 18)
        rounded_rect(d, (x, 472, x + sw, 714), fill=COLORS["accent"], outline=None, radius=24)
        d.text((x + 22, 516), label, font=load_font(26, bold=True), fill=COLORS["black"])
        draw_wrapped_text(d, (x + 22, 578), desc, load_font(20), COLORS["gray"], sw - 44, line_gap=8, paragraph_gap=8)

    rounded_rect(d, (72, 900, 1360, 1730), fill=COLORS["panel_soft"], outline=COLORS["line"], width=3, radius=34)
    d.text((116, 958), "Apa yang bisa dikelola?", font=load_font(36, bold=True), fill=COLORS["black"])
    admin_rows = [
        ["Kelola pengguna", "Cari, filter, aktif/nonaktif, dan hapus akun non-admin."],
        ["Kelola resep", "Lihat resep, cari konten tertentu, lalu hapus jika perlu."],
        ["Kelola laporan", "Filter pengaduan berdasarkan status, target, dan kategori."],
    ]
    draw_simple_table(
        d,
        (116, 1042, 1284, 1650),
        ["Menu", "Gunanya"],
        admin_rows,
        [310, 838],
    )

    rounded_rect(d, (1408, 900, PAGE_W - 72, 1730), fill=COLORS["panel"], outline=COLORS["line"], width=3, radius=34)
    d.text((1450, 958), "Pengaduan", font=load_font(36, bold=True), fill=COLORS["black"])
    report_items = [
        "Pengunjung atau pengguna bisa melaporkan resep dan profil.",
        "Status pengaduan diproses dengan alur menunggu -> selesai / ditolak.",
        "Admin dapat membuka target laporan langsung dari daftar laporan.",
        "Akun admin tidak boleh dihapus oleh admin lain.",
    ]
    yy = 1060
    for item in report_items:
        d.text((1450, yy), "•", font=load_font(26, bold=True), fill=COLORS["orange_dark"])
        draw_wrapped_text(d, (1490, yy), item, load_font(23), COLORS["gray"], 430, line_gap=10, paragraph_gap=10)
        yy += 180

    rounded_rect(d, (72, 1790, PAGE_W - 72, 1960), fill=COLORS["accent"], outline=COLORS["gold"], width=3, radius=32)
    d.text((118, 1846), "Status laporan", font=load_font(30, bold=True), fill=COLORS["black"])
    d.text((392, 1846), "menunggu", font=load_font(28, bold=True), fill=COLORS["orange_dark"])
    d.text((560, 1846), "->", font=load_font(28, bold=True), fill=COLORS["gray"])
    d.text((624, 1846), "selesai", font=load_font(28, bold=True), fill=COLORS["black"])
    d.text((760, 1846), "atau", font=load_font(28), fill=COLORS["gray"])
    d.text((848, 1846), "ditolak", font=load_font(28, bold=True), fill=COLORS["black"])

    draw_footer(d)
    return page_img.convert("RGB")


def page_seven() -> Image.Image:
    global page_img
    page_img = make_canvas().convert("RGBA")
    d = ImageDraw.Draw(page_img)
    draw_header(d, 7, "Cara Pakai Singkat", "Alur sederhana agar pengguna cepat paham")

    step_y = 420
    step_w = 332
    step_h = 280
    gap = 46
    steps = [
        ("1", "Buka Home", "Pengunjung langsung bisa melihat daftar resep dan menu pencarian."),
        ("2", "Cari atau baca", "Gunakan filter untuk menemukan resep yang paling sesuai."),
        ("3", "Login untuk aksi", "Masuk akun jika ingin like, komentar, rating, favorit, atau follow."),
        ("4", "Kelola sesuai role", "Pemilik resep mengatur resepnya sendiri, admin masuk ke panel admin."),
    ]
    for i, (num, title, desc) in enumerate(steps):
        x = 72 + i * (step_w + gap)
        rounded_rect(d, (x, step_y, x + step_w, step_y + step_h), fill=COLORS["panel"], outline=COLORS["line"], width=3, radius=32)
        badge(d, (x + 24, step_y + 24, x + 92, step_y + 84), num, COLORS["orange"], "#ffffff")
        d.text((x + 24, step_y + 112), title, font=load_font(30, bold=True), fill=COLORS["black"])
        draw_wrapped_text(d, (x + 24, step_y + 164), desc, load_font(22), COLORS["gray"], step_w - 48, line_gap=10, paragraph_gap=10)
        if i != len(steps) - 1:
            d.text((x + step_w + 16, step_y + 126), "→", font=load_font(42, bold=True), fill=COLORS["orange_dark"])

    rounded_rect(d, (72, 780, PAGE_W - 72, 1310), fill=COLORS["panel_soft"], outline=COLORS["line"], width=3, radius=34)
    d.text((118, 844), "Batas akses yang penting", font=load_font(38, bold=True), fill=COLORS["black"])
    limits = [
        "Like, komentar, rating, favorit, follow, dan buat resep memerlukan login.",
        "Resep hanya bisa diedit atau dihapus oleh pemilik resep atau admin.",
        "Admin hanya bisa membuka menu admin jika akunnya punya role admin.",
        "Satu user hanya bisa memberi satu rating untuk satu resep, dan like bisa ditoggle.",
    ]
    yy = 960
    for item in limits:
        d.text((118, yy), "✓", font=load_font(28, bold=True), fill=COLORS["orange_dark"])
        draw_wrapped_text(d, (160, yy), item, load_font(24), COLORS["gray"], 1160, line_gap=10, paragraph_gap=10)
        yy += 96

    rounded_rect(d, (72, 1380, PAGE_W - 72, 1700), fill=COLORS["accent"], outline=COLORS["gold"], width=3, radius=34)
    d.text((118, 1450), "Penutup", font=load_font(32, bold=True), fill=COLORS["black"])
    closing = (
        "ResepKu dirancang supaya orang bisa belajar resep dengan mudah, "
        "berinteraksi secara ringan, dan mengelola konten tanpa kerumitan."
    )
    draw_wrapped_text(d, (300, 1450), closing, load_font(27), COLORS["gray"], 1200, line_gap=12, paragraph_gap=14)

    draw_footer(d, "Selesai - dokumen fitur ResepKu")
    return page_img.convert("RGB")


def save_pdf(pages: Sequence[Image.Image], path: Path):
    pdf = canvas.Canvas(str(path), pagesize=(PAGE_W, PAGE_H))
    for page in pages:
        pdf.drawImage(ImageReader(page.convert("RGB")), 0, 0, width=PAGE_W, height=PAGE_H)
        pdf.showPage()
    pdf.save()


def main():
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    PREVIEW_DIR.mkdir(parents=True, exist_ok=True)

    pages = [page_one(), page_two(), page_three(), page_four(), page_five(), page_six(), page_seven()]

    for idx, page in enumerate(pages, start=1):
        page.save(PREVIEW_DIR / f"page-{idx}.png")

    save_pdf(pages, PDF_PATH)
    print(f"Saved PDF: {PDF_PATH}")
    print(f"Saved previews: {PREVIEW_DIR}")


if __name__ == "__main__":
    main()
