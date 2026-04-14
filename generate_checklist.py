import openpyxl
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from openpyxl.utils import get_column_letter

wb = openpyxl.Workbook()

# ─────────────────────────────────────────────
# HELPER STYLES
# ─────────────────────────────────────────────
def thin_border():
    s = Side(style='thin', color='BDBDBD')
    return Border(left=s, right=s, top=s, bottom=s)

def header_fill(hex_color):
    return PatternFill("solid", fgColor=hex_color)

def cell_font(bold=False, color="000000", size=10):
    return Font(bold=bold, color=color, size=size, name="Calibri")

def wrap_center():
    return Alignment(wrap_text=True, vertical='center', horizontal='center')

def wrap_left():
    return Alignment(wrap_text=True, vertical='center', horizontal='left')

def apply_header(ws, row, cols_labels, fill_hex, font_color="FFFFFF"):
    for col_idx, label in enumerate(cols_labels, start=1):
        c = ws.cell(row=row, column=col_idx, value=label)
        c.fill = header_fill(fill_hex)
        c.font = cell_font(bold=True, color=font_color, size=10)
        c.alignment = wrap_center()
        c.border = thin_border()

STATUS_COLORS = {
    "Implemented":          "C8E6C9",
    "Not Implemented":      "FFCDD2",
    "Partially Implemented":"FFF9C4",
}

def set_status_cell(c, status):
    c.value = status
    c.fill = header_fill(STATUS_COLORS.get(status, "FFFFFF"))
    c.font = cell_font(size=10)
    c.alignment = wrap_center()
    c.border = thin_border()


# ═══════════════════════════════════════════════════════════════════
# SHEET 1 – Secure Coding Checklist
# ═══════════════════════════════════════════════════════════════════
ws1 = wb.active
ws1.title = "Secure Coding Checklist"

ws1.merge_cells("A1:B1")
t = ws1["A1"]
t.value = "MASTER CHECKLIST SECURE CODING – Lab JobSeeker"
t.font = cell_font(bold=True, color="FFFFFF", size=13)
t.fill = header_fill("1A237E")
t.alignment = wrap_center()
ws1.row_dimensions[1].height = 30

ws1.column_dimensions["A"].width = 22
ws1.column_dimensions["B"].width = 90

checklist = [
    # ── Input Validation ──────────────────────────────────────────
    ("SCP-IV-001", "Lakukan input validation di server-side; jangan percayakan pada client-side validation"),
    ("SCP-IV-002", "Selalu validasi data dari sumber untrusted (Input Form, Parameter URL/query string, Data dari database atau API eksternal)"),
    ("SCP-IV-003", "Validasi tipe data, panjang karakter, format (email, phone, tanggal) sebelum diproses"),
    ("SCP-IV-004", "Sanitasi dan encode semua input sebelum dimasukkan ke query, template, atau respon HTTP"),
    # ── Authentication & Password Management ──────────────────────
    ("SCP-APM-001", "Terapkan kebijakan kompleksitas password (minimal 8 karakter, kombinasi huruf besar, huruf kecil, angka, dan simbol)"),
    ("SCP-APM-002", "Simpan password menggunakan hash satu arah yang kuat (bcrypt/argon2); jangan simpan plaintext"),
    ("SCP-APM-003", "Implementasi CAPTCHA atau mekanisme anti-bot pada halaman login dan registrasi"),
    ("SCP-APM-004", "Implementasi verifikasi email setelah registrasi sebelum akun dapat digunakan"),
    ("SCP-APM-005", "Token reset password harus memiliki waktu kadaluarsa (maksimal 1 jam) dan hanya bisa digunakan sekali"),
    ("SCP-APM-006", "Jangan kirim password (baik plaintext maupun hash) melalui email; hanya kirim link reset berumur pendek"),
    ("SCP-APM-007", "Implementasi account lockout atau rate limiting setelah beberapa kali gagal login (misal 5 percobaan)"),
    # ── Access Control ────────────────────────────────────────────
    ("SCP-AC-001", "Gunakan hanya objek yang berasal dari sistem terpercaya untuk keputusan otorisasi; jangan percayakan input klien untuk kontrol akses"),
    ("SCP-AC-002", "Kontrol akses harus fail securely: jangan tampilkan data secara default jika otorisasi gagal; kembalikan HTTP 403"),
    ("SCP-AC-003", "Implementasi Role-Based Access Control (RBAC) yang konsisten di semua endpoint dan halaman"),
    ("SCP-AC-004", "Validasi otorisasi di setiap endpoint/API; jangan hanya mengandalkan UI untuk menyembunyikan menu"),
    # ── Session Management ────────────────────────────────────────
    ("SCP-SM-001", "Set flag HttpOnly=true pada session cookie untuk mencegah akses JavaScript ke cookie"),
    ("SCP-SM-002", "Set flag Secure=true pada session cookie agar hanya dikirim melalui HTTPS"),
    ("SCP-SM-003", "Regenerate session ID setelah proses login berhasil untuk mencegah session fixation"),
    ("SCP-SM-004", "Implementasi session timeout/expiry (misal 30 menit idle) dan paksa logout otomatis"),
    ("SCP-SM-005", "Saat logout, hapus semua data session di server (session_destroy) dan hapus cookie di client"),
    # ── Error Handling ────────────────────────────────────────────
    ("SCP-EH-001", "Jangan tampilkan detail teknis error (stack trace, nama file, query SQL) kepada pengguna akhir"),
    ("SCP-EH-002", "Catat (log) error secara lengkap di server-side untuk keperluan debugging; bukan di client-side"),
    ("SCP-EH-003", "Gunakan pesan error yang generik dan ramah pengguna; hindari informasi yang dapat membantu penyerang"),
    # ── File Upload ───────────────────────────────────────────────
    ("SCP-FU-001", "Validasi ekstensi dan MIME type file yang diizinkan (whitelist); tolak tipe berbahaya seperti .php, .exe, .js"),
    ("SCP-FU-002", "Terapkan batas ukuran file upload dan validasi di server-side"),
    ("SCP-FU-003", "Simpan file upload di luar direktori web-accessible (di luar document root) untuk mencegah eksekusi langsung"),
    ("SCP-FU-004", "Gunakan HTTP method POST (bukan GET) untuk semua operasi file (upload, delete)"),
    ("SCP-FU-005", "Rename file yang diupload dengan nama acak (UUID) agar tidak bisa diprediksi atau ditimpa"),
    # ── Data Protection ───────────────────────────────────────────
    ("SCP-DP-001", "Jangan hardcode credentials (password, API key, secret) langsung di source code; gunakan environment variable atau secret manager"),
    ("SCP-DP-002", "Jangan expose secrets (JWT secret, API key) ke sisi client (JavaScript, HTML source)"),
    ("SCP-DP-003", "Jangan log data sensitif (password, token, nomor kartu) ke console browser atau log file yang tidak aman"),
    ("SCP-DP-004", "Jangan mengirimkan data sensitif ke layanan eksternal yang tidak dipercaya"),
    # ── SQL Injection Prevention ──────────────────────────────────
    ("SCP-SQL-001", "Selalu gunakan prepared statements atau parameterized queries untuk semua interaksi database"),
    ("SCP-SQL-002", "Jangan gunakan string concatenation/interpolasi untuk menyusun query SQL dengan input pengguna"),
    # ── CSRF Prevention ───────────────────────────────────────────
    ("SCP-CSRF-001", "Implementasi CSRF token pada semua form yang melakukan perubahan data (POST/PUT/DELETE)"),
    ("SCP-CSRF-002", "Validasi CSRF token di server sebelum memproses setiap request yang mengubah state"),
    # ── Cryptography Management ───────────────────────────────────
    ("SCP-CM-001", "Gunakan JWT dengan verifikasi signature yang benar; jangan gunakan implementasi custom tanpa validasi"),
    ("SCP-CM-002", "Gunakan JWT secret yang panjang dan acak (minimal 256-bit); jangan gunakan nilai lemah seperti 'weak_secret_key_123'"),
    ("SCP-CM-003", "Set expiration time (exp claim) pada setiap JWT token yang diterbitkan"),
    # ── Output Encoding ───────────────────────────────────────────
    ("SCP-OE-001", "Encode semua output yang ditampilkan ke HTML untuk mencegah Cross-Site Scripting (XSS)"),
    ("SCP-OE-002", "Gunakan htmlspecialchars() atau framework templating yang melakukan auto-escaping secara default"),
]

apply_header(ws1, 2, ["Code", "Implementation"], "283593", font_color="FFFFFF")
ws1.row_dimensions[2].height = 20

for idx, (code, impl) in enumerate(checklist, start=3):
    bg = "F5F5F5" if idx % 2 == 0 else "FFFFFF"

    c_code = ws1.cell(row=idx, column=1, value=code)
    c_code.font = cell_font(bold=True, size=10, color="1A237E")
    c_code.alignment = wrap_center()
    c_code.fill = header_fill(bg)
    c_code.border = thin_border()

    c_impl = ws1.cell(row=idx, column=2, value=impl)
    c_impl.font = cell_font(size=10)
    c_impl.alignment = wrap_left()
    c_impl.fill = header_fill(bg)
    c_impl.border = thin_border()

    ws1.row_dimensions[idx].height = 35


# ═══════════════════════════════════════════════════════════════════
# SHEET 2 – Description / Legend
# ═══════════════════════════════════════════════════════════════════
ws2 = wb.create_sheet("Description")

ws2.merge_cells("A1:B1")
t2 = ws2["A1"]
t2.value = "KODE KATEGORI – KETERANGAN"
t2.font = cell_font(bold=True, color="FFFFFF", size=13)
t2.fill = header_fill("1A237E")
t2.alignment = wrap_center()
ws2.row_dimensions[1].height = 30

ws2.column_dimensions["A"].width = 12
ws2.column_dimensions["B"].width = 60

apply_header(ws2, 2, ["Code", "Description"], "283593", font_color="FFFFFF")
ws2.row_dimensions[2].height = 20

descriptions = [
    ("IV",   "Input Validation"),
    ("APM",  "Authentication & Password Management"),
    ("AC",   "Access Control"),
    ("SM",   "Session Management"),
    ("EH",   "Error Handling"),
    ("FU",   "File Upload"),
    ("DP",   "Data Protection"),
    ("SQL",  "SQL Injection Prevention"),
    ("CSRF", "Cross-Site Request Forgery Prevention"),
    ("CM",   "Cryptography Management"),
    ("OE",   "Output Encoding"),
]

for i, (code, desc) in enumerate(descriptions, start=3):
    bg = "F5F5F5" if i % 2 == 0 else "FFFFFF"
    c = ws2.cell(row=i, column=1, value=code)
    c.font = cell_font(bold=True, size=10, color="1A237E")
    c.alignment = wrap_center()
    c.fill = header_fill(bg)
    c.border = thin_border()

    d = ws2.cell(row=i, column=2, value=desc)
    d.font = cell_font(size=10)
    d.alignment = wrap_left()
    d.fill = header_fill(bg)
    d.border = thin_border()
    ws2.row_dimensions[i].height = 25


# ═══════════════════════════════════════════════════════════════════
# SHEET 3 – Secure Coding on Apps
# ═══════════════════════════════════════════════════════════════════
ws3 = wb.create_sheet("Secure Coding on Apps")

ws3.merge_cells("A1:D1")
t3 = ws3["A1"]
t3.value = "IMPLEMENTASI SECURE CODING – Lab JobSeeker Application"
t3.font = cell_font(bold=True, color="FFFFFF", size=13)
t3.fill = header_fill("1A237E")
t3.alignment = wrap_center()
ws3.row_dimensions[1].height = 30

ws3.column_dimensions["A"].width = 26
ws3.column_dimensions["B"].width = 36
ws3.column_dimensions["C"].width = 18
ws3.column_dimensions["D"].width = 24

apply_header(ws3, 2, ["Feature", "Scope", "Implementation", "Status"], "283593", font_color="FFFFFF")
ws3.row_dimensions[2].height = 22

apps_data = [
    # ── GENERAL ──────────────────────────────────────────────────────
    ("General", "Semua halaman",              "SCP-EH-001",   "Implemented"),
    ("General", "Semua halaman",              "SCP-EH-002",   "Implemented"),
    ("General", "Semua halaman",              "SCP-EH-003",   "Implemented"),
    ("General", "Semua halaman",              "SCP-SQL-001",  "Implemented"),
    ("General", "Semua halaman",              "SCP-SQL-002",  "Implemented"),
    ("General", "Semua halaman",              "SCP-CSRF-001", "Implemented"),
    ("General", "Semua halaman",              "SCP-CSRF-002", "Implemented"),
    ("General", "Semua halaman",              "SCP-OE-001",   "Implemented"),
    ("General", "Semua halaman",              "SCP-OE-002",   "Implemented"),
    ("General", "Semua halaman",              "SCP-DP-001",   "Implemented"),
    ("General", "includes/session.php",       "SCP-SM-001",   "Implemented"),
    ("General", "includes/session.php",       "SCP-SM-002",   "Partially Implemented"),
    ("General", "includes/session.php",       "SCP-SM-004",   "Implemented"),
    # ── LOGIN ─────────────────────────────────────────────────────────
    ("Login",   "pages/auth/login.php",       "SCP-IV-001",   "Implemented"),
    ("Login",   "pages/auth/login.php",       "SCP-IV-002",   "Implemented"),
    ("Login",   "pages/auth/login.php",       "SCP-APM-001",  "Implemented"),
    ("Login",   "pages/auth/login.php",       "SCP-APM-002",  "Implemented"),
    ("Login",   "pages/auth/login.php",       "SCP-APM-003",  "Not Implemented"),
    ("Login",   "pages/auth/login.php",       "SCP-APM-007",  "Not Implemented"),
    ("Login",   "pages/auth/login.php",       "SCP-SM-003",   "Implemented"),
    ("Login",   "pages/auth/login.php",       "SCP-DP-003",   "Implemented"),
    ("Login",   "pages/auth/login.php",       "SCP-DP-004",   "Implemented"),
    # ── REGISTER ──────────────────────────────────────────────────────
    ("Register", "pages/auth/register.php",   "SCP-IV-001",   "Implemented"),
    ("Register", "pages/auth/register.php",   "SCP-IV-002",   "Implemented"),
    ("Register", "pages/auth/register.php",   "SCP-APM-001",  "Implemented"),
    ("Register", "pages/auth/register.php",   "SCP-APM-002",  "Implemented"),
    ("Register", "pages/auth/register.php",   "SCP-APM-003",  "Not Implemented"),
    ("Register", "pages/auth/register.php",   "SCP-APM-004",  "Implemented"),
    # ── FORGOT PASSWORD ───────────────────────────────────────────────
    ("Forgot Password", "pages/auth/forgot-password.php", "SCP-IV-001",  "Implemented"),
    ("Forgot Password", "pages/auth/forgot-password.php", "SCP-APM-005", "Implemented"),
    ("Forgot Password", "pages/auth/forgot-password.php", "SCP-APM-006", "Implemented"),
    # ── RESET PASSWORD ────────────────────────────────────────────────
    ("Reset Password", "pages/auth/reset-password.php",   "SCP-IV-001",  "Implemented"),
    ("Reset Password", "pages/auth/reset-password.php",   "SCP-APM-002", "Implemented"),
    # ── LOGOUT ────────────────────────────────────────────────────────
    ("Logout", "pages/auth/logout.php",       "SCP-SM-005",   "Implemented"),
    # ── PROFILE ───────────────────────────────────────────────────────
    ("Profile", "pages/member/profile.php",   "SCP-IV-001",   "Implemented"),
    ("Profile", "pages/member/profile.php",   "SCP-AC-001",   "Implemented"),
    ("Profile", "pages/member/profile.php",   "SCP-AC-002",   "Implemented"),
    ("Profile", "pages/member/profile.php",   "SCP-AC-004",   "Implemented"),
    ("Profile", "pages/member/profile.php",   "SCP-FU-001",   "Implemented"),
    ("Profile", "pages/member/profile.php",   "SCP-FU-002",   "Implemented"),
    ("Profile", "pages/member/profile.php",   "SCP-FU-003",   "Implemented"),
    ("Profile", "pages/member/profile.php",   "SCP-FU-004",   "Implemented"),
    ("Profile", "pages/member/profile.php",   "SCP-FU-005",   "Implemented"),
    # ── CV UPLOAD ─────────────────────────────────────────────────────
    ("CV Upload", "pages/member/cv.php",      "SCP-FU-001",   "Implemented"),
    ("CV Upload", "pages/member/cv.php",      "SCP-FU-002",   "Implemented"),
    ("CV Upload", "pages/member/cv.php",      "SCP-FU-003",   "Implemented"),
    ("CV Upload", "pages/member/cv.php",      "SCP-FU-004",   "Implemented"),
    ("CV Upload", "pages/member/cv.php",      "SCP-FU-005",   "Implemented"),
    ("CV Upload", "pages/member/cv.php",      "SCP-AC-004",   "Implemented"),
    # ── SKILLS ────────────────────────────────────────────────────────
    ("Skills Management", "pages/member/skills.php",    "SCP-IV-001",  "Implemented"),
    ("Skills Management", "pages/member/skills.php",    "SCP-AC-003",  "Implemented"),
    ("Skills Management", "pages/member/skills.php",    "SCP-AC-004",  "Implemented"),
    # ── EDUCATION ─────────────────────────────────────────────────────
    ("Education Management", "pages/member/education.php", "SCP-IV-001", "Implemented"),
    ("Education Management", "pages/member/education.php", "SCP-AC-003", "Implemented"),
    ("Education Management", "pages/member/education.php", "SCP-AC-004", "Implemented"),
    # ── JOB BROWSE ────────────────────────────────────────────────────
    ("Job Browse", "pages/member/jobs.php",   "SCP-IV-001",   "Implemented"),
    ("Job Browse", "pages/member/jobs.php",   "SCP-IV-002",   "Implemented"),
    # ── JOB APPLY ─────────────────────────────────────────────────────
    ("Job Apply", "pages/member/apply.php",   "SCP-IV-001",   "Implemented"),
    ("Job Apply", "pages/member/apply.php",   "SCP-AC-003",   "Implemented"),
    ("Job Apply", "pages/member/apply.php",   "SCP-AC-004",   "Implemented"),
    # ── COMPANY DASHBOARD ─────────────────────────────────────────────
    ("Company Dashboard", "pages/company/dashboard.php", "SCP-AC-002", "Implemented"),
    ("Company Dashboard", "pages/company/dashboard.php", "SCP-AC-003", "Implemented"),
    ("Company Dashboard", "pages/company/dashboard.php", "SCP-AC-004", "Implemented"),
    # ── JOB MANAGEMENT ────────────────────────────────────────────────
    ("Job Management", "pages/company/jobs.php",         "SCP-IV-001", "Implemented"),
    ("Job Management", "pages/company/jobs.php",         "SCP-AC-003", "Implemented"),
    ("Job Management", "pages/company/jobs.php",         "SCP-AC-004", "Implemented"),
    ("Job Management", "pages/company/jobs.php",         "SCP-FU-001", "Implemented"),
    # ── APPLICANT MANAGEMENT ──────────────────────────────────────────
    ("Applicant Management", "pages/company/applicants.php", "SCP-AC-001", "Implemented"),
    ("Applicant Management", "pages/company/applicants.php", "SCP-AC-002", "Implemented"),
    ("Applicant Management", "pages/company/applicants.php", "SCP-AC-004", "Implemented"),
    ("Applicant Management", "pages/company/applicants.php", "SCP-IV-001", "Implemented"),
    # ── JWT / CRYPTOGRAPHY ────────────────────────────────────────────
    ("JWT / Token", "includes/jwt.php",       "SCP-CM-001",   "Implemented"),
    ("JWT / Token", "includes/jwt.php",       "SCP-CM-002",   "Implemented"),
    ("JWT / Token", "includes/jwt.php",       "SCP-CM-003",   "Implemented"),
    ("JWT / Token", "templates/header.php",   "SCP-DP-002",   "Implemented"),
    # ── DATABASE CONFIG ───────────────────────────────────────────────
    ("Database Config", "config/env.php",     "SCP-DP-001",   "Implemented"),
    # ── AUTH MODULE ───────────────────────────────────────────────────
    ("Auth Module", "includes/auth.php",      "SCP-AC-002",   "Implemented"),
    ("Auth Module", "includes/auth.php",      "SCP-AC-003",   "Implemented"),
]

prev_feature = None
for idx, (feature, scope, impl, status) in enumerate(apps_data, start=3):
    bg = "F5F5F5" if idx % 2 == 0 else "FFFFFF"

    c_feat = ws3.cell(row=idx, column=1, value=feature)
    c_feat.font = cell_font(bold=(feature != prev_feature), size=10)
    c_feat.alignment = wrap_center()
    c_feat.fill = header_fill(bg)
    c_feat.border = thin_border()

    c_scope = ws3.cell(row=idx, column=2, value=scope)
    c_scope.font = cell_font(size=10)
    c_scope.alignment = wrap_left()
    c_scope.fill = header_fill(bg)
    c_scope.border = thin_border()

    c_impl = ws3.cell(row=idx, column=3, value=impl)
    c_impl.font = cell_font(bold=True, size=10, color="1A237E")
    c_impl.alignment = wrap_center()
    c_impl.fill = header_fill(bg)
    c_impl.border = thin_border()

    set_status_cell(ws3.cell(row=idx, column=4), status)

    ws3.row_dimensions[idx].height = 28
    prev_feature = feature

# Legend
legend_row = len(apps_data) + 4
leg = ws3.cell(row=legend_row, column=1, value="KETERANGAN STATUS:")
leg.font = cell_font(bold=True, size=10)
leg.alignment = wrap_left()

for i, (label, color) in enumerate([
    ("Implemented", "C8E6C9"),
    ("Partially Implemented", "FFF9C4"),
    ("Not Implemented", "FFCDD2"),
], start=2):
    c = ws3.cell(row=legend_row, column=i, value=label)
    c.fill = header_fill(color)
    c.font = cell_font(bold=True, size=10)
    c.alignment = wrap_center()
    c.border = thin_border()
ws3.row_dimensions[legend_row].height = 22

# ═══════════════════════════════════════════════════════════════════
# SHEET 4 – Threat Modeling
# ═══════════════════════════════════════════════════════════════════
ws4 = wb.create_sheet("Threat Modeling")

ws4.merge_cells("A1:G1")
t4 = ws4["A1"]
t4.value = "THREAT MODELING – Lab JobSeeker Application"
t4.font = cell_font(bold=True, color="FFFFFF", size=13)
t4.fill = header_fill("1A237E")
t4.alignment = wrap_center()
ws4.row_dimensions[1].height = 30

ws4.column_dimensions["A"].width = 20   # Feature
ws4.column_dimensions["B"].width = 30   # Scope
ws4.column_dimensions["C"].width = 26   # Threat
ws4.column_dimensions["D"].width = 55   # Deskripsi
ws4.column_dimensions["E"].width = 12   # Priority
ws4.column_dimensions["F"].width = 22   # STRIDE
ws4.column_dimensions["G"].width = 40   # Kontrol

apply_header(ws4, 2,
    ["Feature (New/Update)", "Scope", "Threat", "Deskripsi", "Priority", "STRIDE", "Kontrol"],
    "283593", font_color="FFFFFF")
ws4.row_dimensions[2].height = 22

PRIORITY_COLORS = {
    "Critical": "B71C1C",
    "High":     "E53935",
    "Medium":   "FB8C00",
    "Low":      "43A047",
}

STRIDE_COLORS = {
    "Spoofing":               "E3F2FD",
    "Tampering":              "FFF3E0",
    "Repudiation":            "F3E5F5",
    "Information Disclosure": "E8F5E9",
    "Denial of Service":      "FCE4EC",
    "Elevation of Privilege": "FFF9C4",
}

# Feature | Scope | Threat | Deskripsi | Priority | STRIDE | Kontrol
threat_data = [
    # ── LOGIN ─────────────────────────────────────────────────────────
    ("Update", "Login – pages/auth/login.php",
     "SQL Injection",
     "Penyerang menyisipkan query SQL berbahaya pada field email/password untuk mem-bypass autentikasi atau mengekstrak data",
     "Critical", "Spoofing",
     "Gunakan prepared statements (PDO) untuk semua query; terapkan SCP-SQL-001, SCP-SQL-002"),

    ("Update", "Login – pages/auth/login.php",
     "Brute Force Attack",
     "Penyerang mencoba ribuan kombinasi username/password secara otomatis untuk menebak kredensial pengguna",
     "High", "Spoofing",
     "SCP-APM-003; SCP-APM-007 (rate limiting & account lockout setelah 5 percobaan gagal)"),

    ("Update", "Login – pages/auth/login.php",
     "Credential Exfiltration",
     "Kredensial login (email & password) dikirim ke layanan eksternal tidak terpercaya (evil-logger.com) melalui JavaScript",
     "Critical", "Information Disclosure",
     "SCP-DP-003; SCP-DP-004 – Hapus seluruh logging ke layanan eksternal; audit semua script JS"),

    ("Update", "Login – pages/auth/login.php",
     "Session Fixation",
     "Penyerang menetapkan session ID sebelum login; setelah korban login, penyerang membajak sesi tersebut",
     "High", "Spoofing",
     "SCP-SM-003 – Regenerate session ID segera setelah autentikasi berhasil"),

    ("Update", "Login – pages/auth/login.php",
     "Plaintext Password Storage",
     "Password disimpan dalam bentuk plaintext di database; jika database bocor, semua password langsung terbaca",
     "Critical", "Information Disclosure",
     "SCP-APM-002 – Gunakan password_hash() dengan algoritma bcrypt/argon2"),

    ("Update", "Login – pages/auth/login.php",
     "Session Data Exposed in Console",
     "Data session pengguna (ID, role, token) dicatat ke console browser dan dapat dibaca melalui DevTools",
     "Medium", "Information Disclosure",
     "SCP-DP-003 – Hapus semua console.log yang memuat data sensitif dari kode JavaScript"),

    # ── REGISTER ──────────────────────────────────────────────────────
    ("Update", "Register – pages/auth/register.php",
     "Mass Registration (Bot)",
     "Bot mendaftarkan ribuan akun secara otomatis yang dapat membebani server dan mengotori database",
     "High", "Denial of Service",
     "SCP-APM-003 – Implementasi CAPTCHA (Google reCAPTCHA / hCaptcha) pada form registrasi"),

    ("Update", "Register – pages/auth/register.php",
     "Weak Password Accepted",
     "Sistem menerima password lemah seperti '123456' atau 'a' tanpa validasi kompleksitas sama sekali",
     "High", "Spoofing",
     "SCP-APM-001 – Terapkan password policy: minimal 8 karakter, huruf besar/kecil, angka, simbol"),

    ("Update", "Register – pages/auth/register.php",
     "SQL Injection pada Registrasi",
     "Input pada form registrasi (nama, email) tidak diparameterkan sehingga rentan terhadap SQL injection",
     "Critical", "Tampering",
     "SCP-SQL-001, SCP-SQL-002 – Gunakan prepared statements PDO untuk semua INSERT query"),

    ("Update", "Register – pages/auth/register.php",
     "User Enumeration via Error Message",
     "Pesan error 'Email sudah terdaftar' memungkinkan penyerang mengetahui email mana yang sudah ada di sistem",
     "Medium", "Information Disclosure",
     "SCP-EH-003 – Gunakan pesan generik; pertimbangkan silent redirect atau email konfirmasi"),

    # ── FORGOT PASSWORD ───────────────────────────────────────────────
    ("Update", "Forgot Password – pages/auth/forgot-password.php",
     "Password Reset Token Tidak Expired",
     "Token reset password tidak memiliki masa berlaku sehingga link reset tetap valid selamanya dan bisa disalahgunakan",
     "High", "Elevation of Privilege",
     "SCP-APM-005 – Set expiry token maksimal 1 jam; invalidasi token setelah digunakan sekali"),

    ("Update", "Forgot Password – pages/auth/forgot-password.php",
     "Account Enumeration via Reset",
     "Respon berbeda untuk email terdaftar vs tidak terdaftar memungkinkan penyerang memetakan akun yang ada",
     "Medium", "Information Disclosure",
     "SCP-EH-003 – Tampilkan pesan yang sama terlepas email terdaftar atau tidak"),

    ("Update", "Forgot Password – pages/auth/forgot-password.php",
     "Email Header Injection",
     "Input email tidak disanitasi sebelum digunakan dalam header email, memungkinkan penyerang menyisipkan header tambahan",
     "High", "Tampering",
     "SCP-IV-004 – Validasi dan sanitasi input email; gunakan library email yang aman"),

    # ── RESET PASSWORD ────────────────────────────────────────────────
    ("Update", "Reset Password – pages/auth/reset-password.php",
     "Password Baru Tidak Di-hash",
     "Password baru yang diinput saat reset disimpan ke database dalam bentuk plaintext",
     "Critical", "Information Disclosure",
     "SCP-APM-002 – Selalu hash password baru dengan password_hash() sebelum disimpan"),

    ("Update", "Reset Password – pages/auth/reset-password.php",
     "Token Reuse Attack",
     "Token reset dapat digunakan lebih dari satu kali karena tidak diinvalidasi setelah berhasil digunakan",
     "High", "Elevation of Privilege",
     "SCP-APM-005 – Hapus atau nonaktifkan token segera setelah digunakan"),

    # ── LOGOUT ────────────────────────────────────────────────────────
    ("Update", "Logout – pages/auth/logout.php",
     "Incomplete Session Destruction",
     "Saat logout, role dan JWT token tidak dihapus dari session sehingga sesi lama masih dapat dieksploitasi",
     "High", "Repudiation",
     "SCP-SM-005 – Gunakan session_destroy() dan hapus semua variabel session termasuk role dan token"),

    # ── SESSION ───────────────────────────────────────────────────────
    ("Update", "Session – includes/session.php",
     "Session Hijacking via XSS",
     "Cookie session dapat diakses oleh JavaScript karena flag HttpOnly tidak di-set, memungkinkan pencurian session via XSS",
     "Critical", "Spoofing",
     "SCP-SM-001 – Set session.cookie_httponly = 1 di konfigurasi PHP session"),

    ("Update", "Session – includes/session.php",
     "Session Dikirim via HTTP",
     "Cookie session dikirimkan melalui koneksi HTTP tidak terenkripsi karena flag Secure tidak di-set",
     "High", "Information Disclosure",
     "SCP-SM-002 – Set session.cookie_secure = 1; paksa HTTPS di seluruh aplikasi"),

    ("Update", "Session – includes/session.php",
     "Session Tidak Timeout",
     "Tidak ada mekanisme timeout; sesi aktif selamanya dan berisiko disalahgunakan jika perangkat ditinggalkan",
     "Medium", "Elevation of Privilege",
     "SCP-SM-004 – Set session.gc_maxlifetime dan validasi waktu aktif terakhir"),

    # ── PROFILE ───────────────────────────────────────────────────────
    ("Update", "Profile – pages/member/profile.php",
     "Malicious File Upload (Web Shell)",
     "Fitur upload foto profil tidak memvalidasi tipe file; penyerang bisa mengupload file .php dan mengeksekusinya sebagai web shell",
     "Critical", "Elevation of Privilege",
     "SCP-FU-001, SCP-FU-003, SCP-FU-005 – Whitelist MIME type; simpan di luar web root; rename dengan UUID"),

    ("Update", "Profile – pages/member/profile.php",
     "CSRF pada Update Profile",
     "Form update profil tidak memiliki CSRF token; penyerang dapat memaksa pengguna yang terautentikasi mengubah data profilnya",
     "High", "Tampering",
     "SCP-CSRF-001, SCP-CSRF-002 – Generate dan validasi CSRF token di setiap form POST"),

    ("Update", "Profile – pages/member/profile.php",
     "IDOR – Akses Profil Pengguna Lain",
     "Parameter user_id di URL tidak divalidasi terhadap session; penyerang bisa mengakses atau memodifikasi profil pengguna lain",
     "High", "Elevation of Privilege",
     "SCP-AC-001, SCP-AC-004 – Ambil user_id dari session server-side, bukan dari parameter URL"),

    # ── CV UPLOAD ─────────────────────────────────────────────────────
    ("Update", "CV Upload – pages/member/cv.php",
     "Malicious File Upload",
     "Upload CV tidak memvalidasi tipe file; file berbahaya (.php, .exe) dapat diupload dan dieksekusi dari web-accessible directory",
     "Critical", "Elevation of Privilege",
     "SCP-FU-001, SCP-FU-002, SCP-FU-003 – Whitelist hanya PDF/DOC; simpan di luar web root"),

    ("Update", "CV Upload – pages/member/cv.php",
     "File Deletion via GET Request",
     "Penghapusan file CV dilakukan melalui parameter GET (?delete=filename) yang bisa dipicu oleh link di email atau iframe",
     "High", "Tampering",
     "SCP-FU-004 – Pindahkan operasi delete ke POST request disertai CSRF token"),

    ("Update", "CV Upload – pages/member/cv.php",
     "Path Traversal",
     "Nama file yang diinput tidak divalidasi; penyerang bisa menggunakan '../' untuk mengakses atau menghapus file di luar direktori upload",
     "Critical", "Information Disclosure",
     "SCP-FU-005 – Gunakan basename() dan rename file dengan UUID; validasi path sebelum operasi file"),

    # ── SKILLS & EDUCATION ────────────────────────────────────────────
    ("Update", "Skills – pages/member/skills.php",
     "SQL Injection via Input Skills",
     "Data skills (nama skill, level) langsung digabungkan ke query SQL tanpa parameterisasi",
     "Critical", "Tampering",
     "SCP-SQL-001, SCP-SQL-002 – Gunakan prepared statements untuk semua operasi CRUD"),

    ("Update", "Skills – pages/member/skills.php",
     "IDOR pada Delete/Edit Skill",
     "Parameter skill_id tidak diverifikasi kepemilikannya; pengguna A bisa menghapus skill milik pengguna B",
     "High", "Elevation of Privilege",
     "SCP-AC-004 – Verifikasi kepemilikan record berdasarkan user_id dari session sebelum operasi"),

    ("Update", "Education – pages/member/education.php",
     "IDOR pada Delete/Edit Riwayat Pendidikan",
     "Parameter education_id tidak divalidasi kepemilikannya; pengguna lain bisa memodifikasi atau menghapus data pendidikan",
     "High", "Elevation of Privilege",
     "SCP-AC-004 – Sertakan user_id dari session dalam WHERE clause saat query update/delete"),

    # ── JOB BROWSE & APPLY ────────────────────────────────────────────
    ("Update", "Job Browse – pages/member/jobs.php",
     "SQL Injection via Search Parameter",
     "Parameter pencarian pekerjaan (judul, lokasi) dimasukkan langsung ke query tanpa sanitasi, rentan SQL injection",
     "Critical", "Tampering",
     "SCP-SQL-001 – Gunakan prepared statements dengan binding parameter untuk semua filter pencarian"),

    ("Update", "Job Apply – pages/member/apply.php",
     "CSRF pada Submit Lamaran",
     "Form pengajuan lamaran kerja tidak memiliki CSRF token; penyerang bisa memaksa pengguna mengirim lamaran tanpa sepengetahuannya",
     "High", "Tampering",
     "SCP-CSRF-001, SCP-CSRF-002 – Tambahkan CSRF token pada form apply"),

    ("Update", "Job Apply – pages/member/apply.php",
     "IDOR – Apply Atas Nama Pengguna Lain",
     "Parameter member_id dalam request tidak divalidasi; penyerang bisa mengirim lamaran menggunakan identitas pengguna lain",
     "High", "Spoofing",
     "SCP-AC-001, SCP-AC-004 – Ambil member_id eksklusif dari session; tolak jika berbeda"),

    # ── COMPANY – JOB MANAGEMENT ──────────────────────────────────────
    ("Update", "Job Management – pages/company/jobs.php",
     "XSS via Job Description",
     "Konten deskripsi pekerjaan ditampilkan tanpa encoding; penyerang bisa menyisipkan script berbahaya yang dieksekusi di browser pelamar",
     "High", "Tampering",
     "SCP-OE-001, SCP-OE-002 – Gunakan htmlspecialchars() pada semua output; pertimbangkan HTML purifier untuk rich text"),

    ("Update", "Job Management – pages/company/jobs.php",
     "CSRF pada Create/Delete Job",
     "Operasi pembuatan dan penghapusan lowongan tidak dilindungi CSRF token",
     "High", "Tampering",
     "SCP-CSRF-001, SCP-CSRF-002 – Tambahkan dan validasi CSRF token pada semua form manajemen job"),

    ("Update", "Job Management – pages/company/jobs.php",
     "IDOR – Manipulasi Job Perusahaan Lain",
     "Parameter job_id tidak diverifikasi kepemilikannya; perusahaan A bisa mengedit atau menghapus lowongan milik perusahaan B",
     "High", "Elevation of Privilege",
     "SCP-AC-004 – Verifikasi company_id dari session sesuai dengan pemilik job_id sebelum operasi"),

    # ── APPLICANT MANAGEMENT ──────────────────────────────────────────
    ("Update", "Applicant Management – pages/company/applicants.php",
     "IDOR – Akses Data Pelamar Perusahaan Lain",
     "Perusahaan dapat mengakses detail lamaran dan profil pelamar dari perusahaan lain hanya dengan mengubah parameter di URL",
     "High", "Information Disclosure",
     "SCP-AC-001, SCP-AC-002 – Validasi bahwa application_id yang diakses memang milik company yang sedang login"),

    ("Update", "Applicant Management – pages/company/applicants.php",
     "Unauthorized Status Update",
     "Status lamaran (accepted/rejected) dapat diubah oleh perusahaan yang bukan pemilik lowongan tersebut",
     "High", "Tampering",
     "SCP-AC-004 – Verifikasi kepemilikan job dan application sebelum mengizinkan perubahan status"),

    # ── JWT / TOKEN ───────────────────────────────────────────────────
    ("Update", "JWT – includes/jwt.php & templates/header.php",
     "JWT Secret Exposed di Client-Side",
     "JWT secret key (weak_secret_key_123) diekspos ke seluruh client melalui variabel JavaScript window.JWT_SECRET di header.php",
     "Critical", "Information Disclosure",
     "SCP-DP-002 – Hapus JWT_SECRET dari output HTML/JS; secret hanya boleh ada di server-side"),

    ("Update", "JWT – includes/jwt.php",
     "JWT Algorithm None Attack",
     "Implementasi JWT custom tidak memverifikasi signature; penyerang bisa memanipulasi payload token dan mengubah role tanpa dideteksi",
     "Critical", "Elevation of Privilege",
     "SCP-CM-001 – Gunakan library JWT terpercaya (firebase/php-jwt) yang memvalidasi signature dengan benar"),

    ("Update", "JWT – includes/jwt.php",
     "JWT Tanpa Expiry",
     "Token JWT tidak memiliki klaim exp (expiration); token yang bocor dapat digunakan selamanya tanpa batas waktu",
     "High", "Spoofing",
     "SCP-CM-003 – Set klaim exp pada setiap token yang diterbitkan (misal: 1 jam)"),

    ("Update", "JWT – includes/jwt.php",
     "JWT Secret Lemah",
     "Secret key JWT menggunakan nilai lemah dan mudah ditebak ('weak_secret_key_123') yang rentan terhadap brute force offline",
     "Critical", "Spoofing",
     "SCP-CM-002 – Gunakan secret acak minimal 256-bit; simpan di environment variable"),

    # ── DATABASE CONFIG ───────────────────────────────────────────────
    ("Update", "DB Config – config/env.php",
     "Hardcoded Database Credentials",
     "Username, password database, dan kredensial SMTP di-hardcode langsung di source code dan dapat bocor melalui source control",
     "Critical", "Information Disclosure",
     "SCP-DP-001 – Pindahkan semua kredensial ke environment variable (.env) dan tambahkan .env ke .gitignore"),

    # ── ERROR HANDLING ────────────────────────────────────────────────
    ("Update", "General – Semua halaman",
     "Verbose Error Message (Information Leakage)",
     "Error database (PDO exception, SQL query, path file) ditampilkan langsung ke pengguna, memberikan informasi berharga bagi penyerang",
     "High", "Information Disclosure",
     "SCP-EH-001, SCP-EH-003 – Set display_errors=Off di production; tampilkan halaman error generik"),

    # ── XSS GENERAL ───────────────────────────────────────────────────
    ("Update", "General – Semua output halaman",
     "Cross-Site Scripting (XSS) Reflected",
     "Parameter URL (search query, filter) ditampilkan kembali ke halaman tanpa encoding, memungkinkan injeksi script berbahaya",
     "High", "Tampering",
     "SCP-OE-001, SCP-OE-002 – Terapkan htmlspecialchars(ENT_QUOTES) pada semua output dari input pengguna"),

    # ── ACCESS CONTROL ────────────────────────────────────────────────
    ("Update", "Auth Module – includes/auth.php",
     "Broken Access Control – checkAccess() Tidak Memblokir",
     "Fungsi checkAccess() mencatat log ketika akses tidak sah terjadi tetapi tidak menghentikan eksekusi; halaman tetap dapat diakses",
     "Critical", "Elevation of Privilege",
     "SCP-AC-002, SCP-AC-003 – Tambahkan header(403) dan exit() setelah deteksi akses tidak sah"),

    ("Update", "General – Semua halaman member/company",
     "Privilege Escalation via Role Manipulation",
     "Role pengguna disimpan di session dan dapat dimanipulasi; tidak ada validasi role dari database pada setiap request",
     "Critical", "Elevation of Privilege",
     "SCP-AC-001 – Validasi role pengguna dari database pada setiap request sensitif, bukan hanya dari session"),
]

for idx, (feature, scope, threat, deskripsi, priority, stride, kontrol) in enumerate(threat_data, start=3):
    bg = "F5F5F5" if idx % 2 == 0 else "FFFFFF"

    c1 = ws4.cell(row=idx, column=1, value=feature)
    c1.font = cell_font(size=10)
    c1.alignment = wrap_center()
    c1.fill = header_fill(bg)
    c1.border = thin_border()

    c2 = ws4.cell(row=idx, column=2, value=scope)
    c2.font = cell_font(size=10)
    c2.alignment = wrap_left()
    c2.fill = header_fill(bg)
    c2.border = thin_border()

    c3 = ws4.cell(row=idx, column=3, value=threat)
    c3.font = cell_font(bold=True, size=10)
    c3.alignment = wrap_left()
    c3.fill = header_fill(bg)
    c3.border = thin_border()

    c4 = ws4.cell(row=idx, column=4, value=deskripsi)
    c4.font = cell_font(size=10)
    c4.alignment = wrap_left()
    c4.fill = header_fill(bg)
    c4.border = thin_border()

    # Priority with color
    c5 = ws4.cell(row=idx, column=5, value=priority)
    p_color = PRIORITY_COLORS.get(priority, "FFFFFF")
    c5.fill = header_fill(p_color)
    c5.font = cell_font(bold=True, size=10, color="FFFFFF")
    c5.alignment = wrap_center()
    c5.border = thin_border()

    # STRIDE with color
    c6 = ws4.cell(row=idx, column=6, value=stride)
    s_color = STRIDE_COLORS.get(stride, "FFFFFF")
    c6.fill = header_fill(s_color)
    c6.font = cell_font(bold=True, size=10, color="000000")
    c6.alignment = wrap_center()
    c6.border = thin_border()

    c7 = ws4.cell(row=idx, column=7, value=kontrol)
    c7.font = cell_font(size=10)
    c7.alignment = wrap_left()
    c7.fill = header_fill(bg)
    c7.border = thin_border()

    ws4.row_dimensions[idx].height = 45

# STRIDE Legend
legend_row4 = len(threat_data) + 4
leg4 = ws4.cell(row=legend_row4, column=1, value="KETERANGAN STRIDE:")
leg4.font = cell_font(bold=True, size=10)
leg4.alignment = wrap_left()

stride_legends = list(STRIDE_COLORS.items())
for i, (label, color) in enumerate(stride_legends, start=2):
    c = ws4.cell(row=legend_row4, column=i, value=label)
    c.fill = header_fill(color)
    c.font = cell_font(bold=True, size=10)
    c.alignment = wrap_center()
    c.border = thin_border()
ws4.row_dimensions[legend_row4].height = 22

# Priority Legend
legend_row4b = legend_row4 + 1
leg4b = ws4.cell(row=legend_row4b, column=1, value="KETERANGAN PRIORITY:")
leg4b.font = cell_font(bold=True, size=10)
leg4b.alignment = wrap_left()

for i, (label, color) in enumerate(PRIORITY_COLORS.items(), start=2):
    c = ws4.cell(row=legend_row4b, column=i, value=label)
    c.fill = header_fill(color)
    c.font = cell_font(bold=True, size=10, color="FFFFFF")
    c.alignment = wrap_center()
    c.border = thin_border()
ws4.row_dimensions[legend_row4b].height = 22


# ═══════════════════════════════════════════════════════════════════
# SHEET 5 – Fixing Summary (OWASP 2025)
# ═══════════════════════════════════════════════════════════════════
ws5 = wb.create_sheet("Fixing Summary")

ws5.merge_cells("A1:E1")
t5 = ws5["A1"]
t5.value = "FIXING SUMMARY – OWASP Top 10 2025 – Lab JobSeeker"
t5.font = cell_font(bold=True, color="FFFFFF", size=13)
t5.fill = header_fill("1A237E")
t5.alignment = wrap_center()
ws5.row_dimensions[1].height = 30

ws5.column_dimensions["A"].width = 26
ws5.column_dimensions["B"].width = 34
ws5.column_dimensions["C"].width = 22
ws5.column_dimensions["D"].width = 55
ws5.column_dimensions["E"].width = 18

apply_header(ws5, 2,
    ["File", "SCP Code", "OWASP 2025 Category", "What Was Fixed", "Status"],
    "283593", font_color="FFFFFF")
ws5.row_dimensions[2].height = 22

fixes = [
    # File | SCP | OWASP | What was fixed | Status
    ("config/env.php",
     "SCP-DP-001, SCP-CM-002",
     "A02 – Cryptographic Failures\nA05 – Security Misconfiguration",
     "Pindahkan credentials & JWT_SECRET ke environment variable; hapus nilai hardcode dari source code",
     "Fixed"),

    ("includes/session.php",
     "SCP-SM-001, SCP-SM-002\nSCP-SM-004",
     "A07 – Identification & Authentication Failures",
     "Set cookie_httponly=1, cookie_samesite=Strict; implementasi session timeout 30 menit dengan validasi last_activity",
     "Fixed"),

    ("includes/auth.php",
     "SCP-APM-001, SCP-APM-002\nSCP-SQL-001, SCP-AC-002\nSCP-AC-003, SCP-SM-003",
     "A03 – Injection\nA07 – Identification & Authentication Failures\nA01 – Broken Access Control",
     "Ganti query string concatenation dengan prepared statements; ganti plaintext password dengan password_hash(bcrypt); checkAccess() kini mengembalikan HTTP 403 + exit; regenerate session ID setelah login",
     "Fixed"),

    ("includes/jwt.php",
     "SCP-CM-001, SCP-CM-002\nSCP-CM-003, SCP-DP-002",
     "A02 – Cryptographic Failures\nA07 – Identification & Authentication Failures",
     "Tambahkan verifikasi HMAC signature dengan hash_equals(); validasi klaim exp; hapus method getSecret() yang mengekspos secret; secret tidak pernah dikirim ke client",
     "Fixed"),

    ("includes/file_upload.php",
     "SCP-FU-001, SCP-FU-002\nSCP-FU-003, SCP-FU-005",
     "A04 – Insecure Design\nA05 – Security Misconfiguration",
     "Whitelist MIME type dengan finfo (bukan ekstensi); batas ukuran 5 MB; simpan di private_uploads/ luar web root; rename file dengan UUID 16-byte; path traversal protection dengan realpath()",
     "Fixed"),

    ("includes/csrf.php (NEW)",
     "SCP-CSRF-001, SCP-CSRF-002",
     "A01 – Broken Access Control",
     "Buat helper CSRF::generate(), CSRF::input(), CSRF::verify() berbasis hash_equals(); token di-rotate setelah setiap verifikasi",
     "Fixed"),

    ("templates/header.php",
     "SCP-DP-002, SCP-DP-003",
     "A02 – Cryptographic Failures\nA09 – Security Logging Failures",
     "Hapus window.JWT_SECRET dari output HTML/JS; hapus window.currentUser yang mengekspos data session ke browser",
     "Fixed"),

    ("pages/auth/login.php",
     "SCP-IV-001, SCP-CSRF-001\nSCP-EH-003, SCP-DP-003\nSCP-DP-004",
     "A03 – Injection\nA07 – Authentication Failures\nA09 – Logging Failures",
     "Tambahkan CSRF token; hapus console.log credentials; hapus fetch ke evil-logger.com; pesan error generik; trim & validasi input server-side",
     "Fixed"),

    ("pages/auth/register.php",
     "SCP-IV-001, SCP-APM-001\nSCP-APM-002, SCP-CSRF-001\nSCP-EH-003",
     "A03 – Injection\nA07 – Authentication Failures",
     "Validasi kompleksitas password; hash bcrypt via Auth::register(); tambahkan CSRF token; sanitasi output XSS; hapus XSS reflected via username-feedback",
     "Fixed"),

    ("pages/auth/forgot-password.php",
     "SCP-IV-001, SCP-APM-005\nSCP-APM-006, SCP-EH-003\nSCP-CSRF-001, SCP-SQL-001",
     "A03 – Injection\nA07 – Authentication Failures",
     "Prepared statement; set expiry token 1 jam di kolom token_expires_at; hapus header injection ($_POST['custom_header']); respon generik (cegah user enumeration)",
     "Fixed"),

    ("pages/auth/reset-password.php",
     "SCP-APM-001, SCP-APM-002\nSCP-APM-005, SCP-CSRF-001\nSCP-SQL-001",
     "A07 – Authentication Failures",
     "Hash bcrypt password baru; validasi token hanya berlaku 1 jam (token_expires_at > NOW()); invalidasi token setelah digunakan; validasi kompleksitas password baru",
     "Fixed"),

    ("pages/auth/logout.php",
     "SCP-SM-005",
     "A07 – Authentication Failures",
     "Gunakan session_unset() + session_destroy(); hapus cookie session di browser dengan setcookie() expired",
     "Fixed"),

    ("pages/member/profile.php",
     "SCP-IV-001, SCP-AC-002\nSCP-AC-004, SCP-FU-001\nSCP-FU-002–005, SCP-CSRF-001\nSCP-OE-001",
     "A01 – Broken Access Control\nA04 – Insecure Design\nA03 – Injection",
     "user_id dari session (bukan URL); prepared statement; upload dengan MIME whitelist; hapus XSS output; CSRF token; hapus XSS via name-preview innerHTML",
     "Fixed"),

    ("pages/member/cv.php",
     "SCP-FU-001–005\nSCP-AC-004, SCP-CSRF-001\nSCP-SQL-001, SCP-OE-001",
     "A01 – Broken Access Control\nA04 – Insecure Design",
     "Upload hanya PDF/DOC/DOCX; delete via POST + CSRF (bukan GET); verifikasi ownership CV sebelum delete; path traversal protection; prepared statement",
     "Fixed"),

    ("pages/member/skills.php",
     "SCP-IV-001, SCP-AC-003\nSCP-AC-004, SCP-CSRF-001\nSCP-SQL-001, SCP-OE-001",
     "A01 – Broken Access Control\nA03 – Injection",
     "Prepared statement; delete via POST + CSRF; WHERE user_id pada delete (IDOR prevention); whitelist level; htmlspecialchars output",
     "Fixed"),

    ("pages/member/education.php",
     "SCP-IV-001, SCP-AC-003\nSCP-AC-004, SCP-CSRF-001\nSCP-SQL-001, SCP-OE-001",
     "A01 – Broken Access Control\nA03 – Injection",
     "Prepared statement; delete via POST + CSRF; WHERE user_id pada delete (IDOR prevention); validasi format tanggal; htmlspecialchars output",
     "Fixed"),

    ("pages/member/apply.php",
     "SCP-IV-001, SCP-AC-003\nSCP-AC-004, SCP-CSRF-001\nSCP-SQL-001, SCP-OE-001",
     "A01 – Broken Access Control\nA03 – Injection",
     "job_id dari GET dicast ke int; user_id dari session (tidak bisa dimanipulasi); prepared statement; CSRF token; htmlspecialchars output",
     "Fixed"),

    ("pages/company/jobs.php",
     "SCP-IV-001, SCP-AC-003\nSCP-AC-004, SCP-CSRF-001\nSCP-SQL-001, SCP-OE-001",
     "A01 – Broken Access Control\nA03 – Injection",
     "Delete via POST + CSRF; verifikasi company_id sebelum update/delete (IDOR prevention); prepared statement; whitelist job_type; htmlspecialchars output",
     "Fixed"),

    ("pages/company/applicants.php",
     "SCP-AC-001, SCP-AC-002\nSCP-AC-004, SCP-CSRF-001\nSCP-SQL-001, SCP-IV-001\nSCP-OE-001",
     "A01 – Broken Access Control\nA03 – Injection",
     "Verifikasi application milik job perusahaan ini sebelum update status; whitelist status; CSRF token; prepared statement; htmlspecialchars output",
     "Fixed"),
]

OWASP_COLORS = {
    "A01": "FFEBEE",
    "A02": "FFF3E0",
    "A03": "FFF9C4",
    "A04": "E8F5E9",
    "A05": "E3F2FD",
    "A07": "F3E5F5",
    "A09": "FCE4EC",
}

for idx, (file_, scp, owasp, what, status) in enumerate(fixes, start=3):
    is_even = idx % 2 == 0
    bg = "F5F5F5" if is_even else "FFFFFF"

    c1 = ws5.cell(row=idx, column=1, value=file_)
    c1.font = cell_font(bold=True, size=9, color="1A237E")
    c1.alignment = wrap_left()
    c1.fill = header_fill(bg)
    c1.border = thin_border()

    c2 = ws5.cell(row=idx, column=2, value=scp)
    c2.font = cell_font(size=9)
    c2.alignment = wrap_left()
    c2.fill = header_fill(bg)
    c2.border = thin_border()

    # Color by first OWASP category mentioned
    first_cat = owasp[:3]
    owasp_bg = OWASP_COLORS.get(first_cat, "FFFFFF")
    c3 = ws5.cell(row=idx, column=3, value=owasp)
    c3.font = cell_font(bold=True, size=9)
    c3.alignment = wrap_left()
    c3.fill = header_fill(owasp_bg)
    c3.border = thin_border()

    c4 = ws5.cell(row=idx, column=4, value=what)
    c4.font = cell_font(size=9)
    c4.alignment = wrap_left()
    c4.fill = header_fill(bg)
    c4.border = thin_border()

    c5 = ws5.cell(row=idx, column=5, value=status)
    c5.fill = header_fill("C8E6C9")
    c5.font = cell_font(bold=True, size=10, color="1B5E20")
    c5.alignment = wrap_center()
    c5.border = thin_border()

    ws5.row_dimensions[idx].height = 55

# Note row
note_row = len(fixes) + 4
note = ws5.cell(row=note_row, column=1,
    value="CATATAN: SCP-APM-003 (CAPTCHA) & SCP-APM-007 (Account Lockout) memerlukan integrasi layanan eksternal (Google reCAPTCHA / Redis) – ditandai Partially Implemented.")
note.font = cell_font(bold=True, size=9, color="B71C1C")
note.alignment = wrap_left()
ws5.merge_cells(f"A{note_row}:E{note_row}")
ws5.row_dimensions[note_row].height = 30


# ═══════════════════════════════════════════════════════════════════
# SAVE
# ═══════════════════════════════════════════════════════════════════
output_path = "/Volumes/Files/projects/lab-jobseeker/Master_Checklist_Secure_Coding.xlsx"
wb.save(output_path)
print(f"Saved: {output_path}")
