#!/usr/bin/env python3
"""Genere les maquettes SVG haute fidelite de la plateforme patrimoine.

Sortie : docs/03-design/maquettes/{desktop,mobile}/*.svg
Relancer : py docs/03-design/tools/gen_maquettes.py
"""
from pathlib import Path

OUT = Path(__file__).resolve().parent.parent / "maquettes"

# ---------- Palette (design-system.md) ----------
C = {
    "primary900": "#0B2545", "primary700": "#13315C", "primary600": "#1D4E89",
    "primary100": "#DBEAFE", "accent500": "#B8860B", "accent100": "#FEF3C7",
    "success600": "#15803D", "success100": "#DCFCE7",
    "warning600": "#B45309", "warning100": "#FEF3C7",
    "danger600": "#B91C1C", "danger100": "#FEE2E2",
    "neutral900": "#111827", "neutral600": "#4B5563", "neutral500": "#6B7280",
    "neutral300": "#D1D5DB", "neutral100": "#F3F4F6", "neutral50": "#F9FAFB",
    "white": "#FFFFFF", "page": "#F3F4F6",
}
FONT = "font-family='Inter,Segoe UI,Arial,sans-serif'"


class SVG:
    def __init__(self, w, h, bg=C["page"]):
        self.w, self.h = w, h
        self.parts = [f"<svg xmlns='http://www.w3.org/2000/svg' width='{w}' height='{h}' viewBox='0 0 {w} {h}'>",
                      f"<rect width='{w}' height='{h}' fill='{bg}'/>"]

    def rect(self, x, y, w, h, fill, rx=0, stroke=None, sw=1, opacity=None):
        op = f" opacity='{opacity}'" if opacity else ""
        st = f" stroke='{stroke}' stroke-width='{sw}'" if stroke else ""
        self.parts.append(f"<rect x='{x}' y='{y}' width='{w}' height='{h}' rx='{rx}' fill='{fill}'{st}{op}/>")

    def circle(self, x, y, r, fill, stroke=None, sw=1):
        st = f" stroke='{stroke}' stroke-width='{sw}'" if stroke else ""
        self.parts.append(f"<circle cx='{x}' cy='{y}' r='{r}' fill='{fill}'{st}/>")

    def line(self, x1, y1, x2, y2, stroke=C["neutral300"], sw=1, dash=None):
        d = f" stroke-dasharray='{dash}'" if dash else ""
        self.parts.append(f"<line x1='{x1}' y1='{y1}' x2='{x2}' y2='{y2}' stroke='{stroke}' stroke-width='{sw}'{d}/>")

    def text(self, x, y, s, size=14, fill=C["neutral900"], weight=400, anchor="start", ls=None):
        tr = f" text-anchor='{anchor}'" if anchor != "start" else ""
        self.parts.append(f"<text x='{x}' y='{y}' font-size='{size}' fill='{fill}' font-weight='{weight}'{tr}{FONT}>{s}</text>")

    def path_bar(self, x, y, w, h, pct, color=C["primary600"]):
        """Barre de progression."""
        self.rect(x, y, w, h, C["neutral100"], rx=h / 2)
        self.rect(x, y, max(2, w * pct), h, color, rx=h / 2)

    def save(self, path):
        self.parts.append("</svg>")
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text("\n".join(self.parts), encoding="utf-8")


# ---------- Composants desktop ----------
def sidebar(s, items, active):
    s.rect(0, 0, 240, s.h, C["primary900"])
    s.rect(0, 0, 240, 64, C["primary700"])
    s.text(20, 40, "PATERNITE", 15, C["white"], 700)
    s.text(20, 57, "Etat togolais", 11, "#93C5FD")
    y = 88
    for label, icon in items:
        active_ = label == active
        if active_:
            s.rect(0, y - 18, 240, 40, C["primary700"])
            s.rect(0, y - 18, 3, 40, C["accent500"])
        s.text(20, y + 4, icon, 15, C["white"] if active_ else "#CBD5E1")
        s.text(46, y + 4, label, 13, C["white"] if active_ else "#CBD5E1", 600 if active_ else 400)
        y += 44
    s.line(20, s.h - 60, 220, s.h - 60, "#1E3A5F")
    s.text(20, s.h - 34, "v1.0 - 2026", 11, "#64748B")


def topbar(s, title, W=1440):
    s.rect(240, 0, W - 240, 56, C["white"])
    s.line(240, 56, W, 56, C["neutral300"])
    s.text(268, 35, title, 20, C["neutral900"], 700)
    # recherche
    s.rect(W - 420, 14, 220, 28, C["neutral100"], rx=6)
    s.text(W - 400, 33, "Rechercher un bien, un mouvement...", 12, C["neutral500"])
    s.circle(W - 60, 28, 14, C["primary100"])
    s.text(W - 60, 33, "A", 13, C["primary700"], 700, "middle")


def btn(s, x, y, w, label, kind="primary", h=36):
    styles = {
        "primary": (C["primary600"], C["white"]),
        "secondary": (C["white"], C["primary600"]),
        "danger": (C["danger600"], C["white"]),
        "ghost": (C["neutral100"], C["neutral900"]),
    }
    bg, fg = styles[kind]
    stroke = C["neutral300"] if kind == "secondary" else None
    s.rect(x, y, w, h, bg, rx=6, stroke=stroke)
    s.text(x + w / 2, y + h / 2 + 5, label, 13, fg, 600, "middle")


def badge(s, x, y, label, kind="info"):
    colors = {
        "info": (C["primary100"], C["primary700"]),
        "success": (C["success100"], C["success600"]),
        "warning": (C["warning100"], C["warning600"]),
        "danger": (C["danger100"], C["danger600"]),
        "neutral": (C["neutral100"], C["neutral600"]),
        "accent": (C["accent100"], "#92400E"),
    }
    bg, fg = colors[kind]
    w = 14 + len(label) * 7
    s.rect(x, y, w, 22, bg, rx=11)
    s.text(x + w / 2, y + 15, label, 11, fg, 600, "middle")
    return w


def kpi(s, x, y, w, label, value, delta, up=True):
    s.rect(x, y, w, 104, C["white"], rx=10, stroke=C["neutral300"])
    s.text(x + 18, y + 26, label, 12, C["neutral600"])
    s.text(x + 18, y + 58, value, 26, C["neutral900"], 700)
    dcol = C["success600"] if up else C["danger600"]
    s.text(x + 18, y + 84, ("▲ " if up else "▼ ") + delta, 12, dcol, 600)


def card(s, x, y, w, h, title=None):
    s.rect(x, y, w, h, C["white"], rx=10, stroke=C["neutral300"])
    if title:
        s.text(x + 18, y + 28, title, 15, C["neutral900"], 600)


def table(s, x, y, w, cols, rows, row_h=34, header_h=36):
    """cols: list of (label, width_ratio). rows: list of list of str."""
    total = sum(c[1] for c in cols)
    widths = [w * c[1] / total for c in cols]
    # header
    s.rect(x, y, w, header_h, C["primary900"], rx=8)
    cx = x
    for (label, _), cw in zip(cols, widths):
        s.text(cx + 10, y + header_h / 2 + 4, label, 11, C["white"], 600)
        cx += cw
    # rows
    ry = y + header_h
    for i, row in enumerate(rows):
        bg = C["neutral50"] if i % 2 else C["white"]
        s.rect(x, ry, w, row_h, bg)
        s.line(x, ry + row_h, x + w, ry + row_h, C["neutral300"])
        cx = x
        for cell, cw in zip(row, widths):
            s.text(cx + 10, ry + row_h / 2 + 4, cell, 12, C["neutral900"])
            cx += cw
        ry += row_h
    return ry


NAV = [("Tableau de bord", "⌂"), ("Biens", "▦"), ("Mouvements", "⇄"),
       ("Inventaires", "☰"), ("Maintenance", "⚒"), ("Valorisation", "¤"),
       ("Rapports", "◔"), ("Administration", "⚙")]


# ---------- Ecrans desktop ----------
def screen_dashboard():
    W, H = 1440, 900
    s = SVG(W, H)
    sidebar(s, NAV, "Tableau de bord")
    topbar(s, "Tableau de bord", W)
    x0 = 268
    s.text(x0, 100, "Bonjour, Komi Mensah", 22, C["neutral900"], 700)
    s.text(x0, 124, "Voici l'etat du patrimoine national au 12/11/2026", 13, C["neutral600"])
    # KPIs
    kpi(s, x0, 148, 268, "BIENS ENREGISTRES", "12 480", "+3,2%")
    kpi(s, x0 + 284, 148, 268, "VALEUR TOTALE", "84,2 Mds FCFA", "+1,8%")
    kpi(s, x0 + 568, 148, 268, "BIENS AFFECTES", "9 312", "75% du parc")
    kpi(s, x0 + 852, 148, 268, "EN MAINTENANCE", "214", "+12")
    # chart card + donut card
    card(s, x0, 272, 700, 300, "Repartition par categorie")
    cats = [("Vehicules", 32, C["primary600"]), ("Immobilier", 26, C["primary700"]),
            ("Informatique", 18, "#3B82F6"), ("Mobilier", 12, C["accent500"]),
            ("Equipements", 8, C["success600"]), ("Autres", 4, C["neutral300"])]
    bx, by = x0 + 40, 320
    for i, (name, pct, col) in enumerate(cats):
        yy = by + i * 34
        s.text(bx, yy + 14, name, 12, C["neutral600"])
        s.rect(bx + 120, yy, 380, 16, C["neutral100"], rx=3)
        s.rect(bx + 120, yy, 380 * pct / 100, 16, col, rx=3)
        s.text(bx + 520, yy + 14, f"{pct}%", 12, C["neutral900"], 600)
    card(s, x0 + 716, 272, 452, 300, "Par structure (top 5)")
    structs = [("MIN-DEF", 2400), ("MIN-FIN", 1900), ("MIN-EDU", 1500),
               ("MIN-SANTE", 1100), ("Autres", 5600)]
    maxv = max(v for _, v in structs)
    for i, (name, v) in enumerate(structs):
        yy = 330 + i * 42
        s.text(x0 + 736, yy + 14, name, 12, C["neutral600"])
        s.rect(x0 + 850, yy, 260, 18, C["neutral100"], rx=3)
        s.rect(x0 + 850, yy, 260 * v / maxv, 18, C["primary600"], rx=3)
        s.text(x0 + 1120, yy + 14, f"{v:,}".replace(",", " "), 12, C["neutral900"], 600)
    # recent movements + alerts
    card(s, x0, 588, 700, 264, "Mouvements recents")
    rows = [["MVT-1042", "Affectation", "PAT-2026-0012", "12/11 09:14"],
            ["MVT-1041", "Sortie", "PAT-2026-0088", "12/11 08:40"],
            ["MVT-1040", "Transfert", "PAT-2026-0201", "11/11 16:22"],
            ["MVT-1039", "Entree", "PAT-2026-0210", "11/11 14:05"]]
    table(s, x0 + 18, 626, 664,
          [("Ref", 1), ("Type", 1.4), ("Bien", 1.4), ("Date", 1)], rows, row_h=40)
    card(s, x0 + 716, 588, 452, 264, "Alertes")
    alerts = [("3 maintenances preventives echues", "warning"),
              ("Campagne inventaire S3 non cloturee", "warning"),
              ("5 demandes d'affectation en attente", "info"),
              ("Garantie expiree : 12 equipements", "danger")]
    for i, (msg, kind) in enumerate(alerts):
        yy = 630 + i * 50
        s.rect(x0 + 734, yy, 416, 40, C[{"warning": "warning100", "info": "primary100", "danger": "danger100"}[kind]], rx=8)
        s.text(x0 + 750, yy + 25, "!", 15, C[{"warning": "warning600", "info": "primary700", "danger": "danger600"}[kind]], 700)
        s.text(x0 + 778, yy + 25, msg, 12, C["neutral900"])
    s.save(OUT / "desktop" / "01-tableau-de-bord.svg")


def screen_login():
    W, H = 1440, 900
    s = SVG(W, H, C["primary900"])
    s.rect(W / 2 - 300, H / 2 - 220, 600, 440, C["white"], rx=14)
    s.rect(W / 2 - 300, H / 2 - 220, 600, 8, C["accent500"], rx=4)
    s.text(W / 2, H / 2 - 160, "PATERNITE", 24, C["primary900"], 800, "middle")
    s.text(W / 2, H / 2 - 136, "Etat togolais - Gestion du patrimoine", 13, C["neutral600"], 400, "middle")
    s.text(W / 2 - 240, H / 2 - 80, "Adresse e-mail", 13, C["neutral900"], 600)
    s.rect(W / 2 - 240, H / 2 - 64, 480, 40, C["neutral100"], rx=6, stroke=C["neutral300"])
    s.text(W / 2 - 220, H / 2 - 38, "prenom.nom@institution.go.tg", 13, C["neutral500"])
    s.text(W / 2 - 240, H / 2 + 0, "Mot de passe", 13, C["neutral900"], 600)
    s.rect(W / 2 - 240, H / 2 + 16, 480, 40, C["neutral100"], rx=6, stroke=C["neutral300"])
    s.text(W / 2 - 220, H / 2 + 42, "••••••••••••", 13, C["neutral500"])
    s.text(W / 2 + 200, H / 2 + 42, "👁", 14)
    s.rect(W / 2 - 240, H / 2 + 72, 16, 16, C["white"], rx=3, stroke=C["primary600"])
    s.text(W / 2 - 216, H / 2 + 85, "Se souvenir de moi", 12, C["neutral600"])
    s.text(W / 2 + 220, H / 2 + 85, "Mot de passe oublie ?", 12, C["primary600"], 600, "end")
    btn(s, W / 2 - 240, H / 2 + 104, 480, "SE CONNECTER", h=44)
    s.text(W / 2, H / 2 + 200, "Authentification securisee - JWT + 2FA", 11, C["neutral500"], 400, "middle")
    s.text(W / 2, H + 40, "Plateforme de gestion du patrimoine de l'Etat togolais", 13, "#93C5FD", 400, "middle")
    s.save(OUT / "desktop" / "02-connexion.svg")


def screen_assets():
    W, H = 1440, 900
    s = SVG(W, H)
    sidebar(s, NAV, "Biens")
    topbar(s, "Biens", W)
    x0 = 268
    s.text(x0, 100, "Biens du patrimoine", 22, C["neutral900"], 700)
    btn(s, W - 300, 76, 200, "+ Nouveau bien", h=36)
    # filters
    fy = 120
    s.rect(x0, fy, 300, 36, C["white"], rx=6, stroke=C["neutral300"])
    s.text(x0 + 12, fy + 23, "Rechercher (code, designation)...", 12, C["neutral500"])
    for i, (fx, label) in enumerate([(x0 + 312, "Type v"), (x0 + 452, "Structure v"),
                                    (x0 + 592, "Categorie v"), (x0 + 732, "Etat v")]):
        s.rect(fx, fy, 130, 36, C["white"], rx=6, stroke=C["neutral300"])
        s.text(fx + 12, fy + 23, label, 12, C["neutral600"])
    btn(s, x0 + 872, fy, 90, "Filtrer", "secondary", 36)
    btn(s, x0 + 972, fy, 90, "Export CSV", "ghost", 36)
    # table
    rows = [
        ["PAT-2026-0012", "Toyota Land Cruiser GX", "Vehicule", "MIN-DEF", "Affecte", "36,8 M"],
        ["PAT-2026-0013", "Batiment administratif A", "Immo.", "MIN-FIN", "Affecte", "144,0 M"],
        ["PAT-2026-0014", "PC Dell OptiPlex 7090", "Info.", "MIN-EDU", "Disponible", "350 k"],
        ["PAT-2026-0015", "Bureau direction (lot 20)", "Mobilier", "MIN-SANTE", "En maintenance", "1,2 M"],
        ["PAT-2026-0016", "Climatiseur 24000 BTU", "Equip.", "MIN-DEF", "Disponible", "480 k"],
        ["PAT-2026-0017", "Imprimante laser HP", "Info.", "MIN-FIN", "Sorti", "210 k"],
    ]
    etats = {"Affecte": "info", "Disponible": "success", "En maintenance": "warning", "Sorti": "neutral"}
    # header custom for badges
    y = 180
    cols = [("Code inventaire", 1.6), ("Designation", 2.2), ("Categorie", 1), ("Structure", 1.1), ("Etat", 1.2), ("VNC", 1)]
    total = sum(c[1] for c in cols); w = W - x0 - 40
    widths = [w * c[1] / total for c in cols]
    s.rect(x0, y, w, 36, C["primary900"], rx=8)
    cx = x0
    for (label, _), cw in zip(cols, widths):
        s.text(cx + 10, y + 23, label, 11, C["white"], 600); cx += cw
    ry = y + 36
    for i, row in enumerate(rows):
        bg = C["neutral50"] if i % 2 else C["white"]
        s.rect(x0, ry, w, 44, bg); s.line(x0, ry + 44, x0 + w, ry + 44, C["neutral300"])
        cx = x0
        for j, (cell, cw) in enumerate(zip(row, widths)):
            if j == 4:
                badge(s, cx + 10, ry + 11, cell, etats.get(cell, "neutral"))
            elif j == 5:
                s.text(cx + cw - 60, ry + 27, cell, 12, C["neutral900"], 600, "end")
            else:
                s.text(cx + 10, ry + 27, cell, 12, C["neutral900"])
            cx += cw
        ry += 44
    # pagination
    s.text(x0, ry + 30, "<  1  2  3  ...  42  >", 13, C["primary600"], 600)
    s.text(W - x0 - 40, ry + 30, "20 / page", 12, C["neutral500"], 400, "end")
    s.save(OUT / "desktop" / "03-liste-biens.svg")


def screen_asset_detail():
    W, H = 1440, 900
    s = SVG(W, H)
    sidebar(s, NAV, "Biens")
    topbar(s, "Fiche bien - PAT-2026-0012", W)
    x0 = 268
    # header card
    card(s, x0, 84, W - x0 - 40, 150)
    s.rect(x0 + 18, 104, 110, 110, C["neutral100"], rx=8, stroke=C["neutral300"])
    s.text(x0 + 73, 165, "QR", 30, C["neutral900"], 700, "middle")
    s.text(x0 + 148, 116, "Toyota Land Cruiser GX 2023", 22, C["neutral900"], 700)
    s.text(x0 + 148, 140, "PAT-2026-0012 - Vehicule de service", 13, C["neutral600"])
    badge(s, x0 + 148, 156, "Affecte", "info")
    badge(s, x0 + 148 + 78, 156, "Bon etat", "success")
    btn(s, W - 340, 110, 110, "Editer", "secondary", 34)
    btn(s, W - 218, 110, 110, "Imprimer QR", "secondary", 34)
    # tabs
    tabs = ["Informations", "Mouvements", "Maintenance", "Documents", "Photos"]
    tx = x0
    for i, t in enumerate(tabs):
        if i == 0:
            s.rect(tx, 252, len(t) * 8 + 20, 34, C["primary600"], rx=6)
            s.text(tx + 12, 274, t, 13, C["white"], 600)
        else:
            s.text(tx + 12, 274, t, 13, C["primary600"], 600)
        tx += len(t) * 8 + 44
    s.line(x0, 286, W - 40, 286, C["neutral300"])
    # info grid
    fields = [("Designation", "Toyota Land Cruiser GX 2023"), ("Categorie", "Vehicule"),
              ("Immatriculation", "TG-1234-AB"), ("Chassis", "JTMBFREV8P0123456"),
              ("Acquisition", "01/03/2024 - Achat"), ("Valeur acquisition", "45 000 000 FCFA"),
              ("Amortissement cumule", "8 200 000 FCFA"), ("Valeur nette (VNC)", "36 800 000 FCFA"),
              ("Structure responsable", "Ministere de la Defense"), ("Site", "Site central de Lome"),
              ("Localisation", "Bat. B - RdC - Bureau 12"), ("Prochaine maintenance", "15/01/2027")]
    gx, gy = x0, 310
    colw = (W - x0 - 40 - 24) / 2
    for i, (k, v) in enumerate(fields):
        cx = gx + (i % 2) * (colw + 24)
        cy = gy + (i // 2) * 52
        s.text(cx, cy + 18, k, 12, C["neutral500"])
        s.text(cx, cy + 38, v, 14, C["neutral900"], 600)
    # actions
    ay = gy + 7 * 52 + 16
    btn(s, gx, ay, 160, "Demander affectation", h=38)
    btn(s, gx + 176, ay, 140, "Declarer panne", "secondary", 38)
    btn(s, gx + 332, ay, 140, "Sortir du patrimoine", "danger", 38)
    s.save(OUT / "desktop" / "04-fiche-bien.svg")


def screen_inventory():
    W, H = 1440, 900
    s = SVG(W, H)
    sidebar(s, NAV, "Inventaires")
    topbar(s, "Campagne INV-2026-S3", W)
    x0 = 268
    s.text(x0, 100, "Inventaire T3 - Structures centrales", 22, C["neutral900"], 700)
    s.text(x0, 124, "MIN-DEF, MIN-FIN - Site central de Lome - 01/12 au 15/12/2026", 13, C["neutral600"])
    card(s, x0, 148, 700, 200)
    s.text(x0 + 18, 182, "Avancement global", 15, C["neutral900"], 600)
    s.path_bar(x0 + 18, 200, 660, 18, 0.68, C["primary600"])
    s.text(x0 + 18, 240, "1 240 / 1 820 biens inventories (68%)", 13, C["neutral600"])
    s.text(x0 + 18, 268, "Equipes :", 13, C["neutral900"], 600)
    s.path_bar(x0 + 90, 256, 280, 12, 0.82, C["success600"])
    s.text(x0 + 380, 267, "Equipe A - 82%", 12, C["neutral600"])
    s.path_bar(x0 + 90, 280, 280, 12, 0.54, C["warning600"])
    s.text(x0 + 380, 291, "Equipe B - 54%", 12, C["neutral600"])
    badge(s, x0 + 560, 160, "En cours", "info")
    # ecarts
    card(s, x0 + 716, 148, 452, 200, "Ecarts detectes")
    rows = [["Manquants", "12"], ["Excedentaires", "3"], ["Degrades", "27"]]
    table(s, x0 + 734, 190, 416, [("Type", 2), ("Nombre", 1)], rows, row_h=36)
    # recent constats
    card(s, x0, 364, W - x0 - 40, 320, "Constats recents")
    rows = [["PAT-2026-0012", "Present", "Bon", "K. Mensah", "10:32"],
            ["PAT-2026-0013", "Present", "Degrade", "A. Lawson", "10:28"],
            ["PAT-2026-0088", "Absent", "-", "K. Mensah", "10:15"],
            ["PAT-2026-0201", "Present", "Bon", "E. Agbeko", "09:58"],
            ["PAT-2026-0202", "Hors service", "HS", "E. Agbeko", "09:44"]]
    etats = {"Present": "success", "Absent": "danger", "Hors service": "warning"}
    y = 404
    cols = [("Code", 1.4), ("Constate", 1.2), ("Etat", 1), ("Agent", 1.6), ("Heure", 1)]
    total = sum(c[1] for c in cols); w = W - x0 - 80
    widths = [w * c[1] / total for c in cols]
    s.rect(x0 + 18, y, w, 34, C["primary900"], rx=8)
    cx = x0 + 18
    for (label, _), cw in zip(cols, widths):
        s.text(cx + 10, y + 22, label, 11, C["white"], 600); cx += cw
    ry = y + 34
    for i, row in enumerate(rows):
        bg = C["neutral50"] if i % 2 else C["white"]
        s.rect(x0 + 18, ry, w, 38, bg); s.line(x0 + 18, ry + 38, x0 + 18 + w, ry + 38, C["neutral300"])
        cx = x0 + 18
        for j, (cell, cw) in enumerate(zip(row, widths)):
            if j in (1, 2):
                badge(s, cx + 10, ry + 8, cell, etats.get(cell, "neutral"))
            else:
                s.text(cx + 10, ry + 24, cell, 12, C["neutral900"])
            cx += cw
        ry += 38
    btn(s, x0, 720, 180, "Exporter la liste", "secondary", 38)
    btn(s, x0 + 196, 720, 220, "Cloturer la campagne", "danger", 38)
    s.save(OUT / "desktop" / "05-campagne-inventaire.svg")


SCREENS = {
    "01-tableau-de-bord": screen_dashboard,
    "02-connexion": screen_login,
    "03-liste-biens": screen_assets,
    "04-fiche-bien": screen_asset_detail,
    "05-campagne-inventaire": screen_inventory,
}


def screen_mobile_login():
    W, H = 390, 844
    s = SVG(W, H, C["primary900"])
    s.rect(0, 0, W, 200, C["primary700"])
    s.text(W / 2, 120, "PATERNITE", 22, C["white"], 800, "middle")
    s.text(W / 2, 146, "Etat togolais", 13, "#93C5FD", 400, "middle")
    s.rect(20, 240, W - 40, 380, C["white"], rx=14)
    s.text(52, 292, "Connexion", 20, C["neutral900"], 700)
    s.text(52, 316, "Accedez a la plateforme patrimoine", 12, C["neutral600"])
    s.text(52, 368, "Adresse e-mail", 13, C["neutral900"], 600)
    s.rect(52, 382, W - 104, 48, C["neutral100"], rx=8, stroke=C["neutral300"])
    s.text(68, 412, "prenom.nom@institution.go.tg", 13, C["neutral500"])
    s.text(52, 456, "Mot de passe", 13, C["neutral900"], 600)
    s.rect(52, 470, W - 104, 48, C["neutral100"], rx=8, stroke=C["neutral300"])
    s.text(68, 500, "••••••••••••", 13, C["neutral500"])
    s.rect(52, 546, W - 104, 50, C["primary600"], rx=8)
    s.text(W / 2, 578, "SE CONNECTER", 15, C["white"], 700, "middle")
    s.text(W / 2, 640, "Mot de passe oublie ?", 13, C["primary600"], 600, "middle")
    s.text(W / 2, H - 40, "v1.0 - Etat togolais", 11, "#64748B", 400, "middle")
    s.save(OUT / "mobile" / "01-connexion.svg")


def screen_mobile_inventory():
    W, H = 390, 844
    s = SVG(W, H, C["page"])
    # header
    s.rect(0, 0, W, 56, C["primary900"])
    s.text(16, 35, "<", 20, C["white"], 700)
    s.text(W / 2, 35, "INV-2026-S3", 15, C["white"], 600, "middle")
    s.text(W - 40, 35, "≡", 20, C["white"], 700, "middle")
    # progression
    s.rect(0, 56, W, 90, C["white"])
    s.line(0, 146, W, 146, C["neutral300"])
    s.text(16, 82, "Progression : 68%", 14, C["neutral900"], 700)
    s.path_bar(16, 94, W - 32, 14, 0.68, C["primary600"])
    s.text(16, 128, "1 240 / 1 820 biens inventories", 12, C["neutral600"])
    # scan button
    s.rect(16, 170, W - 32, 170, C["primary600"], rx=16)
    s.circle(W / 2, 240, 42, "#FFFFFF22" if False else "#FFFFFF")
    s.circle(W / 2, 240, 30, C["primary600"])
    s.text(W / 2, 247, "⌗", 30, C["white"], 700, "middle")
    s.text(W / 2, 330, "SCANNER UN CODE", 16, C["white"], 800, "middle")
    s.text(W / 2, 352, "QR code ou code-barres", 12, "#BFDBFE", 400, "middle")
    # search
    s.rect(16, 356, W - 32, 48, C["white"], rx=10, stroke=C["neutral300"])
    s.text(32, 386, "PAT-...", 14, C["neutral500"])
    s.text(W - 60, 386, "⌕", 18, C["neutral500"], 700, "middle")
    # recent constats
    s.text(16, 432, "Derniers constats", 15, C["neutral900"], 700)
    constats = [("PAT-2026-0012", "Present", "Bon", "success"),
                ("PAT-2026-0011", "Absent", "-", "danger"),
                ("PAT-2026-0010", "Present", "Degrade", "warning")]
    for i, (code, pres, etat, kind) in enumerate(constats):
        yy = 446 + i * 74
        s.rect(16, yy, W - 32, 62, C["white"], rx=10, stroke=C["neutral300"])
        s.text(32, yy + 26, code, 14, C["neutral900"], 600)
        s.text(32, yy + 48, pres, 12, C["neutral600"])
        badge(s, W - 130, yy + 20, etat, kind)
    # offline badge
    s.rect(16, 700, W - 32, 40, C["warning100"], rx=10)
    s.text(W / 2, 725, "Hors ligne - 3 constats en attente de sync", 12, C["warning600"], 600, "middle")
    # bottom nav
    s.rect(0, 760, W, 84, C["white"])
    s.line(0, 760, W, 760, C["neutral300"])
    for i, (lbl, ic) in enumerate([("Constats", "☰"), ("Carte", "◎"), ("Sync (3)", "↻")]):
        cx = W * (i + 0.5) / 3
        s.text(cx, 800, ic, 18, C["primary600"] if i == 0 else C["neutral500"], 700, "middle")
        s.text(cx, 822, lbl, 11, C["primary600"] if i == 0 else C["neutral500"], 600, "middle")
    s.save(OUT / "mobile" / "02-inventaire-terrain.svg")


def screen_mobile_detail():
    W, H = 390, 844
    s = SVG(W, H, C["page"])
    s.rect(0, 0, W, 56, C["primary900"])
    s.text(16, 35, "<", 20, C["white"], 700)
    s.text(W / 2, 35, "PAT-2026-0012", 15, C["white"], 600, "middle")
    # card
    s.rect(16, 72, W - 32, 560, C["white"], rx=12, stroke=C["neutral300"])
    s.rect(36, 96, 90, 90, C["neutral100"], rx=8, stroke=C["neutral300"])
    s.text(81, 152, "QR", 22, C["neutral900"], 700, "middle")
    s.text(146, 112, "Toyota Land Cruiser", 17, C["neutral900"], 700)
    s.text(146, 136, "PAT-2026-0012 - Vehicule", 12, C["neutral600"])
    badge(s, 146, 152, "Affecte", "info")
    # fields
    fields = [("Immatriculation", "TG-1234-AB"), ("Chassis", "JTMBFREV8P..."),
              ("Valeur (VNC)", "36 800 000 FCFA"), ("Structure", "MIN-DEF"),
              ("Site", "Lome Central")]
    for i, (k, v) in enumerate(fields):
        yy = 220 + i * 62
        s.text(36, yy + 18, k, 12, C["neutral500"])
        s.text(36, yy + 40, v, 15, C["neutral900"], 600)
        if i < len(fields) - 1:
            s.line(36, yy + 52, W - 52, yy + 52, C["neutral100"])
    # actions
    btn(s, 36, 620, 150, "Affecter", h=42)
    btn(s, 200, 620, 154, "Panne", "secondary", 42)
    # bottom nav
    s.rect(0, 760, W, 84, C["white"])
    s.line(0, 760, W, 760, C["neutral300"])
    for i, (lbl, ic) in enumerate([("Accueil", "⌂"), ("Biens", "▦"), ("Scan", "⌗"), ("Plus", "⋯")]):
        cx = W * (i + 0.5) / 4
        col = C["primary600"] if i == 1 else C["neutral500"]
        s.text(cx, 800, ic, 18, col, 700, "middle")
        s.text(cx, 822, lbl, 11, col, 600, "middle")
    s.save(OUT / "mobile" / "03-fiche-bien.svg")


MOBILE_SCREENS = {
    "01-connexion": screen_mobile_login,
    "02-inventaire-terrain": screen_mobile_inventory,
    "03-fiche-bien": screen_mobile_detail,
}

if __name__ == "__main__":
    for name, fn in SCREENS.items():
        fn()
        print("desktop/" + name)
    for name, fn in MOBILE_SCREENS.items():
        fn()
        print("mobile/" + name)
    print("Termine.")
