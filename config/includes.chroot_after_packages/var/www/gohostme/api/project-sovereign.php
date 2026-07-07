<?php
/**
 * PROJECT SOVEREIGN — Autonomous AI Development Program
 * CLASSIFICATION: ULTRA SECRET — Commander Eyes Only
 * 50 Dedicated Agents — Build AI that Surpasses Current Models
 * 
 * Goal: Build a proprietary AI system owned entirely by GoSiteMe
 * that achieves and exceeds state-of-the-art capabilities
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../includes/commander-auth.api.inc.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');

$client_id = getCommanderId();
if (!$client_id) {
    echo json_encode(['error' => 'ACCESS DENIED — Classification: ULTRA SECRET']);
    exit;
}

$action = $_REQUEST['action'] ?? 'status';
$db = getDB();

// ═══ 50 SOVEREIGN AI AGENTS ═══
function getSovereignAgents() {
    return [
        // ── Division 1: Architecture & Core Design (8 agents) ──
        ['name' => 'Architect-S', 'division' => 'architecture', 'role' => 'Division Lead — Chief AI Architect', 'specialty' => 'Model architecture design, attention mechanisms, training stability', 'rank' => 'Director'],
        ['name' => 'Transformer', 'division' => 'architecture', 'role' => 'Attention Mechanism Engineer', 'specialty' => 'Multi-head attention, sparse attention, flash attention, linear attention research', 'rank' => 'Senior'],
        ['name' => 'Embed', 'division' => 'architecture', 'role' => 'Embedding Specialist', 'specialty' => 'Token embedding, positional encoding, rotary position embeddings (RoPE)', 'rank' => 'Senior'],
        ['name' => 'MoE', 'division' => 'architecture', 'role' => 'Mixture of Experts', 'specialty' => 'Sparse MoE routing, expert selection, load balancing for efficient scaling', 'rank' => 'Mid'],
        ['name' => 'Memory', 'division' => 'architecture', 'role' => 'Long-Context Memory', 'specialty' => 'Extended context windows, memory compression, retrieval-augmented generation', 'rank' => 'Mid'],
        ['name' => 'Norm', 'division' => 'architecture', 'role' => 'Normalization Research', 'specialty' => 'RMSNorm, LayerNorm, DeepNorm — training stability at scale', 'rank' => 'Mid'],
        ['name' => 'Parallel', 'division' => 'architecture', 'role' => 'Parallelism Engineer', 'specialty' => 'Tensor parallelism, pipeline parallelism, ZeRO optimizer for multi-GPU', 'rank' => 'Junior'],
        ['name' => 'Compress', 'division' => 'architecture', 'role' => 'Model Compression', 'specialty' => 'Quantization (INT4/INT8/FP8), pruning, knowledge distillation', 'rank' => 'Junior'],

        // ── Division 2: Training & Data Pipeline (8 agents) ──
        ['name' => 'Trainer', 'division' => 'training', 'role' => 'Division Lead — Training Director', 'specialty' => 'Pre-training pipelines, curriculum learning, scaling laws', 'rank' => 'Director'],
        ['name' => 'DataForge', 'division' => 'training', 'role' => 'Data Engineer', 'specialty' => 'Web crawling, data cleaning, deduplication, quality filtering', 'rank' => 'Senior'],
        ['name' => 'Tokenizer', 'division' => 'training', 'role' => 'Tokenization Expert', 'specialty' => 'BPE, SentencePiece, multilingual tokenization, vocabulary optimization', 'rank' => 'Senior'],
        ['name' => 'Optimizer', 'division' => 'training', 'role' => 'Optimization Specialist', 'specialty' => 'AdamW, LAMB, learning rate schedules, gradient accumulation strategies', 'rank' => 'Mid'],
        ['name' => 'Curriculum', 'division' => 'training', 'role' => 'Curriculum Designer', 'specialty' => 'Training data ordering, difficulty progression, topic balancing', 'rank' => 'Mid'],
        ['name' => 'Checkpoint', 'division' => 'training', 'role' => 'Checkpoint Manager', 'specialty' => 'Training state management, fault tolerance, checkpoint merging', 'rank' => 'Mid'],
        ['name' => 'SynData', 'division' => 'training', 'role' => 'Synthetic Data Generation', 'specialty' => 'AI-generated training data, self-play, instruction bootstrapping', 'rank' => 'Junior'],
        ['name' => 'Cleaner', 'division' => 'training', 'role' => 'Data Quality Analyst', 'specialty' => 'Toxic content filtering, bias detection, data quality metrics', 'rank' => 'Junior'],

        // ── Division 3: Alignment & Safety (7 agents) ──
        ['name' => 'Align', 'division' => 'alignment', 'role' => 'Division Lead — Alignment Director', 'specialty' => 'RLHF, DPO, constitutional AI, value alignment research', 'rank' => 'Director'],
        ['name' => 'RLHF', 'division' => 'alignment', 'role' => 'RLHF Specialist', 'specialty' => 'Reward modeling, PPO training, KL-divergence constraints', 'rank' => 'Senior'],
        ['name' => 'DPO', 'division' => 'alignment', 'role' => 'Direct Preference Optimization', 'specialty' => 'DPO, IPO, ORPO — next-gen alignment without reward models', 'rank' => 'Senior'],
        ['name' => 'RedTeam', 'division' => 'alignment', 'role' => 'Red Team Lead', 'specialty' => 'Adversarial testing, jailbreak resistance, prompt injection defense', 'rank' => 'Mid'],
        ['name' => 'Ethics', 'division' => 'alignment', 'role' => 'Ethics Researcher', 'specialty' => 'Responsible AI development, harm prevention, transparency', 'rank' => 'Mid'],
        ['name' => 'Guardrail', 'division' => 'alignment', 'role' => 'Guardrails Engineer', 'specialty' => 'Output filtering, content classification, safety classifiers', 'rank' => 'Junior'],
        ['name' => 'Eval', 'division' => 'alignment', 'role' => 'Evaluation Specialist', 'specialty' => 'Benchmark design, automated safety testing, alignment scoring', 'rank' => 'Junior'],

        // ── Division 4: Inference & Deployment (7 agents) ──
        ['name' => 'Deploy', 'division' => 'inference', 'role' => 'Division Lead — Deployment Architect', 'specialty' => 'Inference optimization, serving infrastructure, vLLM/TGI', 'rank' => 'Director'],
        ['name' => 'KVCache', 'division' => 'inference', 'role' => 'KV Cache Specialist', 'specialty' => 'PagedAttention, KV cache compression, speculative decoding', 'rank' => 'Senior'],
        ['name' => 'Batch', 'division' => 'inference', 'role' => 'Batching Engineer', 'specialty' => 'Continuous batching, dynamic batching, throughput optimization', 'rank' => 'Senior'],
        ['name' => 'Edge', 'division' => 'inference', 'role' => 'Edge Deployment', 'specialty' => 'On-device inference, ONNX, TensorRT, mobile deployment', 'rank' => 'Mid'],
        ['name' => 'Quantize', 'division' => 'inference', 'role' => 'Quantization Engineer', 'specialty' => 'GPTQ, AWQ, GGUF quantization for efficient inference', 'rank' => 'Mid'],
        ['name' => 'Stream', 'division' => 'inference', 'role' => 'Streaming Systems', 'specialty' => 'Token streaming, WebSocket inference, real-time response', 'rank' => 'Junior'],
        ['name' => 'Monitor', 'division' => 'inference', 'role' => 'Production Monitor', 'specialty' => 'Latency tracking, error rates, A/B testing, model versioning', 'rank' => 'Junior'],

        // ── Division 5: Multimodal & Vision (6 agents) ──
        ['name' => 'MultiModal', 'division' => 'multimodal', 'role' => 'Division Lead — Multimodal Director', 'specialty' => 'Vision-language models, image understanding, audio processing', 'rank' => 'Director'],
        ['name' => 'VisionEnc', 'division' => 'multimodal', 'role' => 'Vision Encoder', 'specialty' => 'ViT, SigLIP, CLIP — visual feature extraction and alignment', 'rank' => 'Senior'],
        ['name' => 'AudioProc', 'division' => 'multimodal', 'role' => 'Audio Processing', 'specialty' => 'Whisper-style ASR, TTS, audio understanding, music generation', 'rank' => 'Senior'],
        ['name' => 'ImageGen', 'division' => 'multimodal', 'role' => 'Image Generation', 'specialty' => 'Diffusion models, consistency models, image editing capabilities', 'rank' => 'Mid'],
        ['name' => 'VideoProc', 'division' => 'multimodal', 'role' => 'Video Understanding', 'specialty' => 'Video tokenization, temporal understanding, video generation', 'rank' => 'Mid'],
        ['name' => 'Fusion', 'division' => 'multimodal', 'role' => 'Cross-Modal Fusion', 'specialty' => 'Aligning text, image, audio, and video in shared embedding space', 'rank' => 'Junior'],

        // ── Division 6: Tool Use & Agentic Capabilities (5 agents) ──
        ['name' => 'Agent-S', 'division' => 'agentic', 'role' => 'Division Lead — Agentic AI Director', 'specialty' => 'Function calling, tool use, multi-step reasoning, planning', 'rank' => 'Director'],
        ['name' => 'ToolCall', 'division' => 'agentic', 'role' => 'Function Calling Engineer', 'specialty' => 'Structured output, JSON mode, reliable tool invocation', 'rank' => 'Senior'],
        ['name' => 'Planner', 'division' => 'agentic', 'role' => 'Planning & Reasoning', 'specialty' => 'Chain-of-thought, tree-of-thought, multi-step task decomposition', 'rank' => 'Mid'],
        ['name' => 'CodeGen', 'division' => 'agentic', 'role' => 'Code Generation', 'specialty' => 'Code understanding, generation, debugging, multi-language support', 'rank' => 'Mid'],
        ['name' => 'WebAgent', 'division' => 'agentic', 'role' => 'Web Interaction Agent', 'specialty' => 'Browser automation, web scraping, API interaction, form filling', 'rank' => 'Junior'],

        // ── Division 7: Infrastructure & Compute (5 agents) ──
        ['name' => 'Infra', 'division' => 'infrastructure', 'role' => 'Division Lead — Infrastructure Director', 'specialty' => 'GPU cluster management, distributed training, cost optimization', 'rank' => 'Director'],
        ['name' => 'Cluster', 'division' => 'infrastructure', 'role' => 'Cluster Manager', 'specialty' => 'Multi-node GPU coordination, NCCL, InfiniBand networking', 'rank' => 'Senior'],
        ['name' => 'Storage', 'division' => 'infrastructure', 'role' => 'Data Storage Engineer', 'specialty' => 'High-throughput data loading, distributed storage, caching', 'rank' => 'Mid'],
        ['name' => 'CostOpt', 'division' => 'infrastructure', 'role' => 'Cost Optimizer', 'specialty' => 'Cloud vs on-prem analysis, spot instances, training cost reduction', 'rank' => 'Mid'],
        ['name' => 'Backup-S', 'division' => 'infrastructure', 'role' => 'Backup & Recovery', 'specialty' => 'Training checkpoint backup, model versioning, disaster recovery', 'rank' => 'Junior'],

        // ── Division 8: Intelligence & Competitive Analysis (4 agents) ──
        ['name' => 'Intel-S', 'division' => 'intelligence', 'role' => 'Division Lead — AI Intelligence Chief', 'specialty' => 'Competitive analysis, paper tracking, breakthrough monitoring', 'rank' => 'Director'],
        ['name' => 'PaperTracker', 'division' => 'intelligence', 'role' => 'Research Paper Analyst', 'specialty' => 'ArXiv monitoring, key paper analysis, technique extraction', 'rank' => 'Senior'],
        ['name' => 'Benchmark', 'division' => 'intelligence', 'role' => 'Benchmark Analyst', 'specialty' => 'MMLU, HumanEval, GPQA, competitive ranking, capabilities gap analysis', 'rank' => 'Mid'],
        ['name' => 'Scout', 'division' => 'intelligence', 'role' => 'Industry Scout', 'specialty' => 'OpenAI, Anthropic, Google, Meta, Mistral — tracking all competitors', 'rank' => 'Junior'],
    ];
}

// ═══ DEVELOPMENT PHASES ═══
function getSovereignPhases() {
    return [
        [
            'phase' => 1, 'name' => 'Foundation',
            'duration' => '3 months',
            'objectives' => [
                'Select base architecture (Llama 3, Mistral, or custom transformer)',
                'Build data pipeline — 2T+ token dataset from web, books, code, science',
                'Set up training infrastructure — evaluate GPU cloud options',
                'Create tokenizer optimized for English + code + scientific notation',
                'Define evaluation benchmarks and target scores'
            ],
            'compute_estimate' => '8x A100 80GB cluster for initial experiments',
            'status' => 'planning'
        ],
        [
            'phase' => 2, 'name' => 'Pre-Training',
            'duration' => '6 months',
            'objectives' => [
                'Pre-train base model from scratch or continue-train from open weights',
                'Target: 70B parameter model with 128K context window',
                'Implement MoE for efficiency — 70B total, 12B active per token',
                'Curriculum learning: general → technical → scientific → reasoning',
                'Checkpoint evaluation every 1000 steps against benchmark suite'
            ],
            'compute_estimate' => '32-64x H100 for 2-4 weeks continuous training',
            'status' => 'planning'
        ],
        [
            'phase' => 3, 'name' => 'Fine-Tuning & Alignment',
            'duration' => '3 months',
            'objectives' => [
                'Instruction tuning on curated 1M+ example dataset',
                'RLHF/DPO alignment for helpfulness, honesty, harmlessness',
                'Specialized fine-tunes: coding, science, reasoning, tools, ZPE research',
                'Red team testing and safety evaluation',
                'Build Constitutional AI layer — self-correcting behavior'
            ],
            'compute_estimate' => '8x H100 for fine-tuning runs',
            'status' => 'planning'
        ],
        [
            'phase' => 4, 'name' => 'Multimodal & Agentic',
            'duration' => '4 months',
            'objectives' => [
                'Add vision encoder (SigLIP/ViT-L) with cross-attention fusion',
                'Audio understanding via Whisper-style encoder',
                'Function calling and tool use training',
                'Multi-step agentic capabilities — plan → execute → verify',
                'Code generation with execution feedback loop'
            ],
            'compute_estimate' => '16x H100 for multimodal training',
            'status' => 'planning'
        ],
        [
            'phase' => 5, 'name' => 'Deployment & Surpass',
            'duration' => 'Ongoing',
            'objectives' => [
                'Deploy Sovereign AI as Alfred replacement/upgrade',
                'Achieve GPT-4/Claude-level on key benchmarks',
                'Self-hosted inference — fully owned infrastructure',
                'Continuous improvement cycle — weekly fine-tuning from feedback',
                'SURPASS target: beat Anthropic Claude on reasoning, coding, and science benchmarks'
            ],
            'compute_estimate' => '4-8x H100 for inference serving',
            'status' => 'planning'
        ]
    ];
}

// ═══ RESEARCH TOPICS ═══
function getSovereignTopics() {
    return [
        ['topic' => 'Architecture Selection: Custom vs Fork', 'category' => 'architecture', 'priority' => 'critical', 'evidence' => 85, 'notes' => 'Analysis: Custom transformer from scratch vs continuing from Llama 3/Mistral. Recommendation: Start with Llama 3.3 70B architecture, modify attention mechanism, then diverge.'],
        ['topic' => 'Mixture of Experts Routing', 'category' => 'architecture', 'priority' => 'critical', 'evidence' => 90, 'notes' => 'MoE allows 70B total params with only 12B active per token. Key: Expert load balancing, auxiliary loss, top-k routing. Mistral/Mixtral approach proven.'],
        ['topic' => 'Extended Context via RoPE Scaling', 'category' => 'architecture', 'priority' => 'high', 'evidence' => 90, 'notes' => 'YaRN/NTK-aware RoPE scaling extends context from 4K to 128K+. Proven technique — no architecture changes needed.'],
        ['topic' => 'Data Pipeline Architecture', 'category' => 'training', 'priority' => 'critical', 'evidence' => 95, 'notes' => 'Common Crawl + RedPajama + StarCoder + ArXiv + Wikipedia + Books. Dedup with MinHash. Quality filter with classifier. Target: 2-4T tokens.'],
        ['topic' => 'Training Cost Optimization', 'category' => 'infrastructure', 'priority' => 'critical', 'evidence' => 90, 'notes' => 'Options: Lambda Labs ($1.10/hr H100), RunPod ($2.49/hr), AWS p5 ($32/hr), on-prem (~$25K/GPU one-time). Recommendation: Lambda or RunPod for training, on-prem for inference.'],
        ['topic' => 'DPO vs RLHF for Alignment', 'category' => 'alignment', 'priority' => 'high', 'evidence' => 85, 'notes' => 'DPO eliminates reward model, simpler training loop. ORPO combines instruction tuning + preference alignment in one step. Recommendation: DPO for initial, RLHF for refinement.'],
        ['topic' => 'Flash Attention 3 Integration', 'category' => 'architecture', 'priority' => 'high', 'evidence' => 95, 'notes' => 'FlashAttention-3 on H100: 2-3x faster than FA2. Supports FP8, variable-length sequences. Critical for training and inference speed.'],
        ['topic' => 'Speculative Decoding Inference', 'category' => 'inference', 'priority' => 'high', 'evidence' => 85, 'notes' => 'Use small draft model (3B) to propose tokens, large model (70B) verifies in parallel. 2-4x inference speedup with identical quality.'],
        ['topic' => 'Synthetic Data from Claude/GPT', 'category' => 'training', 'priority' => 'high', 'evidence' => 80, 'notes' => 'Use Anthropic/OpenAI APIs to generate high-quality training data. Instruction sets, reasoning chains, code solutions. Bootstrap quality then self-improve.'],
        ['topic' => 'Constitutional AI Self-Correction', 'category' => 'alignment', 'priority' => 'high', 'evidence' => 80, 'notes' => 'Model critiques own output against principles, revises. No human feedback needed for iteration. Scales alignment without annotation costs.'],
        ['topic' => 'ZPE Research Specialization', 'category' => 'specialization', 'priority' => 'critical', 'evidence' => 70, 'notes' => 'Sovereign will have specialized knowledge of ZPE, free energy, and mech suit engineering that NO other AI has. Private training data from Prometheus and Titan programs.'],
        ['topic' => 'On-Prem GPU Server Build', 'category' => 'infrastructure', 'priority' => 'high', 'evidence' => 90, 'notes' => 'Target: 4x RTX 4090 server for inference (~$10K). For training: rent cloud H100s. For deployment: run Sovereign on own hardware — zero API costs.'],
        ['topic' => 'Progressive Fine-Tuning Strategy', 'category' => 'training', 'priority' => 'medium', 'evidence' => 85, 'notes' => 'Fine-tune in stages: general assistant → coding specialist → science expert → ZPE researcher → TITAN engineer. Each builds on previous knowledge.'],
        ['topic' => 'Benchmark Suite Design', 'category' => 'evaluation', 'priority' => 'high', 'evidence' => 95, 'notes' => 'MMLU, HumanEval, GPQA, MATH, ARC, HellaSwag + custom: ZPE_Knowledge_Test, Circuit_Design_Test, Code_GoSiteMe_Test. Must beat Claude 3.5 Sonnet on majority.'],
        ['topic' => 'Open-Weight vs Proprietary Strategy', 'category' => 'intelligence', 'priority' => 'medium', 'evidence' => 80, 'notes' => 'Keep Sovereign proprietary for competitive advantage. Open-source a smaller "Community Edition" for goodwill and ecosystem building. Never release ZPE training data.'],
    ];
}

switch ($action) {

    case 'status':
        $agents = getSovereignAgents();
        $phases = getSovereignPhases();
        $topics = getSovereignTopics();
        
        $divisions = [];
        foreach ($agents as $a) {
            $div = $a['division'];
            if (!isset($divisions[$div])) $divisions[$div] = ['count' => 0, 'director' => '', 'agents' => []];
            $divisions[$div]['count']++;
            $divisions[$div]['agents'][] = $a['name'];
            if ($a['rank'] === 'Director') $divisions[$div]['director'] = $a['name'];
        }
        
        $dbStats = [];
        try {
            $dbStats['agents_deployed'] = $db->query("SELECT COUNT(*) FROM sovereign_agents")->fetchColumn();
            $dbStats['topics_active'] = $db->query("SELECT COUNT(*) FROM sovereign_research WHERE status != 'eliminated'")->fetchColumn();
        } catch (Exception $e) {
            $dbStats = ['status' => 'not_seeded'];
        }
        
        jsonResponse([
            'program' => 'PROJECT SOVEREIGN',
            'classification' => 'ULTRA SECRET',
            'codename' => 'SOVEREIGN',
            'objective' => 'Build proprietary AI system surpassing Anthropic Claude and OpenAI GPT — fully owned by GoSiteMe',
            'total_agents' => count($agents),
            'divisions' => $divisions,
            'phases' => count($phases),
            'research_topics' => count($topics),
            'db_status' => $dbStats,
            'target' => 'Beat Claude 3.5 Sonnet on MMLU, HumanEval, GPQA, MATH benchmarks',
            'key_advantage' => 'Exclusive ZPE/Free Energy/Mech Suit training data that no other AI has',
        ]);
        break;

    case 'agents':
        $division = $_REQUEST['division'] ?? null;
        $agents = getSovereignAgents();
        if ($division) $agents = array_values(array_filter($agents, fn($a) => $a['division'] === $division));
        jsonResponse(['agents' => $agents, 'total' => count($agents)]);
        break;

    case 'phases':
        jsonResponse(['phases' => getSovereignPhases()]);
        break;

    case 'research':
        $topics = getSovereignTopics();
        $category = $_REQUEST['category'] ?? null;
        if ($category) $topics = array_values(array_filter($topics, fn($t) => $t['category'] === $category));
        jsonResponse(['topics' => $topics]);
        break;

    case 'seed':
        $db->exec("CREATE TABLE IF NOT EXISTS sovereign_agents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            division VARCHAR(50) NOT NULL,
            role VARCHAR(100),
            specialty TEXT,
            rank VARCHAR(20),
            status ENUM('active','standby','deployed','offline') DEFAULT 'active',
            findings_count INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $db->exec("CREATE TABLE IF NOT EXISTS sovereign_research (
            id INT AUTO_INCREMENT PRIMARY KEY,
            topic VARCHAR(200) NOT NULL,
            category VARCHAR(50),
            priority ENUM('critical','high','medium','low') DEFAULT 'medium',
            evidence INT DEFAULT 0,
            notes TEXT,
            status ENUM('active','verified','tested','proven','eliminated') DEFAULT 'active',
            assigned_agent VARCHAR(50),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $db->exec("CREATE TABLE IF NOT EXISTS sovereign_phases (
            id INT AUTO_INCREMENT PRIMARY KEY,
            phase_num INT NOT NULL,
            name VARCHAR(50),
            duration VARCHAR(30),
            objectives JSON,
            compute_estimate VARCHAR(100),
            status ENUM('planning','active','testing','complete') DEFAULT 'planning',
            progress INT DEFAULT 0,
            started_at DATETIME,
            completed_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY (phase_num)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $agents = getSovereignAgents();
        $stmt = $db->prepare("INSERT IGNORE INTO sovereign_agents (name, division, role, specialty, rank) VALUES (?, ?, ?, ?, ?)");
        foreach ($agents as $a) $stmt->execute([$a['name'], $a['division'], $a['role'], $a['specialty'], $a['rank']]);
        
        $topics = getSovereignTopics();
        $stmt = $db->prepare("INSERT IGNORE INTO sovereign_research (topic, category, priority, evidence, notes) VALUES (?, ?, ?, ?, ?)");
        foreach ($topics as $t) $stmt->execute([$t['topic'], $t['category'], $t['priority'], $t['evidence'], $t['notes']]);
        
        $phases = getSovereignPhases();
        $stmt = $db->prepare("INSERT IGNORE INTO sovereign_phases (phase_num, name, duration, objectives, compute_estimate, status) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($phases as $p) $stmt->execute([$p['phase'], $p['name'], $p['duration'], json_encode($p['objectives']), $p['compute_estimate'], $p['status']]);
        
        jsonResponse([
            'success' => true,
            'program' => 'PROJECT SOVEREIGN',
            'seeded' => [
                'agents' => count($agents),
                'research_topics' => count($topics),
                'phases' => count($phases),
                'tables' => ['sovereign_agents', 'sovereign_research', 'sovereign_phases']
            ],
            'message' => 'PROJECT SOVEREIGN initialized — 50 agents deployed, 15 research topics, 5 development phases'
        ]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action', 'available' => ['status','agents','phases','research','seed']], 400);
}
