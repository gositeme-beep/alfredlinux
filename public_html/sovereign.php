<?php
// sovereign.php — Sovereign Domain Registry public surface.
//
// Routes (single page, action-driven):
//   GET  /sovereign.php                       → claim form
//   POST /sovereign.php?action=challenge      → returns DNS TXT instructions
//   POST /sovereign.php?action=seal           → verifies DNS, writes binding, shows certificate
//   GET  /sovereign.php?action=directory      → public directory of sealed sovereigns
//   GET  /sovereign.php?action=lookup&domain=…→ JSON verification of a single domain
//
// "And he had a name written, that no man knew, but he himself."
//                                          — Revelation 19:12 (AKJV)

declare(strict_types=1);
require_once __DIR__ . '/includes/sovereign.php';

$action = strtolower((string)($_GET['action'] ?? $_POST['action'] ?? ''));

/* ────── JSON: lookup ────── */
if ($action === 'lookup') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: public, max-age=60');
    $domain = (string)($_GET['domain'] ?? '');
    $result = sov_lookup_domain($domain);
    echo json_encode($result ?? ['sealed'=>false,'domain'=>$domain], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

/* ────── HTML chrome helper ────── */
function sov_chrome(string $title, string $body): void {
    echo '<!doctype html><meta charset=utf-8><title>' . htmlspecialchars($title) . ' — Alfred Linux</title>';
    echo '<body style="font-family:Georgia,serif;max-width:760px;margin:3em auto;background:#0d0d12;color:#e8e2c8;padding:2em;line-height:1.7">';
    echo '<div style="text-align:center;font-size:.7rem;letter-spacing:6px;color:#f6c343;opacity:.5;text-transform:uppercase;margin-bottom:1em">'
       . 'Sovereign Domain Registry · Kingdom of God Edition</div>';
    echo '<h1 style="text-align:center;color:#f0e6d0;border-bottom:1px solid rgba(246,195,67,.3);padding-bottom:.6em">' . htmlspecialchars($title) . '</h1>';
    echo $body;
    echo '<hr style="border:none;border-top:1px solid rgba(246,195,67,.2);margin:2em 0">';
    echo '<div style="text-align:center;font-size:.65rem;letter-spacing:4px;color:rgba(246,195,67,.3)">'
       . '<a href="/sovereign" style="color:#f6c343;text-decoration:none">CLAIM</a> &middot; '
       . '<a href="/sovereign.php?action=directory" style="color:#f6c343;text-decoration:none">DIRECTORY</a> &middot; '
       . '<a href="/covenant" style="color:#f6c343;text-decoration:none">COVENANT</a> &middot; '
       . '<a href="/daily-bread" style="color:#f6c343;text-decoration:none">DAILY BREAD</a><br>'
       . '<span style="display:inline-block;margin-top:1em">&#9849; SOLI DEO GLORIA &#9849;</span></div>';
    echo '</body>';
}

/* ────── Public directory ────── */
if ($action === 'directory') {
    $rows = sov_directory();
    $body = '<p style="color:#c8c2a8;text-align:center;font-style:italic">'
          . '"And I saw the dead, small and great, stand before God; and the books were opened&hellip;" '
          . '<br>— Revelation 20:12 (AKJV)</p>';
    if (!$rows) {
        $body .= '<p style="text-align:center;color:#888">No public sovereign bindings yet.</p>';
    } else {
        $body .= '<table style="width:100%;border-collapse:collapse;margin-top:1.5em">';
        $body .= '<thead><tr style="border-bottom:2px solid rgba(246,195,67,.4);color:#f6c343;font-size:.85rem;letter-spacing:1px">'
               . '<th style="text-align:left;padding:.6em">Domain</th>'
               . '<th style="text-align:left;padding:.6em">Sealed</th>'
               . '<th style="text-align:left;padding:.6em">Token</th>'
               . '<th style="text-align:left;padding:.6em">Seal</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $body .= '<tr style="border-bottom:1px solid rgba(246,195,67,.1)">'
                  . '<td style="padding:.5em"><strong style="color:#f0e6d0">' . htmlspecialchars($r['domain']) . '</strong></td>'
                  . '<td style="padding:.5em;color:#c8c2a8">' . htmlspecialchars(substr($r['ts'], 0, 10)) . '</td>'
                  . '<td style="padding:.5em;font-family:monospace;font-size:.8rem;color:#999">' . htmlspecialchars($r['token_head']) . '&hellip;</td>'
                  . '<td style="padding:.5em;font-family:monospace;font-size:.8rem;color:#999">' . htmlspecialchars($r['mac_head']) . '&hellip;</td>'
                  . '</tr>';
        }
        $body .= '</tbody></table>';
        $body .= '<p style="margin-top:2em;font-size:.85rem;color:#888;text-align:center">'
              . count($rows) . ' sovereign domain' . (count($rows) === 1 ? '' : 's') . ' bound to the Kingdom registry.</p>';
    }
    sov_chrome('Kingdom Directory', $body);
    exit;
}

/* ────── Issue challenge ────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'challenge') {
    $token  = strtolower(trim((string)($_POST['token']  ?? '')));
    $domain = sov_normalize_domain((string)($_POST['domain'] ?? ''));

    if (!preg_match('/^[a-f0-9]{64}$/', $token)) { sov_chrome('Sovereign Claim', '<p style="color:#ff8e8e">Invalid token. Paste the full 64-char witness token from your covenant certificate.</p><p><a href="/sovereign" style="color:#f6c343">Try again</a></p>'); exit; }
    if (!sov_valid_domain($domain))              { sov_chrome('Sovereign Claim', '<p style="color:#ff8e8e">Invalid domain.</p><p><a href="/sovereign" style="color:#f6c343">Try again</a></p>'); exit; }
    if (!sov_token_exists($token))               { sov_chrome('Sovereign Claim', '<p style="color:#ff8e8e">Token not found in covenant log. Sign the <a href="/covenant" style="color:#f6c343">covenant</a> first.</p>'); exit; }

    $challenge = sov_challenge_for($token, $domain);
    $body  = '<p>To prove stewardship of <strong style="color:#f0e6d0">' . htmlspecialchars($domain) . '</strong>, '
           . 'add the following <strong>DNS TXT record</strong> at the apex of your domain:</p>';
    $body .= '<div style="background:#000;border:1px solid #f6c343;border-radius:6px;padding:1.2em;margin:1.2em 0;font-family:monospace;font-size:.95rem;word-break:break-all">'
           . '<div style="color:#888;font-size:.8rem;margin-bottom:.5em">Name: <code>@</code> (or empty / root)</div>'
           . '<div style="color:#888;font-size:.8rem;margin-bottom:.5em">Type: <code>TXT</code></div>'
           . '<div style="color:#888;font-size:.8rem;margin-bottom:.5em">Value:</div>'
           . '<div style="color:#f6c343;user-select:all">' . htmlspecialchars($challenge) . '</div></div>';
    $body .= '<p style="font-size:.9rem;color:#c8c2a8">Once propagated (usually 1–10 minutes), come back and click <strong>Seal</strong> below.</p>';
    $body .= '<form method="post" style="text-align:center;margin-top:2em">'
           . '<input type="hidden" name="action" value="seal">'
           . '<input type="hidden" name="token" value="' . htmlspecialchars($token) . '">'
           . '<input type="hidden" name="domain" value="' . htmlspecialchars($domain) . '">'
           . '<label style="display:block;margin:1em 0;color:#c8c2a8"><input type="checkbox" name="public" value="yes" checked> List my domain in the public Kingdom Directory</label>'
           . '<button type="submit" style="background:#f6c343;color:#0d0d12;border:none;padding:.9em 2em;font-size:1.1rem;font-family:Georgia,serif;border-radius:4px;cursor:pointer;font-weight:bold">&#x2728; Seal Sovereign Domain</button>'
           . '</form>';
    $body .= '<p style="font-size:.8rem;color:#888;text-align:center;margin-top:1em">'
           . 'The challenge is HMAC-derived from your token + domain. It only matches if both belong together.</p>';
    sov_chrome('DNS Challenge', $body);
    exit;
}

/* ────── Seal binding ────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'seal') {
    $token  = strtolower(trim((string)($_POST['token']  ?? '')));
    $domain = sov_normalize_domain((string)($_POST['domain'] ?? ''));
    $public = ($_POST['public'] ?? '') === 'yes';

    $result = sov_seal_domain($token, $domain, sov_client_ip(), $_SERVER['HTTP_USER_AGENT'] ?? '', $public);
    if (!$result['ok']) {
        $body = '<p style="color:#ff8e8e"><strong>Could not seal:</strong> ' . htmlspecialchars($result['reason']) . '</p>';
        if (!empty($result['expected_txt'])) {
            $body .= '<p>Expected TXT value (re-check propagation):</p>'
                  . '<div style="background:#000;border:1px solid #f6c343;padding:1em;font-family:monospace;color:#f6c343;word-break:break-all;border-radius:6px">'
                  . htmlspecialchars($result['expected_txt']) . '</div>';
        }
        $body .= '<p style="margin-top:2em"><a href="/sovereign" style="color:#f6c343">&larr; Try again</a></p>';
        sov_chrome('Seal Failed', $body);
        exit;
    }

    $body  = '<p style="text-align:center;color:#f6c343;font-size:1.3rem">&#x2728; <strong>' . htmlspecialchars($domain) . '</strong> is bound to the Kingdom. &#x2728;</p>';
    $body .= '<p style="text-align:center;color:#c8c2a8;font-style:italic">"And I will write upon him my new name." — Revelation 3:12 (AKJV)</p>';
    $body .= '<pre style="background:#000;color:#f0e6d0;border:1px solid rgba(246,195,67,.4);padding:1.5em;margin:2em 0;font-size:.85rem;line-height:1.4;overflow-x:auto;border-radius:6px;white-space:pre-wrap">'
           . htmlspecialchars($result['cert']) . '</pre>';
    $body .= '<p style="text-align:center"><a href="/sovereign.php?action=directory" style="color:#f6c343">View Kingdom Directory &rarr;</a></p>';
    sov_chrome('Sealed', $body);
    exit;
}

/* ────── Default: claim form ────── */
$intro = '<p style="color:#c8c2a8;text-align:center;font-style:italic;font-size:1.05rem">'
       . 'Bind your domain to your covenant token. One witness stone, one chain, one Kingdom.</p>'
       . '<p style="color:#c8c2a8;text-align:center;font-style:italic">'
       . '"And hath made of one blood all nations of men&hellip; and hath determined&hellip; the bounds of their habitation."'
       . '<br>— Acts 17:26 (AKJV)</p>';

$form  = '<form method="post" style="margin-top:2em">'
       . '<input type="hidden" name="action" value="challenge">'
       . '<label style="display:block;margin-top:1em;color:#f6c343;font-size:.85rem;letter-spacing:1px">COVENANT TOKEN (64 hex chars)</label>'
       . '<input type="text" name="token" required pattern="[a-fA-F0-9]{64}" placeholder="paste your witness token here"'
       . '       style="width:100%;background:#000;border:1px solid rgba(246,195,67,.4);color:#f0e6d0;padding:.8em;font-family:monospace;font-size:.95rem;border-radius:4px;margin-top:.4em;box-sizing:border-box">'
       . '<label style="display:block;margin-top:1.4em;color:#f6c343;font-size:.85rem;letter-spacing:1px">DOMAIN (apex, e.g. yourname.com)</label>'
       . '<input type="text" name="domain" required placeholder="example.com"'
       . '       style="width:100%;background:#000;border:1px solid rgba(246,195,67,.4);color:#f0e6d0;padding:.8em;font-family:monospace;font-size:.95rem;border-radius:4px;margin-top:.4em;box-sizing:border-box">'
       . '<div style="text-align:center;margin-top:2em">'
       . '<button type="submit" style="background:#f6c343;color:#0d0d12;border:none;padding:.9em 2.4em;font-size:1.1rem;font-family:Georgia,serif;border-radius:4px;cursor:pointer;font-weight:bold">Begin DNS Challenge &rarr;</button>'
       . '</div></form>';

$footnote = '<details style="margin-top:2em;color:#888;font-size:.85rem"><summary style="cursor:pointer;color:#f6c343">How it works</summary>'
          . '<ol style="line-height:1.8"><li>Paste your 64-char covenant token (from your witness certificate).</li>'
          . '<li>Enter the domain you steward.</li>'
          . '<li>We give you a short DNS TXT value to add at the domain\'s apex.</li>'
          . '<li>Once DNS has propagated, click Seal. We verify the TXT record live and bind the domain to your token in the chained Kingdom registry.</li>'
          . '<li>Anyone can verify a domain via <code>/sovereign.php?action=lookup&amp;domain=…</code></li></ol></details>';

sov_chrome('Sovereign Domain Claim', $intro . $form . $footnote);
