const { marked } = require('marked');
const fs = require('fs');
const path = require('path');

const md = fs.readFileSync(path.resolve(__dirname, '../DOCUMENTATION.md'), 'utf-8');

const html = marked.parse(md);

const docxHtml = `<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:w="urn:schemas-microsoft-com:office:word"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<!--[if gte mso 9]>
<xml>
<w:WordDocument>
<w:View>Print</w:View>
<w:Zoom>100</w:Zoom>
</w:WordDocument>
</xml>
<![endif]-->
<title>Global Supply Chain Risk Intelligence Platform - Dokumentasi</title>
<style>
    @page { size: A4; margin: 2.54cm 2.54cm; mso-page-orientation: portrait; }
    body { font-family: 'Calibri', 'Segoe UI', Arial, sans-serif; font-size: 11pt; line-height: 1.5; color: #1a1a1a; }
    h1 { font-size: 20pt; color: #2a1a5e; border-bottom: 3px solid #7c3aed; padding-bottom: 8px; margin-top: 28px; margin-bottom: 12px; }
    h2 { font-size: 16pt; color: #3a2a6e; border-bottom: 2px solid #06b6d4; padding-bottom: 4px; margin-top: 24px; margin-bottom: 10px; }
    h3 { font-size: 13pt; color: #4a3a7e; margin-top: 18px; margin-bottom: 8px; }
    h4 { font-size: 11pt; color: #5a4a8e; margin-top: 14px; margin-bottom: 6px; }
    p { margin: 6px 0; }
    table { border-collapse: collapse; width: 100%; margin: 12px 0; font-size: 10pt; }
    th, td { border: 1px solid #bbb; padding: 6px 10px; text-align: left; vertical-align: top; }
    th { background: #f0edf8; font-weight: 600; }
    tr:nth-child(even) { background: #f9f8fc; }
    code { background: #f4f0ff; padding: 1px 5px; font-family: 'Consolas', 'Courier New', monospace; font-size: 9.5pt; }
    pre { background: #1a1a2e; color: #e0e0e0; padding: 12px 16px; font-family: 'Consolas', 'Courier New', monospace; font-size: 9pt; }
    pre code { background: none; padding: 0; color: inherit; }
    ul, ol { margin: 8px 0; padding-left: 24px; }
    li { margin: 3px 0; }
    hr { border: none; border-top: 2px solid #ddd; margin: 24px 0; }
    .cover { text-align: center; padding: 80px 0 40px 0; }
    .cover h1 { font-size: 28pt; border: none; color: #2a1a5e; margin-bottom: 16px; }
    .cover .subtitle { font-size: 16pt; color: #7c3aed; margin: 16px 0; }
    .cover .meta { font-size: 11pt; color: #666; margin-top: 60px; }
    .cover .meta p { margin: 4px 0; }
    .diagram-placeholder { background: #f8f4ff; border: 1px dashed #7c3aed; padding: 16px 20px; margin: 12px 0; border-radius: 4px; color: #555; font-style: italic; }
</style>
</head>
<body>

<div class="cover">
    <h1>Global Supply Chain Risk<br>Intelligence Platform</h1>
    <div class="subtitle">Platform Monitoring Risiko Rantai Pasok Global</div>
    <hr style="width: 60%; margin: 30px auto;">
    <div class="meta">
        <p><strong>Versi Dokumentasi:</strong> 1.0</p>
        <p><strong>Tanggal:</strong> Juli 2026</p>
        <p><strong>Dibuat dengan:</strong> Laravel 12 · Bootstrap 5 · Chart.js · Leaflet.js · MySQL · Vite 7</p>
    </div>
    <hr style="width: 60%; margin: 30px auto;">
    <p style="color: #999; font-size: 10pt;">Global Supply Chain Risk Intelligence Platform</p>
</div>

${html
    .replace(/```mermaid\n[\s\S]*?\n```/g, '<div class="diagram-placeholder">📊 Diagram alir — buka file DOCUMENTATION.md untuk melihat diagram interaktif (Mermaid).</div>')
    .replace(/```\n/g, '```\n')
}

</body>
</html>`;

const outputPath = path.resolve(__dirname, '../DOCUMENTATION.doc');
fs.writeFileSync(outputPath, docxHtml, 'utf-8');
console.log('✅ DOCUMENTATION.doc berhasil dibuat di: ' + outputPath);
