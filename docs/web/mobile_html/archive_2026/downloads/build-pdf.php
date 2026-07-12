<?php
$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Georgia, serif; margin: 40px; color: #1a1a1a; }
  .header { background: #1a3a5c; color: white; padding: 30px; margin: -40px -40px 30px -40px; }
  .header h1 { font-size: 26px; margin: 0 0 8px 0; }
  .header h2 { font-size: 15px; font-weight: normal; margin: 0; color: #b8cfe8; }
  .header .date { font-size: 11px; color: #7aaad4; margin-top: 8px; }
  .intro { background: #f0f5fb; border-left: 4px solid #1a3a5c; padding: 14px 18px; margin-bottom: 24px; font-size: 13px; line-height: 1.7; }
  .section { margin-bottom: 22px; }
  .section-heading { background: #1a3a5c; color: white; font-family: Arial, sans-serif; font-size: 12px; font-weight: bold; padding: 8px 14px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
  .section p { font-size: 13px; line-height: 1.8; margin-bottom: 10px; text-align: justify; }
  ul { margin: 10px 0 10px 20px; }
  ul li { font-size: 13px; line-height: 1.8; margin-bottom: 4px; color: #2c2c2c; }
  .notice { background: #fffbee; border-left: 4px solid #e07b00; padding: 12px 16px; font-size: 13px; line-height: 1.8; }
  .disclaimer { background: #f9f9f9; border-top: 3px solid #1a3a5c; padding: 12px 16px; margin-top: 30px; font-size: 11px; color: #555; font-style: italic; }
  .footer { text-align: center; font-size: 10px; color: #aaa; margin-top: 30px; border-top: 1px solid #ddd; padding-top: 10px; font-family: Arial, sans-serif; }
</style>
</head>
<body>
<div class="header">
  <h1>Settlement and Land Acts in Quebec</h1>
  <h2>A Civil Law Perspective for the Settlor</h2>
  <div class="date">Generated: ' . date('F j, Y') . '</div>
</div>

<div class="intro">
  The Settlement and Land Acts in Quebec (often referred to historically as "settlement acts" in common-law contexts) don\'t operate the same way as in other Canadian provinces. Quebec follows the <strong>Civil Code of Québec (CCQ)</strong> rather than English common law. What people usually mean by "settlement" in Quebec law relates to property transfers, trusts, usufructs, substitutions (a civil law concept similar to a reversionary interest), or estate planning arrangements rather than a distinct "settled land act" statute like in older British law.
</div>

<div class="section">
  <div class="section-heading">The Role of the Settlor (Constituent) in Quebec Civil Law</div>
  <p>In Quebec civil law, the closest equivalent to a "settlor" is the <strong>constituent of a trust (constituant)</strong>. Under the Civil Code, a trust creates a <strong>separate patrimony by appropriation</strong> — meaning the property is no longer owned by the settlor personally, but is administered by trustees for beneficiaries.</p>
  <p>The settlor generally cannot simply "take back" the property unless the trust deed specifically reserves that right. The concept of "reversion" exists in civil law more in the form of a <strong>substitution</strong> (where property passes from one beneficiary to another upon a condition or term) or through conditions written into the act of donation.</p>
</div>

<div class="section">
  <div class="section-heading">Termination of a Trust</div>
  <p>Termination depends entirely on the type of legal arrangement. A trust can end when:</p>
  <ul>
    <li>Its <strong>term expires</strong>;</li>
    <li>Its <strong>purpose is fulfilled</strong> or becomes impossible;</li>
    <li><strong>All beneficiaries consent</strong> (in certain conditions); or</li>
    <li>A <strong>court orders termination</strong>.</li>
  </ul>
  <p>If the trust instrument includes a clause allowing <strong>revocation by the settlor</strong>, then termination may occur without court involvement, provided all legal formalities are respected (often requiring notarized documentation).</p>
</div>

<div class="section">
  <div class="section-heading">Is a Judge Required?</div>
  <p>Whether a judge is required depends on the situation. If the act clearly allows revocation and all parties agree, it may be handled through a <strong>notary</strong> without going before a judge. However, if there is disagreement between beneficiaries, ambiguity in the deed, incapacity of a party, or if the arrangement involves substitution or <strong>protected beneficiaries</strong> (like minors), court authorization may be necessary. In Quebec, changes to patrimonial rights that affect third parties or protected persons often require judicial oversight.</p>
</div>

<div class="section">
  <div class="section-heading">Important Notice</div>
  <div class="notice">
    If you\'re dealing with a specific instrument (for example, a notarial trust, donation with conditions, or substitution clause), the <strong>exact wording controls</strong>. In Quebec civil law, the document itself is central. Before attempting termination, it\'s essential to <strong>review the original deed carefully</strong> — preferably with a notary or lawyer — because acting outside the terms of the Civil Code could make the termination <strong>invalid</strong>.
  </div>
</div>

<div class="disclaimer">
  ⚠️ <strong>Legal Disclaimer:</strong> This document is for informational purposes only and does not constitute legal advice. Always consult a qualified notary or lawyer licensed in Quebec before taking any legal action regarding trusts, settlements, or patrimonial arrangements.
</div>

<div class="footer">
  Quebec Civil Law Reference Document &nbsp;|&nbsp; Generated by Alfred · GoCodeMe.com &nbsp;|&nbsp; Page 1 of 1
</div>
</body>
</html>';

// Write HTML to temp file
$tmpHtml = '/tmp/quebec-settlement.html';
$pdfOut  = __DIR__ . '/quebec-settlement-law.pdf';
file_put_contents($tmpHtml, $html);

// Run wkhtmltopdf
$cmd = "/usr/bin/wkhtmltopdf --page-size A4 --margin-top 0 --margin-bottom 10mm --margin-left 0 --margin-right 0 --encoding utf-8 --quiet $tmpHtml $pdfOut 2>&1";
$output = shell_exec($cmd);

if (file_exists($pdfOut) && filesize($pdfOut) > 0) {
    echo "✅ PDF created successfully! <a href='/downloads/quebec-settlement-law.pdf'>Download PDF</a>";
} else {
    echo "❌ Failed. Output: " . htmlspecialchars($output);
}
?>
