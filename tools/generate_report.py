#!/usr/bin/env python3
"""Generate matching Word/PDF submission reports from actual browser evidence."""
from pathlib import Path
import json
from html import escape
from PIL import Image as PILImage
from docx import Document
from docx.shared import Inches, Pt, Cm, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Image, Table, TableStyle, PageBreak, KeepTogether
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER
from reportlab.lib.pagesizes import A4
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'report'
SHOTS = OUT / 'screenshots'
STEM = 'LAB_1_3_Report_Huynh_Duc_Phat_2452940'
data = json.loads((ROOT / 'tests/results.json').read_text())
if data.get('error'):
    raise SystemExit('Browser verification has not passed: ' + data['error'])
if len(data.get('accessibility', [])) != 6 or any(a['violations'] for a in data['accessibility']):
    raise SystemExit('Six passing accessibility audits are required before reporting success.')
OUT.mkdir(exist_ok=True)
(OUT / 'verification.json').write_text(json.dumps(data, indent=2, ensure_ascii=False))

FONT = Path('/System/Library/Fonts/Supplemental')
pdfmetrics.registerFont(TTFont('ReportArial', str(FONT / 'Arial.ttf')))
pdfmetrics.registerFont(TTFont('ReportArialBold', str(FONT / 'Arial Bold.ttf')))
pdfmetrics.registerFontFamily('ReportArial', normal='ReportArial', bold='ReportArialBold')
TEAL = '#176B5B'
INK = '#183C34'
styles = getSampleStyleSheet()
styles.add(ParagraphStyle(name='BodyReport', fontName='ReportArial', fontSize=10, leading=15, textColor=colors.HexColor(INK), spaceAfter=9))
styles.add(ParagraphStyle(name='TitleReport', fontName='ReportArialBold', fontSize=21, leading=27, textColor=colors.HexColor(TEAL), spaceAfter=17))
styles.add(ParagraphStyle(name='SubReport', fontName='ReportArialBold', fontSize=11, leading=16, textColor=colors.HexColor(INK), spaceBefore=9, spaceAfter=7))
styles.add(ParagraphStyle(name='CaptionReport', fontName='ReportArial', fontSize=8, leading=11, alignment=TA_CENTER, textColor=colors.HexColor('#52665A'), spaceBefore=6, spaceAfter=13))
styles.add(ParagraphStyle(name='CellReport', fontName='ReportArial', fontSize=8.3, leading=11, textColor=colors.HexColor(INK)))
styles.add(ParagraphStyle(name='CoverReport', fontName='ReportArial', fontSize=12, leading=20, alignment=TA_CENTER, textColor=colors.HexColor(INK), spaceAfter=9))
styles.add(ParagraphStyle(name='CoverTitle', fontName='ReportArialBold', fontSize=21, leading=29, alignment=TA_CENTER, textColor=colors.HexColor(TEAL), spaceAfter=11))

doc = Document()
section = doc.sections[0]
section.page_width, section.page_height = Cm(21), Cm(29.7)
section.top_margin, section.bottom_margin = Cm(1.8), Cm(1.8)
section.left_margin, section.right_margin = Cm(1.85), Cm(1.85)
section.header_distance, section.footer_distance = Cm(.7), Cm(.7)
normal = doc.styles['Normal']
normal.font.name = 'Arial'; normal.font.size = Pt(10)
normal.paragraph_format.space_after = Pt(8)
normal.paragraph_format.line_spacing = 1.15
for name, size in [('Heading 1',20), ('Heading 2',11)]:
    style = doc.styles[name]; style.font.name = 'Arial'; style.font.size = Pt(size); style.font.color.rgb = RGBColor.from_string(TEAL[1:])
    style.paragraph_format.space_after = Pt(12)
footer = section.footer.paragraphs[0]
footer.alignment = WD_ALIGN_PARAGRAPH.RIGHT
run = footer.add_run('Huỳnh Đức Phát · 2452940 | LAB 1.3 | ')
run.font.size = Pt(8)
field = OxmlElement('w:fldSimple'); field.set(qn('w:instr'),'PAGE'); footer._p.append(field)
story = []
WIDTH = A4[0] - 2 * 52.5

def para(text, bold=False):
    story.append(Paragraph(escape(text), styles['SubReport' if bold else 'BodyReport']))
    doc.add_paragraph(text, style='Heading 2' if bold else None)

def title(text):
    story.append(Paragraph(escape(text), styles['TitleReport']))
    doc.add_heading(text,1)

def newpage():
    story.append(PageBreak()); doc.add_page_break()

def image(name, caption, width=WIDTH, maxheight=470):
    source=SHOTS/name
    w,h=PILImage.open(source).size
    scale=min(width/w,maxheight/h)
    story.append(Image(str(source),width=w*scale,height=h*scale))
    story.append(Paragraph(escape(caption),styles['CaptionReport']))
    p=doc.add_paragraph(); p.alignment=WD_ALIGN_PARAGRAPH.CENTER
    p.add_run().add_picture(str(source),width=Inches(w*scale/72))
    p=doc.add_paragraph(caption); p.alignment=WD_ALIGN_PARAGRAPH.CENTER
    for run in p.runs: run.italic=True; run.font.size=Pt(8)

def pair(names,captions,width=181,maxheight=325):
    images=[]
    for name in names:
        w,h=PILImage.open(SHOTS/name).size; scale=min(width/w,maxheight/h)
        images.append(Image(str(SHOTS/name),w*scale,h*scale))
    table=Table([images,[Paragraph(escape(c),styles['CaptionReport']) for c in captions]],colWidths=[WIDTH/2]*2)
    table.setStyle(TableStyle([('VALIGN',(0,0),(-1,-1),'TOP'),('ALIGN',(0,0),(-1,-1),'CENTER'),('TOPPADDING',(0,0),(-1,-1),0),('BOTTOMPADDING',(0,0),(-1,-1),4)]))
    story.append(table)
    table=doc.add_table(rows=2,cols=2)
    for index,name in enumerate(names):
        w,h=PILImage.open(SHOTS/name).size; scale=min(width/w,maxheight/h)
        p=table.cell(0,index).paragraphs[0]; p.alignment=WD_ALIGN_PARAGRAPH.CENTER
        p.add_run().add_picture(str(SHOTS/name),width=Inches(w*scale/72))
        p=table.cell(1,index).paragraphs[0]; p.alignment=WD_ALIGN_PARAGRAPH.CENTER
        run=p.add_run(captions[index]);run.font.size=Pt(8)

def grid(headers,rows,widths=None,small=False):
    widths=widths or [WIDTH/len(headers)]*len(headers)
    content=[[Paragraph(escape(str(cell)),styles['CellReport']) for cell in row] for row in [headers]+rows]
    table=Table(content,colWidths=widths,repeatRows=1,hAlign='LEFT')
    table.setStyle(TableStyle([('BACKGROUND',(0,0),(-1,0),colors.HexColor('#E7EFE3')),('FONTNAME',(0,0),(-1,0),'ReportArialBold'),('GRID',(0,0),(-1,-1),.4,colors.HexColor('#D8E1D2')),('VALIGN',(0,0),(-1,-1),'TOP'),('LEFTPADDING',(0,0),(-1,-1),8),('RIGHTPADDING',(0,0),(-1,-1),8),('TOPPADDING',(0,0),(-1,-1),4 if small else 6),('BOTTOMPADDING',(0,0),(-1,-1),4 if small else 6)]))
    story.append(table);story.append(Spacer(1,10))
    table=doc.add_table(rows=1,cols=len(headers));table.style='Table Grid'
    for i,header in enumerate(headers):
        cell=table.rows[0].cells[i]; cell.text=str(header)
        shade=OxmlElement('w:shd');shade.set(qn('w:fill'),'E7EFE3');cell._tc.get_or_add_tcPr().append(shade)
    for row in rows:
        for cell,value in zip(table.add_row().cells,row): cell.text=str(value)
    for row in table.rows:
        for cell in row.cells:
            for p in cell.paragraphs:
                p.paragraph_format.space_after=Pt(2);p.paragraph_format.space_before=Pt(2);p.paragraph_format.line_spacing=1
                for run in p.runs:run.font.size=Pt(8 if small else 8.5)
    doc.add_paragraph().paragraph_format.space_after=Pt(1)

def coverline(text,large=False,bold=False):
    story.append(Paragraph(escape(text),styles['CoverTitle' if large else 'CoverReport']))
    p=doc.add_paragraph();p.alignment=WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_after=Pt(10)
    run=p.add_run(text);run.bold=bold or large;run.font.size=Pt(20 if large else 12)
    if large:run.font.color.rgb=RGBColor.from_string(TEAL[1:])

# 1. Cover — wording and personal information supplied by the student.
story.append(Spacer(1,32));doc.add_paragraph().paragraph_format.space_after=Pt(25)
coverline('VIETNAM NATIONAL UNIVERSITY HO CHI MINH CITY')
coverline('HO CHI MINH CITY UNIVERSITY OF TECHNOLOGY')
coverline('FACULTY OF COMPUTER SCIENCE',bold=True)
coverline('——————– * ———————',bold=True)
story.append(Spacer(1,65));doc.add_paragraph().paragraph_format.space_after=Pt(48)
coverline('Laboratory Report')
coverline('LAB 1.3 – Website Layout & Frontend',large=True)
coverline('Foundation',large=True)
story.append(Spacer(1,30));doc.add_paragraph().paragraph_format.space_after=Pt(20)
coverline('Teacher: NGUYỄN ĐỨC THÁI')
coverline('Class: CC01')
coverline('—o0o—')
coverline('Huỳnh Đức Phát (2452940)',bold=True)
story.append(Spacer(1,35));doc.add_paragraph().paragraph_format.space_after=Pt(24)
coverline('Semester project: Medical Clinic')

# 2. Structure.
newpage();title('1. Project structure')
para('Wellnest Medical Clinic implements the frontend foundation required by LAB 1.3. In the Medical Clinic domain, catalog items are consultation services. The project is placed under the Apache document root at xamppfiles/htdocs/medical_clinic/.')
image('01-project-structure.png','Figure 1. Actual project directory entries; supporting folders are collapsed.',width=350,maxheight=410)
para('Required files: public/index.php, public/css/style.css, public/js/script.js, public/images/, pages/item_details.php, components/header.php, and components/footer.php.')
para('PHP require_once includes the shared header and footer on the homepage and additional pages. Supporting app/ and database/ files keep service names, descriptions, categories, images, and prices consistent between pages.')

# 3. Homepage.
newpage();title('2. Homepage and reusable layout')
image('02-homepage-desktop.png','Figure 2. Homepage at the required desktop viewport, 1366 × 768.',maxheight=285)
para('The homepage contains a brand header, navigation, hero, item catalog, and footer. The navigation includes Home, Items / Services, Search, and Contact. Book a visit provides a domain-appropriate action. Bootstrap navbar-expand-lg collapses navigation below 992px.')
grid(['Required feature','Implementation'],[
    ['HTML5 foundation','DOCTYPE, html lang="en", UTF-8, viewport meta, meaningful title; header, nav, main, section, article and footer.'],
    ['Hero section','Medical Clinic introduction, local doctor photograph, and Explore our services link.'],
    ['Reusable components','components/header.php and components/footer.php are included using require_once.'],
    ['Footer','Wellnest name, current-year copyright, illustrative email/location and contact information.'],
    ['Custom CSS3','Typography, teal/cream palette, spacing, image shapes, cards, buttons, footer, hover effects and responsive media queries.'],
],widths=[126,WIDTH-126])

# 4. Catalog.
newpage();title('3. Medical service catalog')
image('02d-six-service-catalog.png','Figure 3. All six service cards in the three-column desktop catalog.',maxheight=358)
grid(['Item name','Category','Price (VND)'],[
    ['General Consultation','Primary care','250,000'],['Dental Check-up','Dental care','350,000'],['Heart Health Consultation','Specialist care','500,000'],['Skin & Dermatology','Specialist care','400,000'],['Child Wellness Visit','Family health','300,000'],['Preventive Health Review','Primary care','450,000'],
],widths=[226,150,WIDTH-376],small=True)
para('Each Bootstrap card contains an image, service name, short description, category, consultation price and View details link. The exact grid classes col-12 col-md-6 col-lg-4 produce one card per row on mobile, two on tablet and three on desktop.')
para('Prices and services are illustrative semester-project content. Stock imagery, brand text, colors, button labels and descriptions are customized for the Medical Clinic domain.')

# 5. Detail.
newpage();title('4. Item details page')
image('05-item-details.png','Figure 4. pages/item_details.php?id=1 at 1366 × 768.',maxheight=285)
grid(['Specification requirement','Example on the details page'],[
    ['Item name','General Consultation'],['Item image','Local healthcare image with descriptive alternative text.'],['Category','Primary care'],['Description','Service introduction, full description, inclusions and visit preparation.'],['Price','250,000 ₫, consistent with the homepage catalog.'],['Action button','Request an appointment opens the form with this service preselected.'],
],widths=[151,WIDTH-151])
para('All six item cards open their own details using a validated service ID. The shared header/footer retain consistent navigation and visual styling. An unknown or malformed ID returns an HTTP 404 page with a link back to services.')

# 6. Responsive mobile.
newpage();title('5. Responsive design: mobile')
pair(['03-homepage-mobile.png','03d-mobile-catalog.png'],['Figure 5. Hero at 375 × 667.','Figure 6. Single-column mobile catalog.'])
para('Mobile checks confirm readable text, stacked sections, contained images, accessible controls and no horizontal scrolling. The hamburger opens and closes; selecting Search closes the menu and focuses the service search field.')
grid(['Required viewport','Catalog layout','Result'],[
    ['Mobile: 375 × 667','1 card per row','Passed'],['Tablet: 768 × 1024','2 cards per row','Passed'],['Desktop: 1366 × 768','3 cards per row','Passed'],
],widths=[177,178,WIDTH-355])
para('Additional checked widths: 320, 390, 576, 820, 991, 992, 1024 and 1920px. The homepage, service details and appointment form were checked at all 11 sizes. No horizontal overflow or overflowing images was detected.')

# 7. Tablet.
newpage();title('6. Responsive design: tablet')
image('04-homepage-tablet.png','Figure 7. Tablet catalog at the required 768 × 1024 viewport.',width=330,maxheight=440)
para('At the md breakpoint (768px), the service cards form two columns. The search field and category controls adapt to the available width. Navigation continues to use the collapsible menu until the lg breakpoint (992px).')
para('At desktop widths the catalog forms three columns and the navigation expands. Local images use constrained widths, object-fit and responsive containers. The detail page changes from two side-by-side columns to a stacked layout on smaller screens.')

# 8. JS.
newpage();title('7. JavaScript interaction')
image('06-javascript-interaction.png','Figure 8. Primary care filter, saved service state and visible confirmation toast.',maxheight=285)
para('The required visible JavaScript interaction is demonstrated by saving General Consultation: its heart button changes state, the saved count updates, and a notification announces the action. Saved selections persist in localStorage and remain available after refresh.')
grid(['Feature','Demonstration'],[
    ['Challenge A: Search','Type preventive to show the matching service; a query without matches shows a resettable empty state.'],
    ['Challenge B: Categories','Choose Primary care to display only General Consultation and Preventive Health Review. Search and category filters combine.'],
    ['Challenge E: Interactive cards','Save or unsave a service, filter saved items, refresh to verify persistence, or open its details.'],
    ['Additional form interaction','Choose a service to update its displayed fee; submit a valid demo request to see a reference and confirmation. The interface explicitly identifies this as a demo.'],
],widths=[149,WIDTH-149])
para('Demonstration steps: open Items / Services; select Primary care; click the General Consultation heart; observe the changed heart, saved count and toast. These actions are implemented in public/js/script.js.')

# 9. Accessibility, checklist, deliverables.
newpage();title('8. Accessibility and completion')
para('Basic accessibility checks cover meaningful page titles, descriptive image alt attributes, a logical h1–h2–h3 hierarchy, meaningful link labels, text/background contrast, visible keyboard focus, a skip link, form labels and non-image text for important information. Toggle states use aria-pressed/aria-expanded; search results and notifications use live regions.')
para('Automated axe-core WCAG A/AA checks were run on the homepage, details and form at 375px and 1366px: six scans completed with zero reported violations. Keyboard navigation and the mobile menu were also exercised. Reduced-motion preferences disable nonessential transitions.')
checklist=[
    'Project folder under htdocs and required folder structure',
    'public/index.php and semantic HTML5 structure',
    'Bootstrap added with a local fallback',
    'Responsive navigation and hero section',
    'Six service cards and responsive 1 / 2 / 3-column grid',
    'Custom CSS with more than three visual customizations',
    'Reusable header and footer components',
    'Item details: name, image, category, description, price, action',
    'Working JavaScript with visible interaction feedback',
    'Mobile, tablet and desktop layouts tested',
    'Meaningful alt text, titles, headings, links and contrast checked',
    'Medical Clinic content replaces generic sample content',
    'All required screenshots captured',
]
grid(['Lab completion checklist','Status'],[[item,'Completed'] for item in checklist],widths=[WIDTH-85,85],small=True)
para('Submission evidence',bold=True)
para('The complete project folder accompanies this report. Required screenshots are in report/screenshots/: 01-project-structure.png; 02-homepage-desktop.png; 03-homepage-mobile.png; 05-item-details.png; 06-javascript-interaction.png. Additional full-page, tablet, navigation and form screenshots are included. Browser verification results are stored in report/verification.json.')

def page_decoration(canvas, document):
    canvas.setFillColor(colors.white)
    canvas.rect(0,0,A4[0],A4[1],stroke=0,fill=1)
    if document.page == 1:
        canvas.setStrokeColor(colors.HexColor(TEAL));canvas.setLineWidth(1)
        canvas.rect(32,32,A4[0]-64,A4[1]-64)
    else:
        canvas.setStrokeColor(colors.HexColor('#D8E1D2'));canvas.setLineWidth(.5)
        canvas.line(52.5,43,A4[0]-52.5,43)
        canvas.setFillColor(colors.HexColor('#61726A'));canvas.setFont('ReportArial',8)
        canvas.drawString(52.5,30,'Huỳnh Đức Phát · 2452940 | LAB 1.3')
        canvas.drawRightString(A4[0]-52.5,30,str(document.page))

pdf=SimpleDocTemplate(str(OUT/(STEM+'.pdf')),pagesize=A4,rightMargin=52.5,leftMargin=52.5,topMargin=45,bottomMargin=55,title='LAB 1.3 – Medical Clinic Frontend Foundation',author='Huỳnh Đức Phát (2452940)')
pdf.build(story,onFirstPage=page_decoration,onLaterPages=page_decoration)
doc.core_properties.title='LAB 1.3 – Website Layout & Frontend Foundation'
doc.core_properties.author='Huỳnh Đức Phát (2452940)'
doc.core_properties.subject='Medical Clinic — Frontend Structure and Responsive Layout'
doc.save(OUT/(STEM+'.docx'))
print('Created:',OUT/(STEM+'.pdf'))
print('Created:',OUT/(STEM+'.docx'))
