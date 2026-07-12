/**
 * pdfGenerator.js — PDF Document Generator for GoCodeMe MCP Server
 *
 * Creates professional PDF documents from structured content.
 * Styled with colored heading bars, Times Roman body font, and clean layout
 * matching the quality of the original PHP-based PDF generator.
 *
 * Uses the `pdfkit` npm package to produce valid multi-page PDF files.
 */

import PDFDocument from 'pdfkit';
import { writeFileSync, mkdirSync } from 'node:fs';
import { join, dirname } from 'node:path';

// ── Theme / Color constants ──────────────────────────────────────────────────
const COLORS = {
  primary:    '#1F497D',
  primaryRGB: [31, 73, 125],
  secondary:  '#6B7280',
  dark:       '#222222',
  light:      '#F3F4F6',
  white:      '#FFFFFF',
  whiteRGB:   [255, 255, 255],
  tableHead:  '#1F497D',
  tableAlt:   '#EBF0F7',
  warnBg:     '#FFF8E1',
  warnBorder: '#C8A415',
  accentLine: '#CCCCCC',
};

// ── Font sizes ───────────────────────────────────────────────────────────────
const FONT = {
  titleSize:    20,
  subtitleSize: 12,
  h2Size:       11,
  h3Size:       11,
  bodySize:     10,
  smallSize:    8,
  footerSize:   7.5,
  bulletSize:   10,
};

// ── Page layout (A4: 595 x 842 pt) ──────────────────────────────────────────
const PAGE_SIZE = [595, 842];
const MARGINS = { top: 60, bottom: 60, left: 60, right: 60 };

// ── Helpers ──────────────────────────────────────────────────────────────────

function ensureSpace(doc, needed = 40) {
  if (doc.y + needed > doc.page.height - MARGINS.bottom) {
    doc.addPage();
  }
}

function cw(doc) {
  return doc.page.width - MARGINS.left - MARGINS.right;
}

/**
 * Draw a full-width colored banner bar with white text.
 */
function drawBanner(doc, text, opts = {}) {
  const {
    height = 24,
    fontSize = FONT.h2Size,
    font = 'Helvetica-Bold',
    bgColor = COLORS.primaryRGB,
    textColor = COLORS.white,
    textOffsetY = 7,
  } = opts;

  const w = cw(doc);
  const x = MARGINS.left;
  const y = doc.y;

  doc.save();
  doc.rect(x, y, w, height).fill(bgColor);
  doc.font(font).fontSize(fontSize).fillColor(textColor);
  doc.text(text, x + 6, y + textOffsetY, { width: w - 12, align: 'left', lineBreak: false });
  doc.restore();
  doc.y = y + height + 8;
}

/**
 * Draw a title banner at the top of page 1.
 */
function drawTitleBanner(doc, title, subtitle, author) {
  const w = cw(doc);
  const x = MARGINS.left;
  const bannerH = subtitle ? 110 : 90;

  doc.save();
  doc.rect(0, 0, doc.page.width, bannerH).fill(COLORS.primaryRGB);

  // Title
  doc.font('Helvetica-Bold').fontSize(FONT.titleSize).fillColor(COLORS.white);
  doc.text(title, x, bannerH - (subtitle ? 72 : 52), { width: w, align: 'left' });

  // Subtitle
  if (subtitle) {
    doc.font('Times-Bold').fontSize(FONT.subtitleSize).fillColor(COLORS.whiteRGB);
    doc.text(subtitle, x, bannerH - 42, { width: w, align: 'left' });
  }

  // Date line
  const dateStr = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
  const dateLine = author ? `${author}  \u2022  ${dateStr}` : `Date: ${dateStr}`;
  doc.font('Helvetica').fontSize(FONT.smallSize).fillColor(COLORS.whiteRGB);
  doc.text(dateLine, x, bannerH - 18, { width: w, align: 'left' });

  doc.restore();
  doc.y = bannerH + 16;
}

/**
 * Parse inline **bold** and *italic* and render mixed text (Times Roman body).
 */
function renderRichText(doc, text, opts = {}) {
  const fontSize = opts.fontSize || FONT.bodySize;
  const color = opts.color || COLORS.dark;
  const align = opts.align || 'left';
  const indent = opts.indent || 0;
  const bodyFont = opts.bodyFont || 'Times-Roman';
  const boldFont = opts.boldFont || 'Times-Bold';
  const italicFont = opts.italicFont || 'Times-Italic';

  const parts = text.split(/(\*\*[^*]+\*\*|\*[^*]+\*)/g);
  const textOpts = { continued: false, align, indent };
  if (opts.width) textOpts.width = opts.width;

  let first = true;
  for (let i = 0; i < parts.length; i++) {
    const part = parts[i];
    if (!part) continue;
    const isLast = i === parts.length - 1 || parts.slice(i + 1).every(p => !p);

    let chosenFont = bodyFont;
    let stripped = part;
    if (part.startsWith('**') && part.endsWith('**')) {
      chosenFont = boldFont; stripped = part.slice(2, -2);
    } else if (part.startsWith('*') && part.endsWith('*')) {
      chosenFont = italicFont; stripped = part.slice(1, -1);
    }

    doc.font(chosenFont).fontSize(fontSize).fillColor(color);
    if (first) {
      doc.text(stripped, { ...textOpts, continued: !isLast });
      first = false;
    } else {
      doc.text(stripped, { continued: !isLast });
    }
  }

  if (first) {
    doc.font(bodyFont).fontSize(fontSize).fillColor(color).text('', textOpts);
  }
}

/**
 * Draw a horizontal rule.
 */
function drawHR(doc) {
  ensureSpace(doc, 20);
  const y = doc.y + 4;
  doc.strokeColor(COLORS.accentLine).lineWidth(0.5)
     .moveTo(MARGINS.left, y)
     .lineTo(doc.page.width - MARGINS.right, y)
     .stroke();
  doc.y = y + 10;
}

/**
 * Draw a disclaimer / notice box (yellow background, gold border).
 */
function drawNoticeBox(doc, text) {
  const w = cw(doc);
  const x = MARGINS.left;
  const pad = 8;
  const tw = w - pad * 2;

  doc.font('Times-Roman').fontSize(FONT.bodySize);
  const th = doc.heightOfString(text, { width: tw }) + pad * 2;

  ensureSpace(doc, th + 12);
  const y = doc.y;

  doc.save();
  doc.rect(x, y, w, th).fill(COLORS.warnBg);
  doc.rect(x, y, w, th).lineWidth(1).strokeColor(COLORS.warnBorder).stroke();
  doc.font('Times-Italic').fontSize(FONT.bodySize).fillColor(COLORS.dark);
  doc.text(text, x + pad, y + pad, { width: tw });
  doc.restore();
  doc.y = y + th + 10;
}

/**
 * Draw a table. First row = header.
 */
function drawTable(doc, rows) {
  if (!rows || rows.length === 0) return;

  const pageWidth = cw(doc);
  const colCount = rows[0].length;
  const colWidth = pageWidth / colCount;
  const cellPad = 5;
  const rowH = 22;
  const sx = MARGINS.left;

  const needed = (rows.length + 1) * rowH + 16;
  if (doc.y + needed > doc.page.height - MARGINS.bottom - 40) doc.addPage();

  let y = doc.y;

  // Header
  doc.save();
  doc.rect(sx, y, pageWidth, rowH).fill(COLORS.tableHead);
  for (let c = 0; c < colCount; c++) {
    doc.font('Helvetica-Bold').fontSize(FONT.smallSize).fillColor(COLORS.white);
    doc.text(String(rows[0][c]), sx + c * colWidth + cellPad, y + 6, { width: colWidth - cellPad * 2, align: 'left' });
  }
  doc.restore();
  y += rowH;

  // Data
  for (let r = 1; r < rows.length; r++) {
    if (y + rowH > doc.page.height - MARGINS.bottom - 20) { doc.addPage(); y = doc.y; }
    const row = rows[r];
    doc.save();
    if ((r - 1) % 2 === 0) doc.rect(sx, y, pageWidth, rowH).fill(COLORS.tableAlt);
    for (let c = 0; c < colCount; c++) {
      doc.font('Times-Roman').fontSize(FONT.smallSize).fillColor(COLORS.dark);
      doc.text(String(row[c] || ''), sx + c * colWidth + cellPad, y + 6, { width: colWidth - cellPad * 2, align: 'left' });
    }
    doc.strokeColor('#D0D0D0').lineWidth(0.25)
       .moveTo(sx, y + rowH).lineTo(sx + pageWidth, y + rowH).stroke();
    doc.restore();
    y += rowH;
  }

  doc.y = y + 10;
}

/**
 * Parse markdown-like content and render to PDF.
 */
function renderContent(doc, content) {
  const lines = content.split('\n');
  let i = 0;
  let bulletNumber = 0;

  while (i < lines.length) {
    const line = lines[i];

    if (!line.trim()) { doc.moveDown(0.25); i++; continue; }

    ensureSpace(doc, 30);

    // ── ## Section headings → blue bar with white text
    if (line.startsWith('### ')) {
      ensureSpace(doc, 40); doc.moveDown(0.3);
      drawBanner(doc, line.slice(4).trim(), { height: 22, fontSize: FONT.h3Size, bgColor: [80, 120, 170] });
      i++; bulletNumber = 0; continue;
    }
    if (line.startsWith('## ')) {
      ensureSpace(doc, 40); doc.moveDown(0.4);
      drawBanner(doc, line.slice(3).trim());
      i++; bulletNumber = 0; continue;
    }
    if (line.startsWith('# ')) {
      ensureSpace(doc, 50); doc.moveDown(0.5);
      drawBanner(doc, line.slice(2).trim(), { height: 28, fontSize: 13, textOffsetY: 9 });
      i++; bulletNumber = 0; continue;
    }

    // ── Horizontal rule
    if (/^---+$/.test(line.trim())) { drawHR(doc); i++; bulletNumber = 0; continue; }

    // ── Page break
    if (line.trim() === '{{pagebreak}}') { doc.addPage(); i++; bulletNumber = 0; continue; }

    // ── {{notice}} block → yellow box
    if (line.trim() === '{{notice}}') {
      i++;
      let noticeText = '';
      while (i < lines.length && lines[i].trim() !== '{{/notice}}') {
        noticeText += (noticeText ? '\n' : '') + lines[i]; i++;
      }
      if (i < lines.length) i++;
      if (noticeText.trim()) drawNoticeBox(doc, noticeText.trim());
      bulletNumber = 0; continue;
    }

    // ── Table
    if (line.trim().startsWith('|')) {
      const tl = [];
      while (i < lines.length && lines[i].trim().startsWith('|')) {
        const row = lines[i].trim();
        if (!/^\|[\s-:|]+\|$/.test(row)) {
          tl.push(row.split('|').filter(Boolean).map(c => c.trim()));
        }
        i++;
      }
      if (tl.length > 0) drawTable(doc, tl);
      bulletNumber = 0; continue;
    }

    // ── Bullet points
    if (/^\s*[-*]\s/.test(line)) {
      ensureSpace(doc, 24);
      const indent = line.match(/^(\s*)/)[1].length;
      const level = Math.min(Math.floor(indent / 2), 3);
      const text = line.replace(/^\s*[-*]\s+/, '');
      const bc = level === 0 ? '\u2022' : level === 1 ? '\u25E6' : '\u25AA';
      const px = 18 + level * 14;

      doc.font('Times-Roman').fontSize(FONT.bulletSize).fillColor(COLORS.dark);
      doc.text(bc, MARGINS.left + px - 10, doc.y, { continued: false, width: 10 });
      doc.moveUp();
      renderRichText(doc, text, { indent: px, fontSize: FONT.bulletSize });
      i++; bulletNumber = 0; continue;
    }

    // ── Numbered list
    if (/^\s*\d+\.\s/.test(line)) {
      ensureSpace(doc, 24);
      bulletNumber++;
      const text = line.replace(/^\s*\d+\.\s+/, '');
      const px = 18;

      doc.font('Times-Roman').fontSize(FONT.bulletSize).fillColor(COLORS.dark);
      doc.text(`${bulletNumber}.`, MARGINS.left + px - 14, doc.y, { continued: false, width: 14, align: 'right' });
      doc.moveUp();
      renderRichText(doc, text, { indent: px, fontSize: FONT.bulletSize });
      i++; continue;
    }

    // ── Regular paragraph
    bulletNumber = 0;
    renderRichText(doc, line.trim());
    doc.moveDown(0.15);
    i++;
  }
}


// ── Main export class ────────────────────────────────────────────────────────

export class PdfGenerator {
  constructor(homeDir, daClient = null) {
    this.homeDir = homeDir;
    this.daClient = daClient;
  }

  async create(opts) {
    const {
      title, content, author = 'GoCodeMe', subtitle,
      footer = 'Generated by GoCodeMe', domain, filename, path: subPath = '',
    } = opts;

    if (!title || !content || !domain || !filename) {
      return { success: false, path: '', message: 'Missing required fields: title, content, domain, and filename are required.' };
    }

    const finalFilename = filename.endsWith('.pdf') ? filename : `${filename}.pdf`;

    try {
      const buffer = await this._generatePdf({ title, content, author, subtitle, footer });

      const remotePath = subPath
        ? `domains/${domain}/public_html/${subPath}/${finalFilename}`
        : `domains/${domain}/public_html/${finalFilename}`;

      if (this.daClient) {
        await this.daClient.writeFile(remotePath, buffer);
      } else {
        const fullPath = join(this.homeDir, remotePath);
        mkdirSync(dirname(fullPath), { recursive: true });
        writeFileSync(fullPath, buffer);
      }

      const url = `https://${domain}/${subPath ? subPath + '/' : ''}${finalFilename}`;
      return { success: true, path: remotePath, url, message: `PDF created: ${finalFilename} — ${url}` };
    } catch (err) {
      return { success: false, path: '', message: `Failed to generate PDF: ${err.message}` };
    }
  }

  _generatePdf({ title, content, author, subtitle, footer }) {
    return new Promise((resolve, reject) => {
      try {
        const doc = new PDFDocument({
          size: PAGE_SIZE,
          margins: MARGINS,
          bufferPages: true,
          info: { Title: title, Author: author || 'GoCodeMe', Subject: subtitle || title, Creator: 'GoCodeMe AI Assistant — Alfred' },
        });

        const chunks = [];
        doc.on('data', (chunk) => chunks.push(chunk));
        doc.on('end', () => resolve(Buffer.concat(chunks)));
        doc.on('error', reject);

        // ── Title banner ─────────────────────────────────────────────────
        drawTitleBanner(doc, title, subtitle, author);

        // ── Body content ─────────────────────────────────────────────────
        renderContent(doc, content);

        // ── Post-render: headers & footers on every page ─────────────────
        const range = doc.bufferedPageRange();
        const pageCount = range.count;
        for (let pg = 0; pg < pageCount; pg++) {
          doc.switchToPage(pg);

          // Header (skip page 1 — has banner)
          if (pg > 0) {
            doc.save();
            doc.font('Helvetica-Oblique').fontSize(FONT.footerSize).fillColor(COLORS.secondary);
            doc.text(title, MARGINS.left, 24, { width: cw(doc), align: 'right' });
            doc.restore();
          }

          // Footer
          const fy = doc.page.height - 44;
          doc.save();
          doc.strokeColor(COLORS.accentLine).lineWidth(0.5)
             .moveTo(MARGINS.left, fy).lineTo(doc.page.width - MARGINS.right, fy).stroke();
          doc.font('Helvetica').fontSize(FONT.footerSize).fillColor(COLORS.secondary);
          doc.text(footer, MARGINS.left, fy + 6, { width: cw(doc), align: 'left' });
          doc.text(`Page ${pg + 1} of ${pageCount}`, MARGINS.left, fy + 6, { width: cw(doc), align: 'right' });
          doc.restore();
        }

        doc.end();
      } catch (err) {
        reject(err);
      }
    });
  }
}
