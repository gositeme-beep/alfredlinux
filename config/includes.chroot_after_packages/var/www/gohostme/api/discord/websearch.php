<?php
/**
 * GoSiteMe Discord Bot — Web Search & Research Module
 * Commands: /websearch, /readurl, /research, /whois
 * Uses: Jina Reader (free), RDAP (free), Groq AI
 */

namespace GoSiteMe\Discord;
require_once __DIR__ . '/core.php';

// ─── /websearch ────────────────────────────────────────────────────────
function handleWebsearch(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $query = '';
    foreach ($opts as $o) { if ($o['name'] === 'query') $query = trim($o['value']); }
    if (!$query) { respondEphemeral('❌ Please provide a search query.'); return; }

    $userId = $data['member']['user']['id'];
    $username = $data['member']['user']['username'];
    $appId = $data['application_id'];
    $token = $data['token'];

    deferResponse();
    getOrCreateUser($userId, $username);

    $jinaUrl = 'https://s.jina.ai/' . rawurlencode($query);
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $jinaUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER     => ['Accept: application/json', 'X-Return-Format: markdown'],
        CURLOPT_USERAGENT      => 'GoSiteMeBot/3.0',
    ]);
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200 || !$raw) {
        editOriginal($appId, $token, '❌ Search failed. Try again later.');
        return;
    }

    // Parse Jina response or use raw markdown
    $content = $raw;
    $json = json_decode($raw, true);
    if ($json && isset($json['data'])) {
        $content = $json['data'];
    }

    // Summarize with Groq
    $summary = callGroq(
        "You are a search results summarizer. Given raw search results, provide a clean, well-organized summary with the top 5 most relevant results. Format each result with a title, brief description, and URL. Use Discord markdown formatting.",
        "Search query: $query\n\nRaw results:\n" . substr($content, 0, 6000),
        0.3, 1500
    );

    if (!$summary) $summary = truncate($content, 3900);

    followUp($appId, $token, '', [embed(
        "🔍 Web Search: $query",
        truncate($summary, 4000),
        0x4285F4,
        [],
        ['footer' => ['text' => 'Powered by Jina AI Search']]
    )], [actionRow(
        btn(2, '🔄 Search Again', 'websearch_new'),
        btn(5, '🌐 Google', 'https://www.google.com/search?q=' . rawurlencode($query))
    )]);

    awardXP($userId, 3, $appId, $token);
}

// ─── /readurl ──────────────────────────────────────────────────────────
function handleReadurl(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $url = '';
    $summarize = false;
    foreach ($opts as $o) {
        if ($o['name'] === 'url') $url = trim($o['value']);
        if ($o['name'] === 'summarize') $summarize = (bool)$o['value'];
    }
    if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) { respondEphemeral('❌ Please provide a valid URL.'); return; }

    $userId = $data['member']['user']['id'];
    $username = $data['member']['user']['username'];
    $appId = $data['application_id'];
    $token = $data['token'];

    deferResponse();
    getOrCreateUser($userId, $username);

    $jinaUrl = 'https://r.jina.ai/' . $url;
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $jinaUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER     => ['Accept: application/json', 'X-Return-Format: markdown'],
        CURLOPT_USERAGENT      => 'GoSiteMeBot/3.0',
    ]);
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200 || !$raw) {
        editOriginal($appId, $token, '❌ Could not fetch URL. Make sure it\'s accessible.');
        return;
    }

    $content = $raw;
    $json = json_decode($raw, true);
    if ($json && isset($json['data'])) $content = $json['data'];
    if ($json && isset($json['data']['content'])) $content = $json['data']['content'];

    if ($summarize && is_string($content)) {
        $content = callGroq(
            "Summarize this webpage content in 500 words or less. Use Discord markdown. Highlight key points with bullet points.",
            substr($content, 0, 8000),
            0.3, 1000
        ) ?: $content;
    }

    $title = 'Webpage Content';
    if ($json && isset($json['data']['title'])) $title = $json['data']['title'];

    followUp($appId, $token, '', [embed(
        $summarize ? "📝 Summary: $title" : "📄 $title",
        truncate(is_string($content) ? $content : json_encode($content), 4000),
        0x34A853,
        [field('Source', "[Link]($url)", true)],
        ['footer' => ['text' => 'Jina Reader API']]
    )]);

    awardXP($userId, 3, $appId, $token);
}

// ─── /research ─────────────────────────────────────────────────────────
function handleResearch(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $topic = '';
    $depth = 'standard';
    foreach ($opts as $o) {
        if ($o['name'] === 'topic') $topic = trim($o['value']);
        if ($o['name'] === 'depth') $depth = $o['value'];
    }
    if (!$topic) { respondEphemeral('❌ Please provide a research topic.'); return; }

    $userId = $data['member']['user']['id'];
    $username = $data['member']['user']['username'];
    $appId = $data['application_id'];
    $token = $data['token'];

    deferResponse();
    $user = getOrCreateUser($userId, $username);

    $cost = $depth === 'deep' ? 15 : 5;
    if (($user['kgd_balance'] ?? 0) < $cost) {
        editOriginal($appId, $token, "❌ Research costs **$cost KGD**. You have **{$user['kgd_balance']} KGD**.");
        return;
    }

    // Deduct
    $pdo = getDiscordDB();
    if ($pdo) {
        $pdo->prepare("UPDATE discord_users SET kgd_balance = kgd_balance - ?, total_spent = total_spent + ? WHERE discord_id = ?")->execute([$cost, $cost, $userId]);
        $pdo->prepare("INSERT INTO discord_economy (discord_id, entry_type, amount, reason) VALUES (?, 'spend', ?, ?)")->execute([$userId, -$cost, "Research: $topic"]);
    }

    // Step 1: Web search for context
    $searchUrl = 'https://s.jina.ai/' . rawurlencode($topic);
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $searchUrl, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_USERAGENT => 'GoSiteMeBot/3.0',
    ]);
    $searchRaw = curl_exec($ch);
    curl_close($ch);

    $searchContext = '';
    $searchJson = json_decode($searchRaw, true);
    if ($searchJson && isset($searchJson['data'])) {
        $searchContext = is_string($searchJson['data']) ? $searchJson['data'] : json_encode($searchJson['data']);
    } else {
        $searchContext = $searchRaw ?: '';
    }

    // Step 2: Deep AI synthesis
    $depthInstruction = $depth === 'deep'
        ? "Write a comprehensive 1500-word research report with sections: Executive Summary, Background, Key Findings (minimum 5), Analysis, Expert Perspectives, Controversies/Debates, Future Outlook, and Conclusion. Cite sources where possible."
        : "Write a focused 800-word research brief with sections: Overview, Key Findings (3-5 points), Analysis, and Conclusion.";

    $result = callGroq(
        "You are a senior research analyst. $depthInstruction Use Discord markdown formatting with headers (##), bold, bullet points. Be factual and cite specific data/statistics when available.",
        "Research Topic: $topic\n\nWeb Search Context:\n" . substr($searchContext, 0, 8000),
        0.4, $depth === 'deep' ? 3000 : 1500
    );

    if (!$result) {
        editOriginal($appId, $token, '❌ Research generation failed. Your KGD has been spent.');
        return;
    }

    // Split into embeds if too long
    if (strlen($result) > 4000) {
        $part1 = substr($result, 0, 4000);
        $lastNewline = strrpos($part1, "\n");
        if ($lastNewline > 3000) $part1 = substr($result, 0, $lastNewline);
        $part2 = substr($result, strlen($part1));

        followUp($appId, $token, '', [
            embed("📚 Research Report: $topic", truncate($part1, 4090), 0x9C27B0, [], ['footer' => ['text' => "Part 1 | Cost: $cost KGD"]]),
        ]);
        followUp($appId, $token, '', [
            embed("📚 Research (cont.)", truncate($part2, 4090), 0x9C27B0, [], ['footer' => ['text' => "Part 2 | Depth: $depth"]]),
        ], [actionRow(
            btn(2, '🔄 Research More', 'research_more'),
            btn(2, '📊 Deep Dive', 'research_deep')
        )]);
    } else {
        followUp($appId, $token, '', [embed(
            "📚 Research Report: $topic",
            truncate($result, 4090),
            0x9C27B0,
            [],
            ['footer' => ['text' => "Cost: $cost KGD | Depth: $depth"]]
        )], [actionRow(
            btn(2, '🔄 Research More', 'research_more'),
            btn(2, '📊 Deep Dive', 'research_deep')
        )]);
    }

    awardXP($userId, 15, $appId, $token);
}

// ─── /whois ────────────────────────────────────────────────────────────
function handleWhois(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $domainInput = '';
    foreach ($opts as $o) { if ($o['name'] === 'domain') $domainInput = trim($o['value']); }
    if (!$domainInput) { respondEphemeral('❌ Please provide a domain name.'); return; }

    // Sanitize domain
    $domain = preg_replace('/^https?:\/\//', '', $domainInput);
    $domain = preg_replace('/\/.*$/', '', $domain);
    $domain = strtolower(trim($domain));

    if (!preg_match('/^[a-z0-9][a-z0-9\-]*\.[a-z]{2,}$/i', $domain)) {
        respondEphemeral('❌ Invalid domain format. Example: `example.com`');
        return;
    }

    $userId = $data['member']['user']['id'];
    $username = $data['member']['user']['username'];
    $appId = $data['application_id'];
    $token = $data['token'];

    deferResponse();
    getOrCreateUser($userId, $username);

    // RDAP lookup (free, no API key)
    $rdapUrl = "https://rdap.org/domain/$domain";
    $rdap = httpGet($rdapUrl);
    $rdapData = $rdap ? json_decode($rdap, true) : null;

    $fields = [];
    if ($rdapData) {
        // Registrar
        $registrar = 'Unknown';
        foreach ($rdapData['entities'] ?? [] as $entity) {
            foreach ($entity['roles'] ?? [] as $role) {
                if ($role === 'registrar') {
                    $registrar = $entity['vcardArray'][1][1][3] ?? ($entity['handle'] ?? 'Unknown');
                }
            }
        }
        $fields[] = field('Registrar', $registrar, true);

        // Status
        $statuses = array_map(fn($s) => str_replace(' ', '', ucfirst($s)), array_slice($rdapData['status'] ?? ['Unknown'], 0, 4));
        $fields[] = field('Status', implode(', ', $statuses), true);

        // Dates
        foreach ($rdapData['events'] ?? [] as $event) {
            $date = date('M j, Y', strtotime($event['eventDate']));
            if ($event['eventAction'] === 'registration') $fields[] = field('Registered', $date, true);
            if ($event['eventAction'] === 'expiration') $fields[] = field('Expires', $date, true);
            if ($event['eventAction'] === 'last changed') $fields[] = field('Last Updated', $date, true);
        }

        // Nameservers
        $ns = array_map(fn($n) => '`' . ($n['ldhName'] ?? 'Unknown') . '`', array_slice($rdapData['nameservers'] ?? [], 0, 4));
        if ($ns) $fields[] = field('Nameservers', implode("\n", $ns), false);
    }

    // DNS lookup
    $dnsA = dns_get_record($domain, DNS_A);
    $dnsAAAA = dns_get_record($domain, DNS_AAAA);
    $dnsMX = dns_get_record($domain, DNS_MX);

    $ips = array_map(fn($r) => '`' . $r['ip'] . '`', $dnsA ?: []);
    $ipv6 = array_map(fn($r) => '`' . $r['ipv6'] . '`', $dnsAAAA ?: []);
    $mx = array_map(fn($r) => '`' . $r['target'] . '`', array_slice($dnsMX ?: [], 0, 3));

    if ($ips) $fields[] = field('IPv4', implode("\n", $ips), true);
    if ($ipv6) $fields[] = field('IPv6', implode("\n", array_slice($ipv6, 0, 2)), true);
    if ($mx) $fields[] = field('Mail (MX)', implode("\n", $mx), true);

    // SSL check
    $ssl = @stream_context_create(['ssl' => ['capture_peer_cert' => true, 'verify_peer' => false]]);
    $stream = @stream_socket_client("ssl://$domain:443", $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $ssl);
    $sslInfo = '❌ No SSL';
    if ($stream) {
        $cert = stream_context_get_params($stream);
        $certData = openssl_x509_parse($cert['options']['ssl']['peer_certificate'] ?? '');
        if ($certData) {
            $sslExpiry = date('M j, Y', $certData['validTo_time_t']);
            $sslIssuer = $certData['issuer']['O'] ?? $certData['issuer']['CN'] ?? 'Unknown';
            $sslInfo = "✅ Valid\nIssuer: $sslIssuer\nExpires: $sslExpiry";
        }
        fclose($stream);
    }
    $fields[] = field('SSL Certificate', $sslInfo, false);

    $desc = $rdapData ? "RDAP registration data for **$domain**" : "⚠️ RDAP data unavailable — showing DNS only for **$domain**";

    followUp($appId, $token, '', [embed(
        "🔎 WHOIS: $domain",
        $desc,
        0x00BCD4,
        $fields,
        ['footer' => ['text' => 'RDAP + DNS Lookup']]
    )], [actionRow(
        btn(2, '🔄 Refresh', "whois_refresh_$domain"),
        btn(5, '🌐 Visit', "https://$domain")
    )]);

    awardXP($userId, 3, $appId, $token);
}
