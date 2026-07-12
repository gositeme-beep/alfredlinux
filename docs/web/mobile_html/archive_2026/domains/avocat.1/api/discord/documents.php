<?php
/**
 * GoSiteMe Discord Bot — Document Processor Module
 * ══════════════════════════════════════════════════
 * Commands: /doc /ocr /summarizedoc /fileinfo
 * Handles file attachments: PDF, images, text, code files.
 */

function handleDoc($data): void {
    $sub = $data['data']['options'][0]['name'] ?? 'parse';
    $opts = [];
    foreach (($data['data']['options'][0]['options'] ?? []) as $o) {
        $opts[$o['name']] = $o['value'];
    }

    $userId   = $data['member']['user']['id'] ?? ($data['user']['id'] ?? '');
    $username = $data['member']['user']['username'] ?? ($data['user']['username'] ?? 'User');

    // Get attachment from resolved data
    $attachments = $data['data']['resolved']['attachments'] ?? [];
    $attachment = null;
    $attachId = $opts['file'] ?? '';
    if ($attachId && isset($attachments[$attachId])) {
        $attachment = $attachments[$attachId];
    } elseif (!empty($attachments)) {
        $attachment = reset($attachments);
    }

    if (!$attachment) {
        respondEphemeral("❌ Please attach a file to process.");
        return;
    }

    $url      = $attachment['url'] ?? '';
    $filename = $attachment['filename'] ?? 'unknown';
    $size     = $attachment['size'] ?? 0;
    $type     = $attachment['content_type'] ?? '';

    deferResponse();
    $appId = getenv('DISCORD_APP_ID') ?: '';
    $token = $data['token'] ?? '';

    // Download content
    $content = httpGet($url, 30);
    if (!$content) {
        editOriginal($appId, $token, '❌ Failed to download the file.');
        return;
    }

    $sizeStr = $size > 1048576 ? round($size / 1048576, 1) . ' MB' : round($size / 1024, 1) . ' KB';

    switch ($sub) {
        case 'parse':
            // Extract text based on content type
            $text = '';
            if (strpos($type, 'text/') !== false || preg_match('/\.(txt|md|csv|json|xml|html|php|js|py|css|log|yml|yaml|ini|conf|sh|sql)$/i', $filename)) {
                $text = $content;
            } elseif ($type === 'application/json') {
                $decoded = json_decode($content, true);
                $text = $decoded ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $content;
            } else {
                $text = "Binary file detected. Content type: $type\nUse `/ocr` for images or `/fileinfo` for metadata.";
            }

            $text = truncate($text, 3800);
            editOriginal($appId, $token, '', [embed("📄 Parsed: $filename", "```\n$text\n```", 0x3498DB, [
                field('Size', $sizeStr, true),
                field('Type', $type ?: 'unknown', true),
                field('Lines', (string)substr_count($text, "\n"), true),
            ])], [actionRow(
                btn(1, '🤖 AI Summarize', "doc_summarize_$attachId"),
                btn(2, '📊 Analyze', "doc_analyze_$attachId"),
                btn(2, '🔍 Extract Data', "doc_extract_$attachId")
            )]);
            break;

        case 'summarize':
            $text = '';
            if (strpos($type, 'text/') !== false || strpos($type, 'json') !== false ||
                preg_match('/\.(txt|md|csv|json|xml|html|php|js|py|css|log)$/i', $filename)) {
                $text = mb_substr($content, 0, 8000);
            } else {
                editOriginal($appId, $token, '❌ Can only summarize text-based files. Use `/ocr` for images.');
                return;
            }

            $summary = callGroq(
                "Summarize this document concisely. Include:\n- Main purpose\n- Key points (bullet list)\n- Notable details\n- Conclusion/recommendation if applicable",
                "Filename: $filename\n\nContent:\n$text",
                0.5, 800
            );

            editOriginal($appId, $token, '', [embed("📋 Summary: $filename", $summary ?: 'Could not summarize.', 0x2ECC71, [
                field('Original Size', $sizeStr, true),
                field('Type', $type ?: 'unknown', true),
            ])]);
            awardXP($userId, 5);
            break;

        case 'analyze':
            $text = '';
            if (strpos($type, 'text/') !== false || strpos($type, 'json') !== false ||
                preg_match('/\.(txt|md|csv|json|xml|html|php|js|py|css|log|sql)$/i', $filename)) {
                $text = mb_substr($content, 0, 8000);
            } else {
                editOriginal($appId, $token, '❌ Can only analyze text-based files.');
                return;
            }

            $analysis = callGroq(
                "You are a document analyst. Analyze this file and provide:\n1. **Type & Purpose**: What kind of document is this?\n2. **Structure**: How is it organized?\n3. **Quality Score**: Rate 1-10 with justification\n4. **Issues Found**: Any errors, inconsistencies, or improvements?\n5. **Key Insights**: What stands out?\n\nBe specific and technical.",
                "Filename: $filename\nType: $type\n\nContent:\n$text",
                0.5, 800
            );

            editOriginal($appId, $token, '', [embed("🔬 Analysis: $filename", $analysis ?: 'Could not analyze.', 0x9B59B6, [
                field('Size', $sizeStr, true),
                field('Type', $type ?: 'unknown', true),
            ])]);
            awardXP($userId, 5);
            break;

        default:
            editOriginal($appId, $token, 'Unknown subcommand.');
    }
}

function handleOcr($data): void {
    $userId   = $data['member']['user']['id'] ?? ($data['user']['id'] ?? '');
    $attachments = $data['data']['resolved']['attachments'] ?? [];
    $attachment = null;
    $attachId = '';
    foreach (($data['data']['options'] ?? []) as $o) {
        if ($o['name'] === 'image') $attachId = $o['value'];
    }
    if ($attachId && isset($attachments[$attachId])) {
        $attachment = $attachments[$attachId];
    } elseif (!empty($attachments)) {
        $attachment = reset($attachments);
    }

    if (!$attachment) {
        respondEphemeral("❌ Please attach an image.");
        return;
    }

    $url = $attachment['url'] ?? '';
    $type = $attachment['content_type'] ?? '';

    if (!preg_match('/^image\//i', $type)) {
        respondEphemeral("❌ Please attach an image file (PNG, JPG, WEBP).");
        return;
    }

    deferResponse();
    $appId = getenv('DISCORD_APP_ID') ?: '';
    $token = $data['token'] ?? '';

    // Use Groq vision model for OCR
    $result = callGroq(
        "You are an OCR system. Extract ALL text visible in this image. Maintain formatting, line breaks, and structure as closely as possible. If it's a document, preserve paragraphs. If it's a form, show field:value pairs. If text is unclear, mark it as [illegible]. Output ONLY the extracted text.",
        "Image URL: $url\n\nPlease extract all text from the image at the URL above.",
        0.2, 2000
    );

    if (!$result) {
        editOriginal($appId, $token, '❌ Could not extract text from the image.');
        return;
    }

    editOriginal($appId, $token, '', [embed("📸 OCR Result", "```\n" . truncate($result, 3800) . "\n```", 0x3498DB, [
        field('Source', $attachment['filename'] ?? 'image', true),
        field('Characters', (string)strlen($result), true),
    ])], [actionRow(
        btn(1, '📋 Summarize Text', 'ocr_summarize'),
        btn(2, '🔍 Analyze Content', 'ocr_analyze')
    )]);
    awardXP($userId, 5);
}

function handleSummarizedoc($data): void {
    $url = '';
    foreach (($data['data']['options'] ?? []) as $o) {
        if ($o['name'] === 'url') $url = $o['value'];
    }

    if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
        respondEphemeral("❌ Please provide a valid URL.");
        return;
    }

    $userId = $data['member']['user']['id'] ?? ($data['user']['id'] ?? '');
    deferResponse();
    $appId = getenv('DISCORD_APP_ID') ?: '';
    $token = $data['token'] ?? '';

    // Use Jina Reader to fetch content
    $content = httpGet("https://r.jina.ai/" . $url, 20);

    if (!$content) {
        editOriginal($appId, $token, '❌ Could not fetch the URL content.');
        return;
    }

    $text = mb_substr($content, 0, 8000);

    $summary = callGroq(
        "Summarize this web page/document. Provide:\n📌 **Title & Source**\n📋 **Key Points** (bullet list)\n💡 **Main Takeaway** (1 sentence)\n📊 **Content Type** (article, documentation, blog, etc)",
        "URL: $url\n\nContent:\n$text",
        0.5, 800
    );

    editOriginal($appId, $token, '', [embed("📋 Document Summary", $summary ?: 'Could not summarize.', 0x2ECC71, [
        field('Source', truncate($url, 100), false),
    ])], [actionRow(
        btn(2, '🔄 Re-summarize', 'docsummary_refresh'),
        btn(5, '🔗 Open URL', $url)
    )]);
    awardXP($userId, 5);
}

function handleFileinfo($data): void {
    $attachments = $data['data']['resolved']['attachments'] ?? [];
    $attachment = null;
    $attachId = '';
    foreach (($data['data']['options'] ?? []) as $o) {
        if ($o['name'] === 'file') $attachId = $o['value'];
    }
    if ($attachId && isset($attachments[$attachId])) {
        $attachment = $attachments[$attachId];
    } elseif (!empty($attachments)) {
        $attachment = reset($attachments);
    }

    if (!$attachment) {
        respondEphemeral("❌ Please attach a file.");
        return;
    }

    $filename = $attachment['filename'] ?? 'unknown';
    $size     = $attachment['size'] ?? 0;
    $type     = $attachment['content_type'] ?? 'unknown';
    $url      = $attachment['url'] ?? '';
    $width    = $attachment['width'] ?? null;
    $height   = $attachment['height'] ?? null;

    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $sizeStr = $size > 1048576 ? round($size / 1048576, 2) . ' MB' : ($size > 1024 ? round($size / 1024, 2) . ' KB' : $size . ' B');

    $fields = [
        field('Filename', "`$filename`", false),
        field('Size', $sizeStr, true),
        field('MIME Type', "`$type`", true),
        field('Extension', $ext ? "`.{$ext}`" : 'None', true),
    ];

    if ($width && $height) {
        $fields[] = field('Dimensions', "{$width}×{$height}px", true);
        $mp = round(($width * $height) / 1000000, 2);
        $fields[] = field('Megapixels', "{$mp} MP", true);
    }

    // Detect file category
    $category = '📄 Document';
    if (preg_match('/^image\//', $type)) $category = '🖼️ Image';
    elseif (preg_match('/^video\//', $type)) $category = '🎬 Video';
    elseif (preg_match('/^audio\//', $type)) $category = '🎵 Audio';
    elseif (preg_match('/^text\//', $type)) $category = '📝 Text';
    elseif (preg_match('/zip|rar|7z|tar|gz/', $type)) $category = '📦 Archive';
    elseif (preg_match('/pdf/', $type)) $category = '📕 PDF';

    $fields[] = field('Category', $category, true);

    $extra = [];
    if (preg_match('/^image\//', $type) && $url) {
        $extra['thumbnail'] = ['url' => $url];
    }

    respond(null, [embed("📁 File Info: $filename", "Detailed metadata for the uploaded file.", 0x3498DB, $fields, $extra)]);
}
