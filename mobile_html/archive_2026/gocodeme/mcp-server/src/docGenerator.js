/**
 * docGenerator.js — Word Document (.docx) Generator for GoCodeMe MCP Server
 *
 * Creates professional Word documents from structured content.
 * Alfred can generate invoices, proposals, reports, documentation, etc.
 * and save them directly to the customer's hosting account.
 *
 * Uses the `docx` npm package to produce valid .docx files that open
 * natively in Microsoft Word, Google Docs, and LibreOffice.
 */

import {
  Document, Packer, Paragraph, TextRun, HeadingLevel,
  Table, TableRow, TableCell, WidthType, AlignmentType,
  BorderStyle, PageBreak, Header, Footer, ImageRun,
  ExternalHyperlink, TabStopType, TabStopPosition,
  ShadingType, convertInchesToTwip, LevelFormat,
} from 'docx';
import { writeFileSync, readFileSync, existsSync, mkdirSync } from 'node:fs';
import { join, dirname } from 'node:path';

// ── Theme / Color constants ──────────────────────────────────────────────────
const COLORS = {
  primary:   '2563EB', // Blue
  secondary: '6B7280', // Gray
  dark:      '1F2937', // Almost black
  light:     'F3F4F6', // Light gray
  accent:    '059669', // Green
  danger:    'DC2626', // Red
  white:     'FFFFFF',
};

// ── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Create a styled heading paragraph.
 */
function heading(text, level = HeadingLevel.HEADING_1, opts = {}) {
  return new Paragraph({
    heading: level,
    spacing: { before: level === HeadingLevel.HEADING_1 ? 400 : 240, after: 120 },
    children: [
      new TextRun({
        text,
        bold: true,
        color: opts.color || COLORS.dark,
        size: level === HeadingLevel.HEADING_1 ? 32 : level === HeadingLevel.HEADING_2 ? 26 : 22,
        font: 'Calibri',
      }),
    ],
    ...opts,
  });
}

/**
 * Create a body paragraph with optional formatting.
 */
function para(text, opts = {}) {
  const runs = [];
  // Support simple **bold** and *italic* markdown-like syntax
  const parts = text.split(/(\*\*[^*]+\*\*|\*[^*]+\*)/g);
  for (const part of parts) {
    if (part.startsWith('**') && part.endsWith('**')) {
      runs.push(new TextRun({ text: part.slice(2, -2), bold: true, font: 'Calibri', size: opts.size || 22, color: opts.color }));
    } else if (part.startsWith('*') && part.endsWith('*')) {
      runs.push(new TextRun({ text: part.slice(1, -1), italics: true, font: 'Calibri', size: opts.size || 22, color: opts.color }));
    } else if (part) {
      runs.push(new TextRun({ text: part, font: 'Calibri', size: opts.size || 22, color: opts.color, ...opts.runOpts }));
    }
  }
  return new Paragraph({
    spacing: { after: 120 },
    children: runs,
    alignment: opts.alignment,
    indent: opts.indent,
    ...opts.paraOpts,
  });
}

/**
 * Create a bullet point.
 */
function bullet(text, level = 0) {
  return new Paragraph({
    bullet: { level },
    spacing: { after: 80 },
    children: [
      new TextRun({ text, font: 'Calibri', size: 22 }),
    ],
  });
}

/**
 * Create a horizontal rule (thin line).
 */
function hr() {
  return new Paragraph({
    spacing: { before: 200, after: 200 },
    border: {
      bottom: { style: BorderStyle.SINGLE, size: 1, color: COLORS.secondary },
    },
    children: [],
  });
}

/**
 * Create a table from rows of data.
 * First row is treated as header.
 */
function table(rows, opts = {}) {
  if (!rows || rows.length === 0) return new Paragraph({ children: [] });

  const headerRow = rows[0];
  const dataRows = rows.slice(1);
  const colCount = headerRow.length;

  const tableRows = [];

  // Header row
  tableRows.push(
    new TableRow({
      tableHeader: true,
      children: headerRow.map(cell =>
        new TableCell({
          shading: { type: ShadingType.SOLID, color: COLORS.primary },
          children: [
            new Paragraph({
              alignment: AlignmentType.CENTER,
              children: [
                new TextRun({
                  text: String(cell),
                  bold: true,
                  color: COLORS.white,
                  font: 'Calibri',
                  size: 20,
                }),
              ],
            }),
          ],
          verticalAlign: 'center',
        })
      ),
    })
  );

  // Data rows
  for (let i = 0; i < dataRows.length; i++) {
    const row = dataRows[i];
    const isEven = i % 2 === 0;
    tableRows.push(
      new TableRow({
        children: row.map((cell, colIdx) =>
          new TableCell({
            shading: isEven ? { type: ShadingType.SOLID, color: COLORS.light } : undefined,
            children: [
              new Paragraph({
                alignment: opts.alignments?.[colIdx] || AlignmentType.LEFT,
                children: [
                  new TextRun({
                    text: String(cell),
                    font: 'Calibri',
                    size: 20,
                  }),
                ],
              }),
            ],
            verticalAlign: 'center',
          })
        ),
      })
    );
  }

  return new Table({
    rows: tableRows,
    width: { size: 100, type: WidthType.PERCENTAGE },
  });
}

/**
 * Parse simple markdown-like content into docx elements.
 * Supports: # headings, - bullets, | tables |, --- hr, paragraphs.
 */
function parseMarkdownContent(content) {
  const lines = content.split('\n');
  const elements = [];
  let i = 0;

  while (i < lines.length) {
    const line = lines[i];

    // Blank line — skip
    if (!line.trim()) {
      i++;
      continue;
    }

    // Headings
    if (line.startsWith('### '))      { elements.push(heading(line.slice(4).trim(), HeadingLevel.HEADING_3)); i++; continue; }
    if (line.startsWith('## '))       { elements.push(heading(line.slice(3).trim(), HeadingLevel.HEADING_2)); i++; continue; }
    if (line.startsWith('# '))        { elements.push(heading(line.slice(2).trim(), HeadingLevel.HEADING_1)); i++; continue; }

    // Horizontal rule
    if (/^---+$/.test(line.trim()))   { elements.push(hr()); i++; continue; }

    // Page break
    if (line.trim() === '{{pagebreak}}') {
      elements.push(new Paragraph({ children: [new PageBreak()] }));
      i++;
      continue;
    }

    // Table (collect consecutive lines starting with |)
    if (line.trim().startsWith('|')) {
      const tableLines = [];
      while (i < lines.length && lines[i].trim().startsWith('|')) {
        const row = lines[i].trim();
        // Skip separator rows like |---|---|
        if (!/^\|[\s-:|]+\|$/.test(row)) {
          const cells = row.split('|').filter(Boolean).map(c => c.trim());
          tableLines.push(cells);
        }
        i++;
      }
      if (tableLines.length > 0) {
        elements.push(table(tableLines));
      }
      continue;
    }

    // Bullet points
    if (/^\s*[-*]\s/.test(line)) {
      const indent = line.match(/^(\s*)/)[1].length;
      const level = Math.min(Math.floor(indent / 2), 3);
      const text = line.replace(/^\s*[-*]\s+/, '');
      elements.push(bullet(text, level));
      i++;
      continue;
    }

    // Numbered list
    if (/^\s*\d+\.\s/.test(line)) {
      const text = line.replace(/^\s*\d+\.\s+/, '');
      elements.push(para(text, { paraOpts: { numbering: { reference: 'numberedList', level: 0 } } }));
      i++;
      continue;
    }

    // Regular paragraph
    elements.push(para(line.trim()));
    i++;
  }

  return elements;
}

// ── Main export class ────────────────────────────────────────────────────────

export class DocGenerator {
  /**
   * @param {string} homeDir — User home directory path
   * @param {object} [daClient] — DirectAdmin client for uploading to DA
   */
  constructor(homeDir, daClient = null) {
    this.homeDir = homeDir;
    this.daClient = daClient;
  }

  /**
   * Create a Word document from structured content.
   *
   * @param {object} opts
   * @param {string} opts.title         — Document title (shown in header / first page)
   * @param {string} opts.content       — Markdown-like content (# headings, - bullets, | tables |, etc.)
   * @param {string} [opts.author]      — Author name
   * @param {string} [opts.subtitle]    — Subtitle under title
   * @param {string} [opts.footer]      — Footer text (default: "Generated by GoCodeMe")
   * @param {string} opts.domain        — Domain to save the file under
   * @param {string} opts.filename      — Output filename (e.g. "report.docx")
   * @param {string} [opts.path]        — Subdirectory path within public_html (default: root)
   * @returns {Promise<{ success: boolean, path: string, url?: string, message: string }>}
   */
  async create(opts) {
    const {
      title,
      content,
      author = 'GoCodeMe',
      subtitle,
      footer = 'Generated by GoCodeMe',
      domain,
      filename,
      path: subPath = '',
    } = opts;

    if (!title || !content || !domain || !filename) {
      return {
        success: false,
        path: '',
        message: 'Missing required fields: title, content, domain, and filename are required.',
      };
    }

    // Ensure filename ends with .docx
    const finalFilename = filename.endsWith('.docx') ? filename : `${filename}.docx`;

    try {
      // Parse the content into docx elements
      const bodyElements = parseMarkdownContent(content);

      // Build the title section
      const titleSection = [
        new Paragraph({
          alignment: AlignmentType.CENTER,
          spacing: { after: 100 },
          children: [
            new TextRun({
              text: title,
              bold: true,
              size: 48,
              color: COLORS.primary,
              font: 'Calibri',
            }),
          ],
        }),
      ];

      if (subtitle) {
        titleSection.push(
          new Paragraph({
            alignment: AlignmentType.CENTER,
            spacing: { after: 100 },
            children: [
              new TextRun({
                text: subtitle,
                size: 28,
                color: COLORS.secondary,
                font: 'Calibri',
                italics: true,
              }),
            ],
          })
        );
      }

      if (author) {
        titleSection.push(
          new Paragraph({
            alignment: AlignmentType.CENTER,
            spacing: { after: 60 },
            children: [
              new TextRun({
                text: `By ${author}`,
                size: 22,
                color: COLORS.secondary,
                font: 'Calibri',
              }),
            ],
          })
        );
      }

      // Date
      titleSection.push(
        new Paragraph({
          alignment: AlignmentType.CENTER,
          spacing: { after: 300 },
          children: [
            new TextRun({
              text: new Date().toLocaleDateString('en-US', {
                year: 'numeric', month: 'long', day: 'numeric',
              }),
              size: 20,
              color: COLORS.secondary,
              font: 'Calibri',
            }),
          ],
        })
      );

      titleSection.push(hr());

      // Create the document
      const doc = new Document({
        creator: author,
        title: title,
        description: subtitle || title,
        numbering: {
          config: [{
            reference: 'numberedList',
            levels: [{
              level: 0,
              format: LevelFormat.DECIMAL,
              text: '%1.',
              alignment: AlignmentType.LEFT,
              style: { paragraph: { indent: { left: convertInchesToTwip(0.5), hanging: convertInchesToTwip(0.25) } } },
            }],
          }],
        },
        sections: [{
          properties: {
            page: {
              margin: {
                top: convertInchesToTwip(1),
                bottom: convertInchesToTwip(1),
                left: convertInchesToTwip(1),
                right: convertInchesToTwip(1),
              },
            },
          },
          headers: {
            default: new Header({
              children: [
                new Paragraph({
                  alignment: AlignmentType.RIGHT,
                  children: [
                    new TextRun({
                      text: title,
                      italics: true,
                      size: 16,
                      color: COLORS.secondary,
                      font: 'Calibri',
                    }),
                  ],
                }),
              ],
            }),
          },
          footers: {
            default: new Footer({
              children: [
                new Paragraph({
                  alignment: AlignmentType.CENTER,
                  children: [
                    new TextRun({
                      text: footer,
                      size: 16,
                      color: COLORS.secondary,
                      font: 'Calibri',
                    }),
                  ],
                }),
              ],
            }),
          },
          children: [
            ...titleSection,
            ...bodyElements,
          ],
        }],
      });

      // Generate the .docx buffer
      const buffer = await Packer.toBuffer(doc);

      // Build the remote path
      const remotePath = subPath
        ? `domains/${domain}/public_html/${subPath}/${finalFilename}`
        : `domains/${domain}/public_html/${finalFilename}`;

      // Upload via DirectAdmin API
      if (this.daClient) {
        // daClient.writeFile accepts Buffer directly
        await this.daClient.writeFile(remotePath, buffer);
      } else {
        // Local fallback — write directly (for testing)
        const fullPath = join(this.homeDir, remotePath);
        mkdirSync(dirname(fullPath), { recursive: true });
        writeFileSync(fullPath, buffer);
      }

      const url = `https://${domain}/${subPath ? subPath + '/' : ''}${finalFilename}`;

      return {
        success: true,
        path: remotePath,
        url,
        message: `✅ Word document created: **${finalFilename}**\n📄 Download: ${url}`,
      };
    } catch (err) {
      return {
        success: false,
        path: '',
        message: `Failed to generate document: ${err.message}`,
      };
    }
  }
}
