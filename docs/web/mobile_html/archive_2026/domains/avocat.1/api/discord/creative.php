<?php
/**
 * GoSiteMe Discord Bot — Creative Writing Module
 * Commands: /poem, /lyrics, /script
 * Uses: Groq AI for all creative generation
 */

namespace GoSiteMe\Discord;
require_once __DIR__ . '/core.php';

// ─── /poem ─────────────────────────────────────────────────────────────
function handlePoem(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $topic = ''; $style = 'free verse';
    foreach ($opts as $o) {
        if ($o['name'] === 'topic') $topic = trim($o['value']);
        if ($o['name'] === 'style') $style = $o['value'];
    }
    if (!$topic) { respondEphemeral('❌ Please provide a topic.'); return; }

    $userId = $data['member']['user']['id'];
    $username = $data['member']['user']['username'];
    $appId = $data['application_id'];
    $token = $data['token'];

    deferResponse();
    getOrCreateUser($userId, $username);

    $styles = [
        'free verse' => 'Write in free verse — no rigid structure, focus on imagery and emotion.',
        'sonnet' => 'Write a Shakespearean sonnet (14 lines, ABAB CDCD EFEF GG rhyme scheme, iambic pentameter).',
        'haiku' => 'Write a series of 5 connected haiku (5-7-5 syllable pattern each).',
        'limerick' => 'Write 3 connected limericks (AABBA rhyme scheme, humorous).',
        'ballad' => 'Write a narrative ballad with 4-6 stanzas, ABAB rhyme scheme, storytelling focus.',
        'epic' => 'Write in heroic/epic style — grand, dramatic, mythological tone with powerful imagery.',
        'spoken word' => 'Write a spoken word poem — conversational, rhythmic, emphasis on performance and delivery. Use line breaks for rhythm.',
        'rap' => 'Write as rap verses — internal rhymes, wordplay, flow, metaphors. 2 verses + chorus.',
    ];

    $stylePrompt = $styles[$style] ?? $styles['free verse'];

    $result = callGroq(
        "You are a master poet renowned worldwide. Write a beautiful, original poem.\n$stylePrompt\nMake it profound, emotionally resonant, and memorable. Use vivid imagery, metaphor, and symbolism. Title the poem.",
        "Topic: $topic",
        0.9, 1200
    );

    if (!$result) {
        editOriginal($appId, $token, '❌ The muse abandoned us. Try again.');
        return;
    }

    $colors = [
        'free verse' => 0x9B59B6, 'sonnet' => 0xE74C3C, 'haiku' => 0x1ABC9C,
        'limerick' => 0xF39C12, 'ballad' => 0x3498DB, 'epic' => 0xE67E22,
        'spoken word' => 0x2ECC71, 'rap' => 0xE91E63,
    ];

    followUp($appId, $token, '', [embed(
        "✨ $style — \"$topic\"",
        truncate($result, 4000),
        $colors[$style] ?? 0x9B59B6,
        [],
        ['footer' => ['text' => "Style: $style | Requested by $username"]]
    )], [actionRow(
        btn(2, '✨ Another', 'poem_another'),
        btn(2, '📝 Sonnet', 'poem_sonnet'),
        btn(2, '🎤 Spoken Word', 'poem_spokenword'),
        btn(2, '🎵 Rap', 'poem_rap')
    )]);

    awardXP($userId, 5, $appId, $token);
}

// ─── /lyrics ───────────────────────────────────────────────────────────
function handleLyrics(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $topic = ''; $genre = 'pop';
    foreach ($opts as $o) {
        if ($o['name'] === 'topic') $topic = trim($o['value']);
        if ($o['name'] === 'genre') $genre = $o['value'];
    }
    if (!$topic) { respondEphemeral('❌ What should the song be about?'); return; }

    $userId = $data['member']['user']['id'];
    $username = $data['member']['user']['username'];
    $appId = $data['application_id'];
    $token = $data['token'];

    deferResponse();
    getOrCreateUser($userId, $username);

    $genres = [
        'pop' => 'Write catchy pop lyrics — memorable hooks, relatable themes, verse-chorus-verse-chorus-bridge-chorus structure.',
        'rock' => 'Write rock lyrics — powerful, emotional, guitar-driven energy. Verse-chorus structure with a climactic bridge.',
        'hiphop' => 'Write hip-hop lyrics — clever wordplay, internal rhymes, flow, cultural references. 2 verses + hook + bridge.',
        'country' => 'Write country lyrics — storytelling, imagery of rural life, heartfelt emotion, classic AABB structure.',
        'rnb' => 'Write R&B lyrics — smooth, emotional, romantic. Sultry verses, soaring chorus, ad-libs noted in brackets.',
        'metal' => 'Write metal lyrics — intense, powerful imagery, dark themes, aggressive energy. Include [BREAKDOWN] marking.',
        'indie' => 'Write indie lyrics — introspective, unconventional, poetic. Abstract imagery with personal vulnerability.',
        'edm' => 'Write EDM/dance lyrics — short impactful phrases, build-drop structure, hypnotic repetition, euphoric energy.',
    ];

    $genrePrompt = $genres[$genre] ?? $genres['pop'];

    $result = callGroq(
        "You are a Grammy-nominated songwriter. Write complete song lyrics.\n$genrePrompt\nInclude section labels like [Verse 1], [Chorus], [Bridge], [Outro]. Make the chorus catchy and singable. Title the song at the top.",
        "Song topic: $topic",
        0.85, 1500
    );

    if (!$result) {
        editOriginal($appId, $token, '❌ Writer\'s block hit hard. Try again.');
        return;
    }

    $genreEmojis = [
        'pop' => '🎤', 'rock' => '🎸', 'hiphop' => '🎤', 'country' => '🤠',
        'rnb' => '🎶', 'metal' => '🤘', 'indie' => '🎹', 'edm' => '🎧',
    ];

    followUp($appId, $token, '', [embed(
        ($genreEmojis[$genre] ?? '🎵') . " $genre Song — \"$topic\"",
        truncate($result, 4000),
        0xE91E63,
        [],
        ['footer' => ['text' => "Genre: $genre | Written for $username"]]
    )], [actionRow(
        btn(2, '🎵 Another', 'lyrics_another'),
        btn(2, '🎸 Rock Version', 'lyrics_rock'),
        btn(2, '🎤 Hip-Hop', 'lyrics_hiphop'),
        btn(2, '🤠 Country', 'lyrics_country')
    )]);

    awardXP($userId, 5, $appId, $token);
}

// ─── /script ───────────────────────────────────────────────────────────
function handleScript(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $premise = ''; $format = 'sketch';
    foreach ($opts as $o) {
        if ($o['name'] === 'premise') $premise = trim($o['value']);
        if ($o['name'] === 'format') $format = $o['value'];
    }
    if (!$premise) { respondEphemeral('❌ Provide a premise for the script.'); return; }

    $userId = $data['member']['user']['id'];
    $username = $data['member']['user']['username'];
    $appId = $data['application_id'];
    $token = $data['token'];

    deferResponse();
    getOrCreateUser($userId, $username);

    $formats = [
        'sketch' => 'Write a comedy sketch (2-3 minutes). 2-3 characters, escalating humor, strong punchline ending. Format with CHARACTER: lines.',
        'short film' => 'Write a short film script (5 minutes). Include scene headings (INT./EXT.), action lines, dialogue. Dramatic with a twist.',
        'monologue' => 'Write a dramatic monologue (3 minutes). One character, building emotion, revelatory ending. Include stage directions in parentheses.',
        'sitcom' => 'Write a sitcom cold open (2-3 minutes). 3-4 characters, setup-punchline rhythm, end on a big laugh with a cut transition.',
        'horror' => 'Write a horror short script (3 minutes). Slow build, atmospheric descriptions, one terrifying moment. Minimal dialogue.',
        'commercial' => 'Write a creative/funny commercial script (30 seconds). Product: whatever the premise describes. Include NARRATOR: voice-over lines.',
    ];

    $formatPrompt = $formats[$format] ?? $formats['sketch'];

    $result = callGroq(
        "You are an accomplished screenwriter. Write a polished script.\n$formatPrompt\nUse proper screenplay formatting adapted for Discord (bold character names, italicize stage directions).",
        "Premise: $premise",
        0.85, 2000
    );

    if (!$result) {
        editOriginal($appId, $token, '❌ Script generation failed. Try again.');
        return;
    }

    $formatEmojis = [
        'sketch' => '😂', 'short film' => '🎬', 'monologue' => '🎭',
        'sitcom' => '📺', 'horror' => '👻', 'commercial' => '📢',
    ];

    followUp($appId, $token, '', [embed(
        ($formatEmojis[$format] ?? '🎬') . " $format — \"$premise\"",
        truncate($result, 4000),
        0xFF5722,
        [],
        ['footer' => ['text' => "Format: $format | Written for $username"]]
    )], [actionRow(
        btn(2, '🎬 Another', 'script_another'),
        btn(2, '😂 Sketch', 'script_sketch'),
        btn(2, '👻 Horror', 'script_horror'),
        btn(2, '📺 Sitcom', 'script_sitcom')
    )]);

    awardXP($userId, 5, $appId, $token);
}
