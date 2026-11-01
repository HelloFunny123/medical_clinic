#!/usr/bin/env python3
"""Generate a vector relationship diagram, validating its edges against the SQL."""
from pathlib import Path
from reportlab.graphics import renderPDF, renderSVG
from reportlab.graphics.shapes import Drawing, Rect, String, Line, Polygon
from reportlab.lib import colors

ROOT = Path(__file__).resolve().parents[1]


def generate():
    sql = (ROOT / 'database/schema.sql').read_text()
    edges = [
        ('services', 'category_id', 'categories', 'RESTRICT'),
        ('appointment_requests', 'user_id', 'users', 'RESTRICT'),
        ('appointment_requests', 'service_id', 'services', 'RESTRICT'),
        ('audit_logs', 'user_id', 'users', 'SET NULL'),
    ]
    for child, field, parent, deletion in edges:
        assert f'FOREIGN KEY({field}) REFERENCES {parent}(id) ON DELETE {deletion}' in sql
    out = ROOT / 'report/lab2/diagrams'
    out.mkdir(exist_ok=True)
    drawing = Drawing(490, 405)
    ink = colors.HexColor('#183c34')
    teal = colors.HexColor('#176b5b')
    light = colors.HexColor('#edf3e7')
    drawing.add(Rect(0, 0, 490, 405, fillColor=colors.white, strokeColor=None))

    def node(x, y, label, rows):
        drawing.add(Rect(x, y, 145, 115, rx=6, fillColor=colors.white, strokeColor=teal, strokeWidth=.8))
        drawing.add(Rect(x, y+88, 145, 27, fillColor=light, strokeColor=None))
        drawing.add(String(x+8, y+98, label, fontName='Helvetica-Bold', fontSize=9.5, fillColor=ink))
        for i, row in enumerate(rows):
            drawing.add(String(x+8, y+74-i*15, row, fontName='Helvetica', fontSize=9, fillColor=ink))

    def arrow(x1, y1, x2, y2, label, tx, ty):
        drawing.add(Line(x1, y1, x2, y2, strokeColor=teal, strokeWidth=1.2))
        if x1 == x2:
            direction = 1 if y2 > y1 else -1
            pts = [x2, y2, x2-3, y2-direction*6, x2+3, y2-direction*6]
        else:
            direction = 1 if x2 > x1 else -1
            pts = [x2, y2, x2-direction*6, y2-3, x2-direction*6, y2+3]
        drawing.add(Polygon(pts, fillColor=teal, strokeColor=None))
        drawing.add(String(tx, ty, label, fontName='Helvetica', fontSize=8, fillColor=ink))

    node(0, 255, 'users', ['PK id', 'UQ email', 'password_hash', 'role / is_active', 'full_name / created_at'])
    node(345, 255, 'services', ['PK id', 'FK category_id', 'name / price / duration', 'is_active / version', '... full dictionary in report'])
    node(0, 60, 'audit_logs', ['PK id', 'FK user_id (nullable)', 'action / entity / entity_id', 'description / created_at'])
    node(172.5, 60, 'appointment_requests', ['PK id; UQ reference', 'UQ request_key', 'FK user_id / service_id', 'service_name / quoted_price', 'status / version / visit time'])
    node(345, 60, 'categories', ['PK id', 'UQ name', 'description'])
    # Arrow direction is parent to child (one-to-many).
    arrow(72.5, 255, 72.5, 175, '1 : many; SET NULL', 80, 195)
    arrow(145, 275, 225, 175, '1 : many; RESTRICT', 82, 226)
    arrow(345, 275, 265, 175, '1 : many; RESTRICT', 320, 235)
    arrow(417.5, 175, 417.5, 255, '1 : many', 422, 213)
    drawing.add(String(345, 45, 'category deletion: RESTRICT', fontName='Helvetica', fontSize=8, fillColor=ink))
    drawing.add(Rect(172.5, 305, 145, 65, rx=6, fillColor=light, strokeColor=teal, strokeWidth=.8))
    drawing.add(String(180, 351, 'login_attempts', fontName='Helvetica-Bold', fontSize=10, fillColor=ink))
    drawing.add(String(180, 335, 'PK id; identifier; attempted_at', fontName='Helvetica', fontSize=8.5, fillColor=ink))
    drawing.add(String(180, 320, 'No foreign key (rate limiting).', fontName='Helvetica', fontSize=8.5, fillColor=ink))
    drawing.add(String(0, 390, 'Lab 2 relational model — key fields', fontName='Helvetica-Bold', fontSize=13, fillColor=teal))
    drawing.add(String(0, 35, 'PK: primary key. FK: foreign key. UQ: unique. Arrows: parent -> child.', fontName='Helvetica', fontSize=9, fillColor=ink))
    drawing.add(String(0, 19, 'Selected attributes shown; all fields and constraints are supplied in database/lab2.sql.', fontName='Helvetica', fontSize=8.5, fillColor=ink))
    pdf = out / 'relational-model.pdf'
    renderPDF.drawToFile(drawing, str(pdf))
    renderSVG.drawToFile(drawing, str(out / 'relational-model.svg'))
    import pymupdf
    with pymupdf.open(pdf) as document:
        document[0].get_pixmap(matrix=pymupdf.Matrix(3, 3)).save(out / 'relational-model.png')
    return out / 'relational-model.png'


if __name__ == '__main__':
    print(generate())
