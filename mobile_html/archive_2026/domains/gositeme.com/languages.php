<?php
$noGlobalMain = true;
// ─── Page Meta (set before site-header) ───
$page_title       = '300+ Languages & Technologies — Alfred AI | GoSiteMe';
$page_description = 'Explore 300+ programming languages, frameworks, and technologies supported by Alfred AI. From Python and JavaScript to Rust, Go, blockchain, and beyond.';
$page_canonical   = 'https://gositeme.com/languages.php';
$page_og_image    = 'https://gositeme.com/assets/hero-banner.png';

require_once __DIR__ . '/includes/site-header.inc.php';

$languages = [
    '🌐 Web Development' => [
        'JavaScript', 'TypeScript', 'HTML', 'CSS', 'SCSS / SASS', 'Less',
        'WebAssembly (WASM)', 'WAT', 'AssemblyScript', 'Grain', 'Gleam',
        'Pug / Jade', 'Handlebars', 'EJS', 'Jinja2'
    ],
    '⚙️ Backend Development' => [
        'Python', 'Java', 'C#', 'Go', 'Ruby', 'PHP', 'Rust', 'Scala',
        'Kotlin', 'Elixir', 'Haskell', 'Erlang', 'Clojure', 'F#',
        'Crystal', 'Nim', 'Zig', 'V (Vlang)', 'Carbon', 'Mojo',
        'Vale', 'Ante', 'Hare', 'Lobster', 'Odin', 'Jai', 'Beef', 'Roc'
    ],
    '📱 Mobile Development' => [
        'Swift', 'Objective-C', 'Kotlin', 'Dart (Flutter)',
        'React Native (JavaScript)'
    ],
    '🖥️ Systems & Low-Level' => [
        'C', 'C++', 'Rust', 'Assembly', 'Embedded C', 'MISRA C',
        'TinyGo', 'SPARK (Ada subset)'
    ],
    '🗄️ Database & Query Languages' => [
        'SQL (MySQL, PostgreSQL, SQLite)', 'NoSQL (MongoDB)', 'GraphQL',
        'PL/SQL', 'SPARQL', 'XQuery', 'OQL', 'MDX',
        'Flux (InfluxDB)', 'PromQL (Prometheus)', 'Cypher (Neo4j)'
    ],
    '🔧 Scripting & Automation' => [
        'Bash / Shell', 'PowerShell', 'Perl', 'Lua', 'Groovy',
        'Tcl', 'Rexx', 'AWK', 'Sed', 'Fish Shell', 'Zsh',
        'Csh / Tcsh', 'Batch (.bat)', 'VBScript', 'JScript'
    ],
    '📊 Data Science & Research' => [
        'R', 'MATLAB', 'Julia', 'SAS', 'SPSS Syntax', 'Stata',
        'BUGS / JAGS', 'Stan', 'Raku (Perl 6)'
    ],
    '☁️ Infrastructure & Config' => [
        'YAML', 'JSON', 'TOML', 'HCL (Terraform)', 'Dockerfile',
        'Kubernetes YAML', 'Bicep (Azure)', 'CloudFormation (AWS)',
        'CDK (TypeScript/Python)', 'Crossplane (YAML)', 'Salt (SLS)',
        'Helmfile', 'Kustomize', 'Ansible (YAML DSL)', 'Puppet DSL',
        'Chef (Ruby DSL)', 'Pulumi'
    ],
    '🎮 Game Development' => [
        'GDScript (Godot)', 'Lua (Roblox)', 'C++ (Unreal Engine)',
        'C# (Unity)', 'Papyrus (Skyrim)', 'UnrealScript',
        'AngelScript', 'Squirrel', 'Wren', 'ZScript (GZDoom)'
    ],
    '🧪 Scientific & Specialized' => [
        'Fortran', 'COBOL', 'Ada', 'Prolog', 'Lisp', 'Scheme',
        'APL', 'ABAP'
    ],
    '🌍 Markup & Templating' => [
        'Markdown', 'LaTeX', 'XML', 'XSLT', 'reStructuredText (RST)',
        'DocBook', 'Troff / Groff', 'Texinfo', 'PostScript'
    ],
    '🔌 Hardware & Embedded' => [
        'VHDL', 'Verilog', 'SystemVerilog', 'Arduino (C/C++)',
        'MicroPython', 'LabVIEW (G Language)', 'Simulink (MATLAB)',
        'SCADE', 'Ladder Logic (PLC)', 'Structured Text (IEC 61131-3)',
        'G-Code (CNC Machines)', 'URDF (Robot Description)'
    ],
    '📦 Build & Package Tools' => [
        'Makefile', 'CMake', 'Gradle (Groovy/Kotlin)', 'Bazel', 'Nix'
    ],
    '🧠 AI & Machine Learning' => [
        'TensorFlow (Python)', 'PyTorch (Python)', 'ONNX', 'Mojo'
    ],
    '🌊 Parallel & GPU Computing' => [
        'CUDA (C/C++)', 'OpenCL', 'SYCL', 'OpenMP', 'MPI',
        'ISPC', 'Halide'
    ],
    '🛠️ Domain Specific Languages (DSLs)' => [
        'Gherkin (BDD Testing)', 'Cucumber', 'Robot Framework',
        'Terraform HCL'
    ],
    '🌲 Parsing & Grammar' => [
        'Tree-sitter', 'ANTLR', 'Yacc / Bison', 'Lex / Flex',
        'PEG.js', 'Nearley'
    ],
    '🔒 Formal Verification & Proof' => [
        'TLA+', 'Alloy', 'Z Notation', 'B Method', 'Lean',
        'Isabelle', 'HOL', 'NuSMV'
    ],
    '🕸️ Semantic Web & Ontology' => [
        'OWL', 'RDF', 'Turtle (RDF Syntax)', 'N-Triples',
        'JSON-LD', 'SHACL', 'SWRL'
    ],
    '🧩 Functional Languages' => [
        'OCaml', 'Elm', 'PureScript', 'Idris', 'Agda', 'Coq',
        'Mercury', 'Clean', 'Standard ML (SML)', 'Alice ML',
        'Concurrent ML', 'Oz / Mozart', 'Curry', 'Logtalk',
        'XSB Prolog', 'SWI-Prolog'
    ],
    '🧵 Concatenative & Stack-Based' => [
        'Forth', 'Factor', 'Joy', 'Cat', 'RPL (HP Calculators)',
        'Kitten', 'Concat'
    ],
    '🔢 Array & Vector Languages' => [
        'APL', 'J', 'K', 'Q', 'BQN', 'Uiua', 'Dyalog APL'
    ],
    '🎵 Audio & Music Programming' => [
        'SuperCollider', 'ChucK', 'Csound', 'Faust', 'Max/MSP',
        'Pure Data (Pd)', 'TidalCycles', 'Sonic Pi'
    ],
    '📐 Mathematical & Symbolic' => [
        'Mathematica (Wolfram Language)', 'Maple', 'Maxima', 'Octave',
        'Scilab', 'GAP (Group Theory)', 'Magma', 'Macaulay2'
    ],
    '🧬 Bioinformatics' => [
        'BioPerl', 'BioPython', 'R (Bioconductor)', 'Nextflow', 'Snakemake'
    ],
    '🏦 Finance & Trading' => [
        'Q (kdb+)', 'J', 'Pine Script (TradingView)',
        'EasyLanguage (TradeStation)', 'MQL4 / MQL5 (MetaTrader)'
    ],
    '🎨 Shader & Graphics' => [
        'GLSL', 'HLSL', 'WGSL', 'Metal (Apple)', 'Cg'
    ],
    '📜 Legacy & Historical' => [
        'ALGOL', 'PL/I', 'RPG (IBM)', 'MUMPS / M', 'Simula',
        'SNOBOL', 'BASIC', 'Pascal', 'Delphi', 'Turbo Pascal'
    ],
    '🤖 Robotics & Automation' => [
        'ROS (Python/C++)', 'Ladder Logic (PLC)',
        'Structured Text (IEC 61131-3)', 'G-Code (CNC Machines)',
        'URDF (Robot Description)'
    ],
    '🔐 Security & Low-Level Scripting' => [
        'Metasploit (Ruby)', 'Yara', 'Sigma', 'Snort Rules'
    ],
    '📡 Networking & Protocols' => [
        'P4 (Network Programming)', 'NetLogo', 'NETCONF / YANG'
    ],
    '🛡️ Smart Contracts & Blockchain' => [
        'Solidity (Ethereum)', 'Vyper (Ethereum)', 'Rust (Solana)',
        'Move (Aptos/Sui)', 'Cadence (Flow)', 'Michelson (Tezos)',
        'Plutus (Cardano)', 'Ink! (Polkadot)'
    ],
    '🌏 Non-English Programming Languages' => [
        '易语言 (Chinese)', 'اردو کوڈ (Urdu)', 'Hindawi (Arabic)',
        'Sanskrit (experimental)', 'Rapira (Russian)', 'Perla (Spanish)'
    ],
    '🤪 Esoteric & Fun Languages' => [
        'Brainfuck', 'Whitespace', 'LOLCODE', 'Befunge', 'Malbolge',
        'Shakespeare (SPL)', 'Piet', 'Chef', 'Intercal', 'Ook!',
        'Cow', 'Rockstar', 'ArnoldC', 'Velato', 'Whenever', 'Folders'
    ],
    '🆕 Newest & Emerging (2020s)' => [
        'Carbon (Google, 2022)', 'Mojo (Modular, 2023)', 'Vale',
        'Ante', 'Hare', 'Lobster', 'Odin', 'Jai (Jonathan Blow)',
        'Beef', 'Roc'
    ],
];

$totalCount = 0;
foreach ($languages as $category => $langs) {
    $totalCount += count($langs);
}

// ─── Schema.org ItemList ───
$schema_languages = [
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => 'Programming Languages & Technologies Supported by Alfred AI',
    'description' => $page_description,
    'numberOfItems' => $totalCount,
    'itemListElement' => []
];
$pos = 1;
foreach ($languages as $cat => $langs) {
    foreach ($langs as $lang) {
        $schema_languages['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => $pos++,
            'name' => $lang
        ];
    }
}
?>
<script type="application/ld+json"><?php echo json_encode($schema_languages, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>

<style>
    /* ═══════════════════════════════════════════════
       Languages & Technologies Page
       ═══════════════════════════════════════════════ */

    .lang-hero {
        position: relative;
        text-align: center;
        padding: 100px 20px 60px;
        overflow: hidden;
    }
    .lang-hero::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background:
            radial-gradient(ellipse at 20% 30%, rgba(108,92,231,0.08) 0%, transparent 50%),
            radial-gradient(ellipse at 80% 70%, rgba(0,212,255,0.06) 0%, transparent 50%);
        pointer-events: none;
    }
    .lang-hero::after {
        content: '';
        position: absolute;
        bottom: 0; left: 50%;
        transform: translateX(-50%);
        width: 200px; height: 1px;
        background: linear-gradient(90deg, transparent, var(--al-accent, #6c5ce7), transparent);
    }

    .lang-badge {
        display: inline-block;
        background: rgba(108,92,231,0.12);
        border: 1px solid rgba(108,92,231,0.25);
        color: #a29bfe;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        padding: 6px 18px;
        border-radius: 50px;
        margin-bottom: 20px;
    }

    .lang-hero h1 {
        font-family: 'Space Grotesk', 'Inter', sans-serif;
        font-size: clamp(2.2rem, 5vw, 3.6rem);
        font-weight: 700;
        line-height: 1.1;
        margin-bottom: 16px;
        background: linear-gradient(135deg, #fff 30%, #a29bfe 70%, #00D4FF 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .lang-hero p {
        color: var(--al-text-secondary, #8a8ab0);
        font-size: 1.05rem;
        font-weight: 400;
        max-width: 600px;
        margin: 0 auto 36px;
        line-height: 1.7;
    }

    /* ── Stats bar ── */
    .lang-stats {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 12px 40px;
        margin-bottom: 12px;
    }
    .lang-stat {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .lang-stat-num {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 2.2rem;
        font-weight: 700;
        background: linear-gradient(135deg, #a29bfe, #00D4FF);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        line-height: 1;
    }
    .lang-stat-label {
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--al-text-secondary, #8a8ab0);
    }

    /* ── Sticky search bar ── */
    .lang-controls {
        position: sticky;
        top: 60px;
        z-index: 100;
        background: rgba(10,10,20,0.92);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border-bottom: 1px solid rgba(108,92,231,0.12);
        padding: 14px 20px;
    }
    .lang-controls-inner {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .lang-search-wrap {
        position: relative;
        flex: 1;
        min-width: 220px;
    }
    .lang-search-wrap svg {
        position: absolute;
        left: 14px; top: 50%;
        transform: translateY(-50%);
        color: var(--al-text-secondary, #8a8ab0);
        pointer-events: none;
    }
    #langSearchInput {
        width: 100%;
        background: var(--al-card-bg, #12121e);
        border: 1px solid rgba(108,92,231,0.15);
        border-radius: 10px;
        color: var(--al-text-primary, #e0e0e0);
        font-family: 'Inter', sans-serif;
        font-size: 0.9rem;
        padding: 10px 14px 10px 42px;
        outline: none;
        transition: border-color 0.25s, box-shadow 0.25s;
    }
    #langSearchInput:focus {
        border-color: #6c5ce7;
        box-shadow: 0 0 0 3px rgba(108,92,231,0.15);
    }
    #langSearchInput::placeholder { color: var(--al-text-secondary, #8a8ab0); }
    .lang-result-count {
        font-size: 0.8rem;
        color: var(--al-text-secondary, #8a8ab0);
        white-space: nowrap;
    }
    .lang-result-count span {
        color: #a29bfe;
        font-weight: 700;
    }

    /* ── Main layout ── */
    .lang-main {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px 80px;
    }

    /* ── Category section ── */
    .lang-cat {
        margin-bottom: 48px;
        transition: opacity 0.3s;
    }
    .lang-cat.hidden { display: none; }
    .lang-cat-header {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 18px;
    }
    .lang-cat-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--al-text-primary, #e0e0e0);
    }
    .lang-cat-count {
        background: rgba(108,92,231,0.12);
        border: 1px solid rgba(108,92,231,0.2);
        color: #a29bfe;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 50px;
        letter-spacing: 0.05em;
    }
    .lang-cat-line {
        flex: 1;
        height: 1px;
        background: linear-gradient(90deg, rgba(108,92,231,0.2), transparent);
    }

    /* ── Language tags ── */
    .lang-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    .lang-tag {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: rgba(108,92,231,0.06);
        border: 1px solid rgba(108,92,231,0.12);
        border-radius: 8px;
        padding: 7px 14px;
        font-family: 'Space Grotesk', monospace;
        font-size: 0.82rem;
        font-weight: 500;
        color: var(--al-text-primary, #e0e0e0);
        cursor: default;
        transition: background 0.2s, border-color 0.2s, transform 0.2s, color 0.2s;
        position: relative;
        overflow: hidden;
    }
    .lang-tag::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(108,92,231,0.12), transparent);
        opacity: 0;
        transition: opacity 0.25s;
    }
    .lang-tag:hover {
        background: rgba(108,92,231,0.15);
        border-color: rgba(108,92,231,0.4);
        color: #a29bfe;
        transform: translateY(-2px);
    }
    .lang-tag:hover::before { opacity: 1; }
    .lang-tag.highlight {
        background: rgba(0,212,255,0.1);
        border-color: #00D4FF;
        color: #00D4FF;
    }
    .lang-tag .dot {
        width: 6px; height: 6px;
        border-radius: 50%;
        background: #6c5ce7;
        flex-shrink: 0;
        transition: background 0.2s;
    }
    .lang-tag:hover .dot { background: #a29bfe; }
    .lang-tag.highlight .dot { background: #00D4FF; }

    /* ── CTA Section ── */
    .lang-cta {
        text-align: center;
        padding: 60px 20px 80px;
        border-top: 1px solid rgba(108,92,231,0.12);
    }
    .lang-cta-inner {
        max-width: 600px;
        margin: 0 auto;
    }
    .lang-cta-icon {
        font-size: 3rem;
        margin-bottom: 20px;
        display: block;
    }
    .lang-cta h2 {
        font-family: 'Space Grotesk', sans-serif;
        font-size: clamp(1.6rem, 3vw, 2.4rem);
        font-weight: 700;
        margin-bottom: 14px;
        color: var(--al-text-primary, #e0e0e0);
    }
    .lang-cta p {
        color: var(--al-text-secondary, #8a8ab0);
        font-size: 1rem;
        margin-bottom: 32px;
        line-height: 1.7;
    }
    .lang-cta-buttons {
        display: flex;
        justify-content: center;
        gap: 14px;
        flex-wrap: wrap;
    }
    .lang-btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, #6c5ce7, #0984e3);
        color: #fff;
        font-family: 'Inter', sans-serif;
        font-size: 0.9rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        padding: 14px 30px;
        border-radius: 10px;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: transform 0.25s, box-shadow 0.25s;
    }
    .lang-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(108,92,231,0.3);
    }
    .lang-btn-outline {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: transparent;
        color: #a29bfe;
        font-family: 'Inter', sans-serif;
        font-size: 0.9rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        padding: 13px 30px;
        border-radius: 10px;
        text-decoration: none;
        border: 1px solid rgba(108,92,231,0.4);
        cursor: pointer;
        transition: background 0.25s, transform 0.25s;
    }
    .lang-btn-outline:hover {
        background: rgba(108,92,231,0.1);
        transform: translateY(-2px);
    }

    /* ── No results state ── */
    #langNoResults {
        display: none;
        text-align: center;
        padding: 60px 20px;
        color: var(--al-text-secondary, #8a8ab0);
    }
    #langNoResults .no-results-icon { font-size: 3rem; margin-bottom: 12px; }
    #langNoResults p { font-size: 1rem; }

    /* ═══ Responsive ═══ */
    @media (max-width: 768px) {
        .lang-hero { padding: 80px 16px 40px; }
        .lang-stats { gap: 8px 24px; }
        .lang-stat-num { font-size: 1.7rem; }
        .lang-controls { top: 0; }
        .lang-controls-inner { flex-direction: column; gap: 8px; }
        .lang-search-wrap { min-width: 100%; }
        .lang-result-count { text-align: center; }
        .lang-main { padding: 24px 16px 60px; }
        .lang-cat { margin-bottom: 32px; }
        .lang-cat-header { gap: 10px; }
        .lang-cat-title { font-size: 1.05rem; }
        .lang-cta { padding: 40px 16px 60px; }
    }

    @media (max-width: 480px) {
        .lang-hero h1 { font-size: 1.8rem; }
        .lang-hero p { font-size: 0.92rem; }
        .lang-stat-num { font-size: 1.4rem; }
        .lang-tag { font-size: 0.75rem; padding: 6px 11px; gap: 5px; }
        .lang-tags { gap: 7px; }
        .lang-cta h2 { font-size: 1.3rem; }
        .lang-btn-primary,
        .lang-btn-outline { width: 100%; justify-content: center; padding: 14px 20px; }
    }

    @media (pointer: coarse) {
        .lang-tag { padding: 10px 16px; font-size: 0.85rem; min-height: 44px; }
        #langSearchInput { padding: 14px 14px 14px 42px; font-size: 1rem; }
        .lang-btn-primary,
        .lang-btn-outline { min-height: 48px; }
    }
</style>

    <!-- Hero -->
    <section class="lang-hero">
        <div class="lang-badge"><i class="fas fa-code" style="margin-right:6px;"></i> Full-Stack Developer</div>

        <h1><?php echo $totalCount; ?>+ Languages &amp; Technologies</h1>
        <p>
            No matter what you're building or what stack you're on — Alfred's got you covered.
            Explore every language and technology Alfred works with below.
        </p>

        <div class="lang-stats">
            <div class="lang-stat">
                <span class="lang-stat-num" id="totalCount"><?php echo $totalCount; ?>+</span>
                <span class="lang-stat-label">Languages</span>
            </div>
            <div class="lang-stat">
                <span class="lang-stat-num"><?php echo count($languages); ?></span>
                <span class="lang-stat-label">Categories</span>
            </div>
            <div class="lang-stat">
                <span class="lang-stat-num">∞</span>
                <span class="lang-stat-label">Possibilities</span>
            </div>
        </div>
    </section>

    <!-- Search & Filter Bar -->
    <div class="lang-controls">
        <div class="lang-controls-inner">
            <div class="lang-search-wrap">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
                <input type="text" id="langSearchInput" placeholder="Search a language or technology..." autocomplete="off" data-lang-total="<?php echo $totalCount; ?>">
            </div>
            <div class="lang-result-count" id="langResultCount">
                Showing <span id="langVisibleCount"><?php echo $totalCount; ?></span> of <?php echo $totalCount; ?> languages
            </div>
        </div>
    </div>

    <!-- Language Grid -->
    <main class="lang-main" id="langMainContent">

        <?php foreach ($languages as $category => $langs): ?>
        <section class="lang-cat" data-category="<?php echo htmlspecialchars($category); ?>">
            <div class="lang-cat-header">
                <h2 class="lang-cat-title"><?php echo htmlspecialchars($category); ?></h2>
                <span class="lang-cat-count"><?php echo count($langs); ?></span>
                <div class="lang-cat-line"></div>
            </div>
            <div class="lang-tags">
                <?php foreach ($langs as $lang): ?>
                <span class="lang-tag" data-name="<?php echo htmlspecialchars(strtolower($lang)); ?>">
                    <span class="dot"></span>
                    <?php echo htmlspecialchars($lang); ?>
                </span>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>

        <div id="langNoResults">
            <div class="no-results-icon">🔍</div>
            <p>No languages found matching your search. Try a different term!</p>
        </div>

    </main>

    <!-- CTA Section -->
    <section class="lang-cta">
        <div class="lang-cta-inner">
            <span class="lang-cta-icon">🚀</span>
            <h2>Don't see your stack? Let's talk.</h2>
            <p>
                This list covers <?php echo $totalCount; ?>+ languages but the world of code never stops growing.
                Whatever you're building, Alfred can help you get it done — fast, clean, and right.
            </p>
            <div class="lang-cta-buttons">
                <a href="mailto:support@gositeme.com" class="lang-btn-primary">
                    <i class="fas fa-envelope"></i>
                    Get in Touch
                </a>
                <a href="/" class="lang-btn-outline">
                    <i class="fas fa-arrow-left"></i>
                    Back to Home
                </a>
            </div>
        </div>
    </section>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>

    <script src="/assets/js/languages-engine.js" defer></script>

</body>
</html>
