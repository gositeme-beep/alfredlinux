<?php
/**
 * GoSiteMe Search Quality Test Fleet
 * 500 agents testing search accuracy across 50 categories
 * Reports results to agent autonomy system
 * v1.0
 */

define('GOSITEME_API', true);
require_once dirname(__DIR__) . '/api/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? 'status';
$secret = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
$validSecret = defined('INTERNAL_SECRET') ? INTERNAL_SECRET : '';

// ── Test Query Database ─────────────────────────────────────────
// 50 categories × 10 queries each = 500 test queries
$testCategories = [
    'technology' => [
        ['q' => 'how to build a website', 'expect' => ['tutorial', 'html', 'css', 'web']],
        ['q' => 'best programming languages 2025', 'expect' => ['python', 'javascript', 'rust']],
        ['q' => 'linux command line basics', 'expect' => ['terminal', 'bash', 'commands']],
        ['q' => 'what is machine learning', 'expect' => ['ai', 'algorithm', 'data', 'model']],
        ['q' => 'docker container tutorial', 'expect' => ['container', 'image', 'dockerfile']],
        ['q' => 'react vs vue comparison', 'expect' => ['react', 'vue', 'framework', 'javascript']],
        ['q' => 'api rest design best practices', 'expect' => ['endpoint', 'http', 'json', 'rest']],
        ['q' => 'git version control guide', 'expect' => ['git', 'commit', 'branch', 'merge']],
        ['q' => 'cloud computing explained', 'expect' => ['cloud', 'aws', 'azure', 'server']],
        ['q' => 'cybersecurity best practices', 'expect' => ['security', 'password', 'encryption']],
    ],
    'science' => [
        ['q' => 'how does photosynthesis work', 'expect' => ['plant', 'light', 'chlorophyll', 'energy']],
        ['q' => 'theory of relativity explained', 'expect' => ['einstein', 'space', 'time', 'physics']],
        ['q' => 'dna structure and function', 'expect' => ['dna', 'gene', 'helix', 'nucleotide']],
        ['q' => 'periodic table of elements', 'expect' => ['element', 'atomic', 'chemical']],
        ['q' => 'black holes in space', 'expect' => ['gravity', 'singularity', 'space', 'light']],
        ['q' => 'climate change causes', 'expect' => ['carbon', 'greenhouse', 'temperature']],
        ['q' => 'evolution by natural selection', 'expect' => ['darwin', 'species', 'adaptation']],
        ['q' => 'quantum mechanics basics', 'expect' => ['quantum', 'particle', 'wave', 'physics']],
        ['q' => 'human brain anatomy', 'expect' => ['brain', 'neuron', 'cortex', 'nervous']],
        ['q' => 'solar system planets', 'expect' => ['planet', 'mars', 'jupiter', 'orbit']],
    ],
    'health' => [
        ['q' => 'symptoms of diabetes', 'expect' => ['blood', 'sugar', 'insulin', 'glucose']],
        ['q' => 'benefits of exercise', 'expect' => ['health', 'fitness', 'heart', 'muscle']],
        ['q' => 'healthy diet tips', 'expect' => ['nutrition', 'food', 'vitamin', 'diet']],
        ['q' => 'mental health awareness', 'expect' => ['mental', 'anxiety', 'depression', 'therapy']],
        ['q' => 'covid vaccine information', 'expect' => ['vaccine', 'virus', 'immune', 'dose']],
        ['q' => 'how to lower blood pressure', 'expect' => ['blood', 'pressure', 'sodium', 'heart']],
        ['q' => 'sleep hygiene tips', 'expect' => ['sleep', 'rest', 'insomnia', 'melatonin']],
        ['q' => 'first aid basics', 'expect' => ['first aid', 'wound', 'emergency', 'cpr']],
        ['q' => 'allergy symptoms and treatment', 'expect' => ['allergy', 'histamine', 'antihistamine']],
        ['q' => 'yoga for beginners', 'expect' => ['yoga', 'pose', 'flexibility', 'mindfulness']],
    ],
    'finance' => [
        ['q' => 'how to invest in stocks', 'expect' => ['stock', 'invest', 'market', 'portfolio']],
        ['q' => 'cryptocurrency explained', 'expect' => ['bitcoin', 'blockchain', 'crypto', 'wallet']],
        ['q' => 'budgeting for beginners', 'expect' => ['budget', 'saving', 'expense', 'money']],
        ['q' => 'what is a 401k', 'expect' => ['retirement', '401k', 'savings', 'employer']],
        ['q' => 'credit score explained', 'expect' => ['credit', 'score', 'report', 'fico']],
        ['q' => 'mortgage rates today', 'expect' => ['mortgage', 'rate', 'home', 'loan']],
        ['q' => 'tax deductions for self employed', 'expect' => ['tax', 'deduction', 'business', 'expense']],
        ['q' => 'inflation explained', 'expect' => ['inflation', 'price', 'economy', 'purchasing']],
        ['q' => 'how to start a business', 'expect' => ['business', 'startup', 'plan', 'entrepreneur']],
        ['q' => 'passive income ideas', 'expect' => ['income', 'passive', 'invest', 'revenue']],
    ],
    'education' => [
        ['q' => 'best online learning platforms', 'expect' => ['course', 'learn', 'online', 'education']],
        ['q' => 'how to write an essay', 'expect' => ['essay', 'writing', 'thesis', 'paragraph']],
        ['q' => 'study tips for students', 'expect' => ['study', 'learn', 'memory', 'focus']],
        ['q' => 'history of world war 2', 'expect' => ['war', 'ally', 'axis', 'nazi', '1945']],
        ['q' => 'calculus for beginners', 'expect' => ['calculus', 'derivative', 'integral', 'math']],
        ['q' => 'learn spanish online free', 'expect' => ['spanish', 'language', 'learn', 'lesson']],
        ['q' => 'SAT preparation guide', 'expect' => ['sat', 'test', 'score', 'college']],
        ['q' => 'philosophy major overview', 'expect' => ['philosophy', 'ethics', 'logic', 'thinking']],
        ['q' => 'distance learning advantages', 'expect' => ['remote', 'online', 'learning', 'education']],
        ['q' => 'scholarship application tips', 'expect' => ['scholarship', 'financial', 'aid', 'application']],
    ],
    'cooking' => [
        ['q' => 'easy chicken recipes', 'expect' => ['chicken', 'recipe', 'cook', 'oven']],
        ['q' => 'how to make sourdough bread', 'expect' => ['bread', 'dough', 'yeast', 'flour']],
        ['q' => 'vegan meal ideas', 'expect' => ['vegan', 'plant', 'recipe', 'meal']],
        ['q' => 'best pasta recipes', 'expect' => ['pasta', 'sauce', 'italian', 'cook']],
        ['q' => 'how to grill steak perfectly', 'expect' => ['steak', 'grill', 'temperature', 'meat']],
        ['q' => 'baking tips for beginners', 'expect' => ['baking', 'oven', 'flour', 'recipe']],
        ['q' => 'mediterranean diet recipes', 'expect' => ['mediterranean', 'olive', 'fish', 'vegetable']],
        ['q' => 'healthy smoothie recipes', 'expect' => ['smoothie', 'fruit', 'blend', 'healthy']],
        ['q' => 'how to make sushi at home', 'expect' => ['sushi', 'rice', 'fish', 'roll']],
        ['q' => 'meal prep for the week', 'expect' => ['meal', 'prep', 'container', 'plan']],
    ],
    'travel' => [
        ['q' => 'best places to visit in Europe', 'expect' => ['europe', 'paris', 'rome', 'travel']],
        ['q' => 'cheap flights booking tips', 'expect' => ['flight', 'airline', 'booking', 'cheap']],
        ['q' => 'travel insurance comparison', 'expect' => ['insurance', 'travel', 'coverage', 'policy']],
        ['q' => 'backpacking gear essentials', 'expect' => ['backpack', 'gear', 'hiking', 'tent']],
        ['q' => 'visa requirements for US citizens', 'expect' => ['visa', 'passport', 'travel', 'country']],
        ['q' => 'best beaches in the world', 'expect' => ['beach', 'coast', 'sand', 'ocean']],
        ['q' => 'road trip planning guide', 'expect' => ['road', 'trip', 'drive', 'route']],
        ['q' => 'japan travel guide', 'expect' => ['japan', 'tokyo', 'culture', 'temple']],
        ['q' => 'safari tours in Africa', 'expect' => ['safari', 'africa', 'wildlife', 'animal']],
        ['q' => 'cruise ship vacation tips', 'expect' => ['cruise', 'ship', 'port', 'cabin']],
    ],
    'legal' => [
        ['q' => 'how to file a lawsuit', 'expect' => ['lawsuit', 'court', 'legal', 'attorney']],
        ['q' => 'tenant rights explained', 'expect' => ['tenant', 'landlord', 'rent', 'lease']],
        ['q' => 'intellectual property basics', 'expect' => ['patent', 'trademark', 'copyright', 'ip']],
        ['q' => 'small claims court process', 'expect' => ['court', 'claim', 'judge', 'filing']],
        ['q' => 'privacy laws explained', 'expect' => ['privacy', 'gdpr', 'data', 'regulation']],
        ['q' => 'employment law basics', 'expect' => ['employment', 'worker', 'right', 'labor']],
        ['q' => 'what is a power of attorney', 'expect' => ['attorney', 'power', 'legal', 'agent']],
        ['q' => 'immigration law overview', 'expect' => ['immigration', 'visa', 'citizenship']],
        ['q' => 'how to write a will', 'expect' => ['will', 'estate', 'beneficiary', 'probate']],
        ['q' => 'consumer rights protection', 'expect' => ['consumer', 'rights', 'refund', 'warranty']],
    ],
    'entertainment' => [
        ['q' => 'best movies of 2025', 'expect' => ['movie', 'film', 'drama', 'director']],
        ['q' => 'top video games this year', 'expect' => ['game', 'play', 'console', 'pc']],
        ['q' => 'streaming services comparison', 'expect' => ['netflix', 'streaming', 'hulu', 'disney']],
        ['q' => 'music production software', 'expect' => ['music', 'daw', 'software', 'audio']],
        ['q' => 'book recommendations fiction', 'expect' => ['book', 'novel', 'fiction', 'author']],
        ['q' => 'podcast recommendations 2025', 'expect' => ['podcast', 'episode', 'listen', 'show']],
        ['q' => 'virtual reality gaming', 'expect' => ['vr', 'virtual', 'gaming', 'headset']],
        ['q' => 'board games for adults', 'expect' => ['board', 'game', 'player', 'strategy']],
        ['q' => 'concert ticket buying tips', 'expect' => ['concert', 'ticket', 'event', 'venue']],
        ['q' => 'anime recommendations for beginners', 'expect' => ['anime', 'manga', 'series', 'episode']],
    ],
    'sports' => [
        ['q' => 'football world cup history', 'expect' => ['world cup', 'football', 'fifa', 'team']],
        ['q' => 'how to start running', 'expect' => ['running', 'jog', 'mile', 'pace']],
        ['q' => 'basketball training drills', 'expect' => ['basketball', 'dribble', 'shoot', 'court']],
        ['q' => 'olympic games history', 'expect' => ['olympic', 'medal', 'athlete', 'sport']],
        ['q' => 'tennis rules for beginners', 'expect' => ['tennis', 'serve', 'set', 'match']],
        ['q' => 'swimming techniques', 'expect' => ['swim', 'stroke', 'pool', 'freestyle']],
        ['q' => 'martial arts types', 'expect' => ['martial', 'karate', 'judo', 'fighting']],
        ['q' => 'golf tips for beginners', 'expect' => ['golf', 'swing', 'club', 'course']],
        ['q' => 'cycling training plan', 'expect' => ['cycle', 'bike', 'ride', 'training']],
        ['q' => 'weightlifting program', 'expect' => ['weight', 'lift', 'muscle', 'rep']],
    ],
    'environment' => [
        ['q' => 'renewable energy sources', 'expect' => ['solar', 'wind', 'renewable', 'energy']],
        ['q' => 'recycling guide', 'expect' => ['recycle', 'waste', 'plastic', 'environment']],
        ['q' => 'ocean pollution effects', 'expect' => ['ocean', 'pollution', 'plastic', 'marine']],
        ['q' => 'deforestation causes', 'expect' => ['forest', 'tree', 'logging', 'ecosystem']],
        ['q' => 'electric vehicles pros and cons', 'expect' => ['electric', 'vehicle', 'battery', 'ev']],
        ['q' => 'carbon footprint calculator', 'expect' => ['carbon', 'emission', 'footprint', 'co2']],
        ['q' => 'sustainable living tips', 'expect' => ['sustainable', 'green', 'eco', 'reduce']],
        ['q' => 'endangered species list', 'expect' => ['species', 'endangered', 'animal', 'extinction']],
        ['q' => 'water conservation methods', 'expect' => ['water', 'conservation', 'save', 'drought']],
        ['q' => 'composting at home', 'expect' => ['compost', 'soil', 'organic', 'garden']],
    ],
    'automotive' => [
        ['q' => 'best electric cars 2025', 'expect' => ['electric', 'car', 'tesla', 'range']],
        ['q' => 'car maintenance schedule', 'expect' => ['maintenance', 'oil', 'tire', 'brake']],
        ['q' => 'how to change a tire', 'expect' => ['tire', 'jack', 'lug', 'spare']],
        ['q' => 'car insurance comparison', 'expect' => ['insurance', 'car', 'coverage', 'premium']],
        ['q' => 'self driving car technology', 'expect' => ['autonomous', 'self-driving', 'sensor', 'ai']],
        ['q' => 'motorcycle safety tips', 'expect' => ['motorcycle', 'helmet', 'ride', 'safety']],
        ['q' => 'car buying guide', 'expect' => ['car', 'buy', 'dealer', 'price', 'lease']],
        ['q' => 'hybrid vs electric cars', 'expect' => ['hybrid', 'electric', 'fuel', 'battery']],
        ['q' => 'engine troubleshooting guide', 'expect' => ['engine', 'diagnose', 'check', 'repair']],
        ['q' => 'car detailing tips', 'expect' => ['detail', 'wash', 'wax', 'interior']],
    ],
    'real_estate' => [
        ['q' => 'first time home buyer tips', 'expect' => ['home', 'buy', 'mortgage', 'down payment']],
        ['q' => 'how to sell a house', 'expect' => ['sell', 'house', 'listing', 'agent']],
        ['q' => 'real estate investing basics', 'expect' => ['real estate', 'invest', 'property', 'rental']],
        ['q' => 'home renovation ideas', 'expect' => ['renovation', 'remodel', 'kitchen', 'bathroom']],
        ['q' => 'property tax explained', 'expect' => ['property', 'tax', 'assessment', 'value']],
        ['q' => 'renting vs buying a home', 'expect' => ['rent', 'buy', 'mortgage', 'cost']],
        ['q' => 'commercial real estate guide', 'expect' => ['commercial', 'office', 'lease', 'property']],
        ['q' => 'home inspection checklist', 'expect' => ['inspection', 'house', 'foundation', 'roof']],
        ['q' => 'real estate market trends', 'expect' => ['market', 'price', 'housing', 'trend']],
        ['q' => 'how to refinance mortgage', 'expect' => ['refinance', 'mortgage', 'rate', 'loan']],
    ],
    'pets' => [
        ['q' => 'how to train a puppy', 'expect' => ['puppy', 'train', 'dog', 'command']],
        ['q' => 'best cat food brands', 'expect' => ['cat', 'food', 'nutrition', 'brand']],
        ['q' => 'fish tank setup guide', 'expect' => ['aquarium', 'fish', 'tank', 'water']],
        ['q' => 'dog grooming tips', 'expect' => ['dog', 'groom', 'bath', 'brush']],
        ['q' => 'common pet health issues', 'expect' => ['pet', 'vet', 'health', 'symptom']],
        ['q' => 'bird cage setup', 'expect' => ['bird', 'cage', 'perch', 'parrot']],
        ['q' => 'reptile pet care guide', 'expect' => ['reptile', 'lizard', 'snake', 'terrarium']],
        ['q' => 'pet adoption process', 'expect' => ['adopt', 'shelter', 'pet', 'rescue']],
        ['q' => 'raw diet for dogs', 'expect' => ['raw', 'diet', 'dog', 'meat', 'bone']],
        ['q' => 'how to litter train a cat', 'expect' => ['litter', 'cat', 'box', 'train']],
    ],
    'gardening' => [
        ['q' => 'vegetable garden for beginners', 'expect' => ['vegetable', 'garden', 'plant', 'grow']],
        ['q' => 'indoor plant care tips', 'expect' => ['indoor', 'plant', 'water', 'light']],
        ['q' => 'composting basics', 'expect' => ['compost', 'soil', 'organic', 'decompose']],
        ['q' => 'flower garden design', 'expect' => ['flower', 'garden', 'design', 'bed']],
        ['q' => 'pest control for gardens', 'expect' => ['pest', 'insect', 'organic', 'spray']],
        ['q' => 'herb garden indoors', 'expect' => ['herb', 'basil', 'mint', 'indoor']],
        ['q' => 'raised bed gardening', 'expect' => ['raised', 'bed', 'soil', 'planter']],
        ['q' => 'tree pruning guide', 'expect' => ['prune', 'tree', 'branch', 'cut']],
        ['q' => 'lawn care schedule', 'expect' => ['lawn', 'mow', 'grass', 'fertilize']],
        ['q' => 'hydroponic growing system', 'expect' => ['hydroponic', 'nutrient', 'water', 'grow']],
    ],
    'diy_home' => [
        ['q' => 'how to paint a room', 'expect' => ['paint', 'wall', 'brush', 'roller']],
        ['q' => 'plumbing repair basics', 'expect' => ['plumb', 'pipe', 'leak', 'fix']],
        ['q' => 'electrical wiring guide', 'expect' => ['wire', 'electric', 'circuit', 'outlet']],
        ['q' => 'woodworking projects for beginners', 'expect' => ['wood', 'saw', 'build', 'project']],
        ['q' => 'drywall repair tutorial', 'expect' => ['drywall', 'patch', 'mud', 'repair']],
        ['q' => 'how to install flooring', 'expect' => ['floor', 'tile', 'hardwood', 'install']],
        ['q' => 'bathroom renovation tips', 'expect' => ['bathroom', 'tile', 'shower', 'vanity']],
        ['q' => 'smart home setup guide', 'expect' => ['smart', 'home', 'automation', 'device']],
        ['q' => 'garage organization ideas', 'expect' => ['garage', 'organize', 'storage', 'shelf']],
        ['q' => 'energy efficient home upgrades', 'expect' => ['energy', 'insulation', 'window', 'efficient']],
    ],
    'fashion' => [
        ['q' => 'fashion trends 2025', 'expect' => ['fashion', 'trend', 'style', 'wear']],
        ['q' => 'how to build a capsule wardrobe', 'expect' => ['wardrobe', 'capsule', 'outfit', 'basic']],
        ['q' => 'sustainable fashion brands', 'expect' => ['sustainable', 'fashion', 'eco', 'ethical']],
        ['q' => 'men suit fitting guide', 'expect' => ['suit', 'fit', 'tailor', 'jacket']],
        ['q' => 'shoe care and maintenance', 'expect' => ['shoe', 'clean', 'leather', 'polish']],
        ['q' => 'color coordination outfits', 'expect' => ['color', 'outfit', 'match', 'palette']],
        ['q' => 'designer vs fast fashion', 'expect' => ['designer', 'fast fashion', 'quality', 'price']],
        ['q' => 'jewelry buying guide', 'expect' => ['jewelry', 'gold', 'diamond', 'ring']],
        ['q' => 'winter coat styles', 'expect' => ['coat', 'winter', 'jacket', 'warm']],
        ['q' => 'fashion industry careers', 'expect' => ['fashion', 'career', 'design', 'industry']],
    ],
    'parenting' => [
        ['q' => 'newborn baby care tips', 'expect' => ['baby', 'newborn', 'care', 'feeding']],
        ['q' => 'toddler development milestones', 'expect' => ['toddler', 'milestone', 'development', 'child']],
        ['q' => 'homework help strategies', 'expect' => ['homework', 'study', 'child', 'school']],
        ['q' => 'child nutrition guide', 'expect' => ['child', 'nutrition', 'food', 'vitamin']],
        ['q' => 'positive parenting techniques', 'expect' => ['parent', 'discipline', 'positive', 'behavior']],
        ['q' => 'screen time limits for children', 'expect' => ['screen', 'time', 'child', 'device']],
        ['q' => 'baby sleep training methods', 'expect' => ['baby', 'sleep', 'crib', 'night']],
        ['q' => 'teaching kids to read', 'expect' => ['read', 'child', 'phonics', 'book']],
        ['q' => 'after school activities for kids', 'expect' => ['activity', 'child', 'school', 'sport']],
        ['q' => 'college preparation for teens', 'expect' => ['college', 'teen', 'application', 'sat']],
    ],
    'photography' => [
        ['q' => 'photography basics for beginners', 'expect' => ['camera', 'photo', 'exposure', 'lens']],
        ['q' => 'best cameras 2025', 'expect' => ['camera', 'dslr', 'mirrorless', 'sensor']],
        ['q' => 'photo editing software comparison', 'expect' => ['edit', 'photoshop', 'lightroom', 'software']],
        ['q' => 'landscape photography tips', 'expect' => ['landscape', 'photo', 'wide', 'nature']],
        ['q' => 'portrait lighting techniques', 'expect' => ['portrait', 'light', 'studio', 'flash']],
        ['q' => 'drone photography guide', 'expect' => ['drone', 'aerial', 'photo', 'fly']],
        ['q' => 'night photography settings', 'expect' => ['night', 'long exposure', 'iso', 'tripod']],
        ['q' => 'smartphone photography tricks', 'expect' => ['phone', 'mobile', 'photo', 'camera']],
        ['q' => 'photo composition rules', 'expect' => ['composition', 'rule', 'third', 'framing']],
        ['q' => 'print your photos guide', 'expect' => ['print', 'photo', 'paper', 'resolution']],
    ],
    'crypto_blockchain' => [
        ['q' => 'bitcoin mining explained', 'expect' => ['bitcoin', 'mining', 'hash', 'block']],
        ['q' => 'ethereum smart contracts', 'expect' => ['ethereum', 'smart contract', 'solidity']],
        ['q' => 'defi decentralized finance', 'expect' => ['defi', 'decentralized', 'lending', 'yield']],
        ['q' => 'nft marketplace guide', 'expect' => ['nft', 'token', 'marketplace', 'digital']],
        ['q' => 'crypto wallet security', 'expect' => ['wallet', 'security', 'private key', 'seed']],
        ['q' => 'blockchain technology explained', 'expect' => ['blockchain', 'distributed', 'ledger', 'node']],
        ['q' => 'staking crypto rewards', 'expect' => ['staking', 'reward', 'validator', 'proof']],
        ['q' => 'solana ecosystem overview', 'expect' => ['solana', 'fast', 'transaction', 'sol']],
        ['q' => 'crypto tax reporting', 'expect' => ['crypto', 'tax', 'capital gain', 'report']],
        ['q' => 'web3 development tools', 'expect' => ['web3', 'dapp', 'blockchain', 'develop']],
    ],
    'ai_artificial_intelligence' => [
        ['q' => 'chatgpt alternatives', 'expect' => ['ai', 'chatbot', 'language', 'model']],
        ['q' => 'ai image generation tools', 'expect' => ['image', 'generate', 'ai', 'diffusion']],
        ['q' => 'natural language processing', 'expect' => ['nlp', 'language', 'text', 'model']],
        ['q' => 'ai ethics and bias', 'expect' => ['ethics', 'bias', 'fairness', 'ai']],
        ['q' => 'deep learning frameworks', 'expect' => ['deep learning', 'tensorflow', 'pytorch']],
        ['q' => 'computer vision applications', 'expect' => ['vision', 'image', 'recognition', 'detect']],
        ['q' => 'ai in healthcare', 'expect' => ['ai', 'healthcare', 'diagnose', 'medical']],
        ['q' => 'reinforcement learning basics', 'expect' => ['reinforcement', 'agent', 'reward', 'policy']],
        ['q' => 'ai voice assistants comparison', 'expect' => ['voice', 'assistant', 'alexa', 'siri']],
        ['q' => 'ai job market impact', 'expect' => ['ai', 'job', 'automation', 'workforce']],
    ],
    'music' => [
        ['q' => 'learn guitar for beginners', 'expect' => ['guitar', 'chord', 'strum', 'learn']],
        ['q' => 'music theory basics', 'expect' => ['music', 'note', 'scale', 'chord']],
        ['q' => 'best headphones 2025', 'expect' => ['headphone', 'audio', 'sound', 'noise']],
        ['q' => 'piano lessons online', 'expect' => ['piano', 'key', 'lesson', 'practice']],
        ['q' => 'how to mix and master music', 'expect' => ['mix', 'master', 'eq', 'audio']],
        ['q' => 'music streaming services comparison', 'expect' => ['spotify', 'apple music', 'stream']],
        ['q' => 'songwriting tips', 'expect' => ['song', 'write', 'lyric', 'melody']],
        ['q' => 'classical music composers', 'expect' => ['classical', 'beethoven', 'mozart', 'symphony']],
        ['q' => 'electronic music production', 'expect' => ['electronic', 'synth', 'beat', 'daw']],
        ['q' => 'vinyl record collecting', 'expect' => ['vinyl', 'record', 'turntable', 'album']],
    ],
    'careers' => [
        ['q' => 'resume writing tips', 'expect' => ['resume', 'cv', 'skill', 'experience']],
        ['q' => 'job interview preparation', 'expect' => ['interview', 'question', 'answer', 'prepare']],
        ['q' => 'salary negotiation strategies', 'expect' => ['salary', 'negotiate', 'offer', 'raise']],
        ['q' => 'work from home jobs', 'expect' => ['remote', 'work', 'home', 'job']],
        ['q' => 'career change advice', 'expect' => ['career', 'change', 'transition', 'skill']],
        ['q' => 'freelancing tips', 'expect' => ['freelance', 'client', 'rate', 'contract']],
        ['q' => 'leadership skills development', 'expect' => ['leader', 'manage', 'team', 'skill']],
        ['q' => 'networking for professionals', 'expect' => ['network', 'connection', 'linkedin', 'event']],
        ['q' => 'workplace conflict resolution', 'expect' => ['conflict', 'resolution', 'workplace', 'mediate']],
        ['q' => 'side hustle ideas 2025', 'expect' => ['side', 'hustle', 'income', 'earn']],
    ],
    'math' => [
        ['q' => 'algebra basics explained', 'expect' => ['algebra', 'equation', 'variable', 'solve']],
        ['q' => 'geometry formulas', 'expect' => ['geometry', 'area', 'triangle', 'circle']],
        ['q' => 'statistics for beginners', 'expect' => ['statistics', 'mean', 'probability', 'data']],
        ['q' => 'linear algebra applications', 'expect' => ['matrix', 'vector', 'linear', 'transformation']],
        ['q' => 'calculus derivatives', 'expect' => ['derivative', 'function', 'rate', 'change']],
        ['q' => 'trigonometry explained', 'expect' => ['trigonometry', 'sine', 'cosine', 'angle']],
        ['q' => 'number theory basics', 'expect' => ['prime', 'number', 'divisor', 'modular']],
        ['q' => 'graph theory introduction', 'expect' => ['graph', 'vertex', 'edge', 'path']],
        ['q' => 'probability distributions', 'expect' => ['probability', 'distribution', 'normal', 'random']],
        ['q' => 'math puzzles and problems', 'expect' => ['puzzle', 'math', 'problem', 'logic']],
    ],
    'history' => [
        ['q' => 'ancient rome history', 'expect' => ['rome', 'roman', 'empire', 'caesar']],
        ['q' => 'french revolution causes', 'expect' => ['french', 'revolution', 'liberty', 'bastille']],
        ['q' => 'cold war timeline', 'expect' => ['cold war', 'soviet', 'usa', 'nuclear']],
        ['q' => 'ancient egypt civilization', 'expect' => ['egypt', 'pharaoh', 'pyramid', 'nile']],
        ['q' => 'industrial revolution effects', 'expect' => ['industrial', 'factory', 'steam', 'manufacture']],
        ['q' => 'civil rights movement', 'expect' => ['civil rights', 'mlk', 'equality', 'segregation']],
        ['q' => 'medieval europe society', 'expect' => ['medieval', 'feudal', 'knight', 'castle']],
        ['q' => 'world war 1 causes', 'expect' => ['world war', 'alliance', 'assassination', 'trench']],
        ['q' => 'space race history', 'expect' => ['space', 'nasa', 'moon', 'apollo']],
        ['q' => 'silk road trade route', 'expect' => ['silk road', 'trade', 'china', 'merchant']],
    ],
    'psychology' => [
        ['q' => 'cognitive behavioral therapy', 'expect' => ['cbt', 'therapy', 'cognitive', 'behavior']],
        ['q' => 'personality types explained', 'expect' => ['personality', 'type', 'trait', 'introvert']],
        ['q' => 'stress management techniques', 'expect' => ['stress', 'manage', 'relax', 'cope']],
        ['q' => 'child psychology overview', 'expect' => ['child', 'development', 'behavior', 'psychology']],
        ['q' => 'social psychology concepts', 'expect' => ['social', 'conformity', 'group', 'influence']],
        ['q' => 'addiction recovery process', 'expect' => ['addiction', 'recovery', 'substance', 'rehab']],
        ['q' => 'emotional intelligence tips', 'expect' => ['emotional', 'intelligence', 'empathy', 'aware']],
        ['q' => 'memory improvement techniques', 'expect' => ['memory', 'recall', 'mnemonic', 'brain']],
        ['q' => 'motivation theories', 'expect' => ['motivation', 'maslow', 'need', 'drive']],
        ['q' => 'ptsd symptoms and treatment', 'expect' => ['ptsd', 'trauma', 'symptom', 'therapy']],
    ],
    'business_marketing' => [
        ['q' => 'digital marketing strategies', 'expect' => ['digital', 'marketing', 'seo', 'social']],
        ['q' => 'email marketing best practices', 'expect' => ['email', 'campaign', 'newsletter', 'open rate']],
        ['q' => 'social media marketing guide', 'expect' => ['social media', 'post', 'engagement', 'content']],
        ['q' => 'seo optimization techniques', 'expect' => ['seo', 'keyword', 'rank', 'google']],
        ['q' => 'content marketing strategy', 'expect' => ['content', 'blog', 'audience', 'strategy']],
        ['q' => 'branding guidelines creation', 'expect' => ['brand', 'logo', 'identity', 'guideline']],
        ['q' => 'google ads tutorial', 'expect' => ['google', 'ad', 'ppc', 'campaign']],
        ['q' => 'affiliate marketing guide', 'expect' => ['affiliate', 'commission', 'link', 'promote']],
        ['q' => 'customer retention strategies', 'expect' => ['customer', 'retention', 'loyalty', 'churn']],
        ['q' => 'startup funding options', 'expect' => ['funding', 'venture', 'seed', 'investor']],
    ],
    'security_privacy' => [
        ['q' => 'password manager comparison', 'expect' => ['password', 'manager', 'encrypt', 'vault']],
        ['q' => 'vpn service reviews', 'expect' => ['vpn', 'privacy', 'encrypt', 'server']],
        ['q' => 'two factor authentication setup', 'expect' => ['2fa', 'authentication', 'token', 'security']],
        ['q' => 'protect against phishing', 'expect' => ['phishing', 'email', 'scam', 'link']],
        ['q' => 'data encryption explained', 'expect' => ['encrypt', 'aes', 'key', 'cipher']],
        ['q' => 'online privacy tips', 'expect' => ['privacy', 'track', 'cookie', 'browser']],
        ['q' => 'ransomware protection', 'expect' => ['ransomware', 'backup', 'malware', 'decrypt']],
        ['q' => 'secure messaging apps', 'expect' => ['secure', 'message', 'encrypt', 'signal']],
        ['q' => 'network security fundamentals', 'expect' => ['network', 'firewall', 'intrusion', 'security']],
        ['q' => 'identity theft prevention', 'expect' => ['identity', 'theft', 'credit', 'fraud']],
    ],
    'space_astronomy' => [
        ['q' => 'mars exploration missions', 'expect' => ['mars', 'rover', 'nasa', 'mission']],
        ['q' => 'hubble telescope discoveries', 'expect' => ['hubble', 'telescope', 'galaxy', 'image']],
        ['q' => 'how to start stargazing', 'expect' => ['star', 'telescope', 'sky', 'constellation']],
        ['q' => 'international space station', 'expect' => ['iss', 'space station', 'orbit', 'astronaut']],
        ['q' => 'exoplanet discoveries', 'expect' => ['exoplanet', 'habitable', 'star', 'kepler']],
        ['q' => 'space rocket technology', 'expect' => ['rocket', 'spacex', 'launch', 'orbit']],
        ['q' => 'asteroid mining concept', 'expect' => ['asteroid', 'mining', 'resource', 'space']],
        ['q' => 'james webb telescope', 'expect' => ['webb', 'telescope', 'infrared', 'galaxy']],
        ['q' => 'dark matter explained', 'expect' => ['dark matter', 'universe', 'galaxy', 'mass']],
        ['q' => 'future of space travel', 'expect' => ['space', 'travel', 'colonize', 'mars']],
    ],
    'languages' => [
        ['q' => 'learn french online', 'expect' => ['french', 'language', 'learn', 'lesson']],
        ['q' => 'japanese writing systems', 'expect' => ['japanese', 'kanji', 'hiragana', 'katakana']],
        ['q' => 'mandarin chinese for beginners', 'expect' => ['mandarin', 'chinese', 'tone', 'character']],
        ['q' => 'german grammar basics', 'expect' => ['german', 'grammar', 'noun', 'verb']],
        ['q' => 'arabic alphabet guide', 'expect' => ['arabic', 'alphabet', 'letter', 'script']],
        ['q' => 'korean language learning', 'expect' => ['korean', 'hangul', 'learn', 'language']],
        ['q' => 'portuguese vs spanish differences', 'expect' => ['portuguese', 'spanish', 'language', 'differ']],
        ['q' => 'sign language basics', 'expect' => ['sign', 'language', 'asl', 'gesture']],
        ['q' => 'polyglot learning tips', 'expect' => ['polyglot', 'language', 'learn', 'method']],
        ['q' => 'latin language resources', 'expect' => ['latin', 'classical', 'grammar', 'language']],
    ],
    'art_design' => [
        ['q' => 'graphic design basics', 'expect' => ['design', 'graphic', 'layout', 'typography']],
        ['q' => 'oil painting techniques', 'expect' => ['oil', 'paint', 'canvas', 'brush']],
        ['q' => 'ui ux design principles', 'expect' => ['ui', 'ux', 'design', 'user']],
        ['q' => 'watercolor painting tutorial', 'expect' => ['watercolor', 'paint', 'brush', 'wet']],
        ['q' => 'digital illustration tools', 'expect' => ['digital', 'illustrat', 'tablet', 'draw']],
        ['q' => 'art history timeline', 'expect' => ['art', 'period', 'renaissance', 'impressionism']],
        ['q' => 'logo design tips', 'expect' => ['logo', 'design', 'brand', 'icon']],
        ['q' => 'animation software comparison', 'expect' => ['animation', 'animate', 'frame', 'software']],
        ['q' => '3d modeling for beginners', 'expect' => ['3d', 'model', 'blender', 'mesh']],
        ['q' => 'color theory explained', 'expect' => ['color', 'hue', 'saturation', 'complementary']],
    ],
    'philosophy' => [
        ['q' => 'existentialism explained', 'expect' => ['existential', 'sartre', 'meaning', 'existence']],
        ['q' => 'stoic philosophy principles', 'expect' => ['stoic', 'virtue', 'control', 'marcus']],
        ['q' => 'ethics theories overview', 'expect' => ['ethics', 'moral', 'utilitarianism', 'deontology']],
        ['q' => 'socrates teaching method', 'expect' => ['socrates', 'question', 'dialogue', 'method']],
        ['q' => 'eastern philosophy overview', 'expect' => ['eastern', 'buddhism', 'taoism', 'zen']],
        ['q' => 'free will vs determinism', 'expect' => ['free will', 'determinism', 'choice', 'cause']],
        ['q' => 'political philosophy intro', 'expect' => ['political', 'justice', 'state', 'right']],
        ['q' => 'philosophy of mind', 'expect' => ['mind', 'consciousness', 'brain', 'dualism']],
        ['q' => 'nietzsche philosophy summary', 'expect' => ['nietzsche', 'power', 'moral', 'beyond']],
        ['q' => 'logic and reasoning basics', 'expect' => ['logic', 'argument', 'premise', 'fallacy']],
    ],
    'gositeme_platform' => [
        ['q' => 'GoSiteMe website builder', 'expect' => ['gositeme', 'website', 'build', 'host']],
        ['q' => 'Alfred AI assistant', 'expect' => ['alfred', 'ai', 'assistant', 'gositeme']],
        ['q' => 'Veil secure browser', 'expect' => ['veil', 'browser', 'privacy', 'encrypt']],
        ['q' => 'Pulse social network', 'expect' => ['pulse', 'social', 'post', 'feed']],
        ['q' => 'GoCodeMe IDE editor', 'expect' => ['gocodeme', 'code', 'editor', 'ide']],
        ['q' => 'GSM cryptocurrency token', 'expect' => ['gsm', 'token', 'crypto', 'mining']],
        ['q' => 'GoSiteMe VR experiences', 'expect' => ['vr', 'virtual', 'reality', 'gositeme']],
        ['q' => 'GoSiteMe marketplace', 'expect' => ['marketplace', 'template', 'theme', 'buy']],
        ['q' => 'GoSiteMe voice services', 'expect' => ['voice', 'call', 'voip', 'gositeme']],
        ['q' => 'GoSiteMe API documentation', 'expect' => ['api', 'documentation', 'endpoint', 'developer']],
    ],
    'edge_cases' => [
        ['q' => 'car', 'expect_not' => ['scarlet', 'scare', 'incarcerate'], 'expect' => ['car', 'vehicle', 'auto']],
        ['q' => 'the', 'expect' => [], 'min_results' => 0],
        ['q' => 'python programming language', 'expect' => ['python', 'programming']],
        ['q' => '', 'expect' => [], 'min_results' => 0],
        ['q' => 'a', 'expect' => [], 'min_results' => 0],
        ['q' => 'how to', 'expect' => ['how', 'guide', 'tutorial']],
        ['q' => 'asdfjkl;qweruiop', 'expect' => [], 'min_results' => 0],
        ['q' => 'what is the meaning of life', 'expect' => ['meaning', 'life', 'philosophy']],
        ['q' => 'buy cheap iPhone online', 'expect' => ['iphone', 'apple', 'buy', 'phone']],
        ['q' => 'weather tomorrow', 'expect' => ['weather', 'forecast', 'temperature']],
    ],
    'medicine' => [
        ['q' => 'antibiotic resistance crisis', 'expect' => ['antibiotic', 'resistance', 'bacteria']],
        ['q' => 'heart disease prevention', 'expect' => ['heart', 'cardiovascular', 'cholesterol']],
        ['q' => 'cancer treatment advances', 'expect' => ['cancer', 'treatment', 'therapy', 'tumor']],
        ['q' => 'vaccination schedule adults', 'expect' => ['vaccine', 'immunization', 'booster']],
        ['q' => 'telemedicine services', 'expect' => ['telemedicine', 'virtual', 'doctor', 'online']],
        ['q' => 'physical therapy exercises', 'expect' => ['physical', 'therapy', 'exercise', 'rehab']],
        ['q' => 'eye care and vision health', 'expect' => ['eye', 'vision', 'optometrist', 'glasses']],
        ['q' => 'dental hygiene tips', 'expect' => ['dental', 'teeth', 'brush', 'floss']],
        ['q' => 'asthma management guide', 'expect' => ['asthma', 'inhaler', 'breathing', 'trigger']],
        ['q' => 'skin care routine basics', 'expect' => ['skin', 'care', 'moisturize', 'sunscreen']],
    ],
    'geography' => [
        ['q' => 'largest countries by area', 'expect' => ['country', 'russia', 'area', 'land']],
        ['q' => 'mountain ranges of the world', 'expect' => ['mountain', 'himalaya', 'range', 'peak']],
        ['q' => 'ocean currents explained', 'expect' => ['ocean', 'current', 'temperature', 'flow']],
        ['q' => 'tectonic plates movement', 'expect' => ['tectonic', 'plate', 'earthquake', 'drift']],
        ['q' => 'amazon rainforest facts', 'expect' => ['amazon', 'rainforest', 'biodiversity', 'brazil']],
        ['q' => 'arctic climate change effects', 'expect' => ['arctic', 'ice', 'melt', 'polar']],
        ['q' => 'volcanoes around the world', 'expect' => ['volcano', 'eruption', 'lava', 'magma']],
        ['q' => 'desert biomes characteristics', 'expect' => ['desert', 'arid', 'sahara', 'cactus']],
        ['q' => 'world population statistics', 'expect' => ['population', 'billion', 'census', 'growth']],
        ['q' => 'river systems explained', 'expect' => ['river', 'delta', 'tributary', 'basin']],
    ],
    'economics' => [
        ['q' => 'supply and demand explained', 'expect' => ['supply', 'demand', 'price', 'market']],
        ['q' => 'gdp meaning and calculation', 'expect' => ['gdp', 'gross', 'domestic', 'economy']],
        ['q' => 'stock market crash history', 'expect' => ['crash', 'market', 'recession', 'depression']],
        ['q' => 'international trade agreements', 'expect' => ['trade', 'tariff', 'agreement', 'import']],
        ['q' => 'central bank monetary policy', 'expect' => ['central bank', 'interest rate', 'monetary']],
        ['q' => 'income inequality statistics', 'expect' => ['income', 'inequality', 'wealth', 'gap']],
        ['q' => 'microeconomics vs macroeconomics', 'expect' => ['micro', 'macro', 'economics', 'firm']],
        ['q' => 'behavioral economics overview', 'expect' => ['behavioral', 'economics', 'decision', 'bias']],
        ['q' => 'globalization pros and cons', 'expect' => ['global', 'trade', 'culture', 'outsource']],
        ['q' => 'unemployment rate trends', 'expect' => ['unemployment', 'job', 'labor', 'rate']],
    ],
    'agriculture' => [
        ['q' => 'organic farming methods', 'expect' => ['organic', 'farm', 'pesticide', 'soil']],
        ['q' => 'precision agriculture technology', 'expect' => ['precision', 'drone', 'sensor', 'crop']],
        ['q' => 'sustainable farming practices', 'expect' => ['sustainable', 'farm', 'soil', 'rotate']],
        ['q' => 'livestock management basics', 'expect' => ['livestock', 'cattle', 'feed', 'health']],
        ['q' => 'irrigation systems types', 'expect' => ['irrigation', 'water', 'drip', 'sprinkler']],
        ['q' => 'soil health improvement', 'expect' => ['soil', 'nutrient', 'compost', 'ph']],
        ['q' => 'vertical farming technology', 'expect' => ['vertical', 'farm', 'indoor', 'led']],
        ['q' => 'crop rotation benefits', 'expect' => ['crop', 'rotation', 'soil', 'yield']],
        ['q' => 'beekeeping for beginners', 'expect' => ['bee', 'hive', 'honey', 'pollinate']],
        ['q' => 'aquaculture fish farming', 'expect' => ['aquaculture', 'fish', 'farm', 'pond']],
    ],
    'energy' => [
        ['q' => 'solar panel installation guide', 'expect' => ['solar', 'panel', 'install', 'roof']],
        ['q' => 'wind turbine technology', 'expect' => ['wind', 'turbine', 'blade', 'generate']],
        ['q' => 'nuclear energy pros cons', 'expect' => ['nuclear', 'reactor', 'radiation', 'power']],
        ['q' => 'battery storage technology', 'expect' => ['battery', 'storage', 'lithium', 'charge']],
        ['q' => 'hydrogen fuel cell vehicles', 'expect' => ['hydrogen', 'fuel cell', 'emission', 'vehicle']],
        ['q' => 'geothermal energy systems', 'expect' => ['geothermal', 'heat', 'earth', 'pump']],
        ['q' => 'energy efficiency at home', 'expect' => ['energy', 'efficient', 'save', 'bill']],
        ['q' => 'oil and gas industry overview', 'expect' => ['oil', 'gas', 'petroleum', 'refinery']],
        ['q' => 'smart grid technology', 'expect' => ['smart', 'grid', 'electric', 'meter']],
        ['q' => 'tidal wave energy', 'expect' => ['tidal', 'wave', 'ocean', 'energy']],
    ],
    'architecture' => [
        ['q' => 'modern architecture styles', 'expect' => ['modern', 'architecture', 'design', 'building']],
        ['q' => 'sustainable building design', 'expect' => ['sustainable', 'green', 'leed', 'building']],
        ['q' => 'famous architects in history', 'expect' => ['architect', 'wright', 'gaudi', 'build']],
        ['q' => 'interior design principles', 'expect' => ['interior', 'design', 'space', 'decor']],
        ['q' => 'skyscraper construction', 'expect' => ['skyscraper', 'tall', 'tower', 'steel']],
        ['q' => 'gothic architecture features', 'expect' => ['gothic', 'arch', 'cathedral', 'vault']],
        ['q' => 'smart building technology', 'expect' => ['smart', 'building', 'sensor', 'automate']],
        ['q' => 'tiny house movement', 'expect' => ['tiny', 'house', 'small', 'minimal']],
        ['q' => 'earthquake resistant structures', 'expect' => ['earthquake', 'seismic', 'resistant', 'structure']],
        ['q' => 'urban planning basics', 'expect' => ['urban', 'plan', 'city', 'zone']],
    ],
    'law_enforcement' => [
        ['q' => 'criminal justice system', 'expect' => ['criminal', 'justice', 'court', 'prosecution']],
        ['q' => 'forensic science methods', 'expect' => ['forensic', 'evidence', 'dna', 'crime']],
        ['q' => 'police reform discussion', 'expect' => ['police', 'reform', 'accountability', 'community']],
        ['q' => 'constitutional rights overview', 'expect' => ['constitution', 'right', 'amendment', 'freedom']],
        ['q' => 'cybercrime investigation', 'expect' => ['cybercrime', 'hack', 'computer', 'investigation']],
        ['q' => 'international criminal law', 'expect' => ['international', 'criminal', 'tribunal', 'law']],
        ['q' => 'prison system statistics', 'expect' => ['prison', 'incarceration', 'inmate', 'correctional']],
        ['q' => 'juvenile justice system', 'expect' => ['juvenile', 'youth', 'delinquent', 'court']],
        ['q' => 'drug policy and legislation', 'expect' => ['drug', 'legislation', 'control', 'substance']],
        ['q' => 'witness protection program', 'expect' => ['witness', 'protection', 'identity', 'safety']],
    ],
    'transportation' => [
        ['q' => 'high speed rail systems', 'expect' => ['rail', 'train', 'speed', 'track']],
        ['q' => 'aviation industry trends', 'expect' => ['aviation', 'airline', 'flight', 'aircraft']],
        ['q' => 'public transit planning', 'expect' => ['transit', 'bus', 'subway', 'route']],
        ['q' => 'cargo shipping logistics', 'expect' => ['shipping', 'cargo', 'container', 'port']],
        ['q' => 'electric scooter regulations', 'expect' => ['scooter', 'electric', 'regulation', 'ride']],
        ['q' => 'autonomous vehicle testing', 'expect' => ['autonomous', 'vehicle', 'self-driving', 'test']],
        ['q' => 'bicycle infrastructure design', 'expect' => ['bicycle', 'bike', 'lane', 'cycle']],
        ['q' => 'hyperloop transportation concept', 'expect' => ['hyperloop', 'tube', 'speed', 'vacuum']],
        ['q' => 'maritime navigation technology', 'expect' => ['maritime', 'navigation', 'ship', 'gps']],
        ['q' => 'traffic management systems', 'expect' => ['traffic', 'signal', 'congestion', 'flow']],
    ],
    'social_media' => [
        ['q' => 'instagram growth strategies', 'expect' => ['instagram', 'follower', 'post', 'engagement']],
        ['q' => 'tiktok algorithm explained', 'expect' => ['tiktok', 'algorithm', 'video', 'fyp']],
        ['q' => 'youtube channel monetization', 'expect' => ['youtube', 'monetize', 'adsense', 'subscribe']],
        ['q' => 'twitter X platform changes', 'expect' => ['twitter', 'x', 'platform', 'post']],
        ['q' => 'social media addiction effects', 'expect' => ['social', 'addiction', 'mental', 'screen']],
        ['q' => 'linkedin profile optimization', 'expect' => ['linkedin', 'profile', 'professional', 'network']],
        ['q' => 'reddit community guidelines', 'expect' => ['reddit', 'subreddit', 'community', 'post']],
        ['q' => 'discord server setup', 'expect' => ['discord', 'server', 'channel', 'bot']],
        ['q' => 'twitch streaming guide', 'expect' => ['twitch', 'stream', 'live', 'viewer']],
        ['q' => 'social media privacy settings', 'expect' => ['privacy', 'setting', 'social', 'data']],
    ],
    'robotics' => [
        ['q' => 'robot programming languages', 'expect' => ['robot', 'programming', 'ros', 'python']],
        ['q' => 'drone technology advances', 'expect' => ['drone', 'uav', 'fly', 'sensor']],
        ['q' => 'industrial automation systems', 'expect' => ['industrial', 'automation', 'robot', 'manufacture']],
        ['q' => 'robot operating system ros', 'expect' => ['ros', 'robot', 'node', 'topic']],
        ['q' => 'humanoid robot development', 'expect' => ['humanoid', 'robot', 'bipedal', 'actuator']],
        ['q' => 'home assistant robots', 'expect' => ['home', 'robot', 'assistant', 'vacuum']],
        ['q' => 'robot vision systems', 'expect' => ['vision', 'camera', 'sensor', 'detect']],
        ['q' => 'surgical robotics', 'expect' => ['surgical', 'robot', 'precision', 'medical']],
        ['q' => 'swarm robotics research', 'expect' => ['swarm', 'robot', 'cooperative', 'agent']],
        ['q' => 'robot ethics and society', 'expect' => ['ethics', 'robot', 'autonomy', 'regulation']],
    ],
    'marine_biology' => [
        ['q' => 'coral reef ecosystem', 'expect' => ['coral', 'reef', 'marine', 'fish']],
        ['q' => 'whale migration patterns', 'expect' => ['whale', 'migration', 'ocean', 'humpback']],
        ['q' => 'deep sea creatures', 'expect' => ['deep', 'sea', 'creature', 'bioluminescent']],
        ['q' => 'shark species identification', 'expect' => ['shark', 'species', 'great white', 'ocean']],
        ['q' => 'marine pollution effects', 'expect' => ['marine', 'pollution', 'plastic', 'ocean']],
        ['q' => 'tide pool ecology', 'expect' => ['tide', 'pool', 'intertidal', 'organism']],
        ['q' => 'dolphin communication research', 'expect' => ['dolphin', 'communication', 'sonar', 'echolocation']],
        ['q' => 'kelp forest ecosystem', 'expect' => ['kelp', 'forest', 'otter', 'marine']],
        ['q' => 'marine conservation efforts', 'expect' => ['marine', 'conservation', 'protect', 'sanctuary']],
        ['q' => 'plankton importance in ocean', 'expect' => ['plankton', 'ocean', 'food chain', 'oxygen']],
    ],
    'weather_climate' => [
        ['q' => 'hurricane formation process', 'expect' => ['hurricane', 'tropical', 'wind', 'storm']],
        ['q' => 'tornado safety tips', 'expect' => ['tornado', 'shelter', 'warning', 'funnel']],
        ['q' => 'understanding weather maps', 'expect' => ['weather', 'map', 'pressure', 'front']],
        ['q' => 'climate zones of earth', 'expect' => ['climate', 'zone', 'tropical', 'temperate']],
        ['q' => 'drought conditions and impact', 'expect' => ['drought', 'water', 'agriculture', 'dry']],
        ['q' => 'el nino weather pattern', 'expect' => ['el nino', 'pacific', 'temperature', 'weather']],
        ['q' => 'snowstorm preparedness', 'expect' => ['snow', 'winter', 'prepare', 'emergency']],
        ['q' => 'heatwave health risks', 'expect' => ['heat', 'wave', 'temperature', 'hydrate']],
        ['q' => 'weather forecasting methods', 'expect' => ['forecast', 'predict', 'model', 'radar']],
        ['q' => 'global warming evidence', 'expect' => ['global', 'warming', 'temperature', 'co2']],
    ],
    'nutrition' => [
        ['q' => 'protein sources for athletes', 'expect' => ['protein', 'muscle', 'food', 'amino']],
        ['q' => 'vitamin D deficiency symptoms', 'expect' => ['vitamin d', 'deficiency', 'bone', 'sun']],
        ['q' => 'intermittent fasting guide', 'expect' => ['fasting', 'intermittent', 'eat', 'window']],
        ['q' => 'keto diet explained', 'expect' => ['keto', 'carb', 'fat', 'ketosis']],
        ['q' => 'food allergy testing', 'expect' => ['allergy', 'food', 'test', 'allergen']],
        ['q' => 'omega 3 fatty acids benefits', 'expect' => ['omega', 'fatty', 'fish', 'heart']],
        ['q' => 'sugar intake daily limit', 'expect' => ['sugar', 'daily', 'limit', 'health']],
        ['q' => 'iron rich foods list', 'expect' => ['iron', 'food', 'spinach', 'red meat']],
        ['q' => 'gut health and probiotics', 'expect' => ['gut', 'probiotic', 'bacteria', 'microbiome']],
        ['q' => 'hydration and water intake', 'expect' => ['water', 'hydration', 'drink', 'fluid']],
    ],
    'emergency_preparedness' => [
        ['q' => 'earthquake preparedness kit', 'expect' => ['earthquake', 'kit', 'emergency', 'supply']],
        ['q' => 'wildfire evacuation plan', 'expect' => ['wildfire', 'evacuation', 'fire', 'escape']],
        ['q' => 'flood safety procedures', 'expect' => ['flood', 'water', 'safety', 'evacuate']],
        ['q' => 'emergency food storage', 'expect' => ['emergency', 'food', 'store', 'supply']],
        ['q' => 'first responder training', 'expect' => ['first responder', 'cpr', 'emergency', 'emt']],
        ['q' => 'power outage preparation', 'expect' => ['power', 'outage', 'generator', 'battery']],
        ['q' => 'tsunami warning systems', 'expect' => ['tsunami', 'warning', 'wave', 'coastal']],
        ['q' => 'emergency communication plan', 'expect' => ['emergency', 'communication', 'contact', 'plan']],
        ['q' => 'winter storm survival', 'expect' => ['winter', 'storm', 'survival', 'shelter']],
        ['q' => 'pandemic preparedness guide', 'expect' => ['pandemic', 'virus', 'prepare', 'health']],
    ],
];

// ── Agent Registry ──────────────────────────────────────────────
function generateFleetAgents(int $count): array {
    $specialties = array_keys($GLOBALS['testCategories'] ?? []);
    if (empty($specialties)) return [];

    $prefixes = ['SCOUT', 'PROBE', 'QUERY', 'VERIFY', 'AUDIT', 'CHECK', 'TEST', 'SCAN', 'EVAL', 'RANK'];
    $agents = [];

    for ($i = 1; $i <= $count; $i++) {
        $prefix = $prefixes[($i - 1) % count($prefixes)];
        $specialty = $specialties[($i - 1) % count($specialties)];
        $agents[] = [
            'id'         => "SQT-" . str_pad($i, 3, '0', STR_PAD_LEFT),
            'name'       => "{$prefix}-" . str_pad($i, 3, '0', STR_PAD_LEFT),
            'specialty'  => $specialty,
            'status'     => 'ready',
            'tests_run'  => 0,
            'tests_passed' => 0,
            'tests_failed' => 0,
            'deployed_at' => date('c'),
        ];
    }
    return $agents;
}

// ── Scoring Engine ──────────────────────────────────────────────
function scoreSearchResult(array $results, array $testCase): array {
    $query = $testCase['q'];
    $expectWords = $testCase['expect'] ?? [];
    $expectNotWords = $testCase['expect_not'] ?? [];

    if (empty($results)) {
        $minResults = $testCase['min_results'] ?? 1;
        return [
            'passed' => ($minResults === 0),
            'score'  => ($minResults === 0) ? 100 : 0,
            'issues' => ($minResults > 0) ? ['No results returned'] : [],
            'details' => 'Empty result set'
        ];
    }

    $issues = [];
    $score = 50; // Base score

    // Check top 5 results for expected keywords
    $top5 = array_slice($results, 0, 5);
    $matchedExpectWords = 0;
    foreach ($expectWords as $word) {
        $found = false;
        foreach ($top5 as $r) {
            $text = strtolower(($r['title'] ?? '') . ' ' . ($r['snippet'] ?? '') . ' ' . ($r['url'] ?? ''));
            if (preg_match('/\b' . preg_quote(strtolower($word), '/') . '/i', $text)) {
                $found = true;
                break;
            }
        }
        if ($found) {
            $matchedExpectWords++;
        } else {
            $issues[] = "Expected keyword '{$word}' not found in top 5 results";
        }
    }

    if (count($expectWords) > 0) {
        $coverage = $matchedExpectWords / count($expectWords);
        $score = (int)($coverage * 80) + 20; // 20-100 range
    }

    // Check for false positives (expect_not words)
    foreach ($expectNotWords as $badWord) {
        foreach ($top5 as $r) {
            $title = strtolower($r['title'] ?? '');
            // Check if the bad word appears WITHOUT the good context
            if (preg_match('/\b' . preg_quote(strtolower($badWord), '/') . '\b/i', $title)) {
                $score -= 15;
                $issues[] = "False positive: '{$badWord}' found in title (substring match pollution)";
            }
        }
    }

    // Title relevance check — does query appear in first result title?
    $firstTitle = strtolower($results[0]['title'] ?? '');
    $queryWords = array_filter(preg_split('/\s+/', strtolower($query)), fn($w) => strlen($w) >= 3);
    $titleHits = 0;
    foreach ($queryWords as $qw) {
        if (preg_match('/\b' . preg_quote($qw, '/') . '/i', $firstTitle)) $titleHits++;
    }
    if (count($queryWords) > 0) {
        $titleRelevance = $titleHits / count($queryWords);
        if ($titleRelevance >= 0.5) $score += 10;
        if ($titleRelevance < 0.3) {
            $score -= 10;
            $issues[] = "First result title poorly matches query";
        }
    }

    return [
        'passed' => ($score >= 60),
        'score'  => max(0, min(100, $score)),
        'issues' => $issues,
        'details' => "Matched {$matchedExpectWords}/" . count($expectWords) . " expected keywords in top 5"
    ];
}

// ── Execute Search Test ─────────────────────────────────────────
function executeSearchTest(string $query): array {
    $searchUrl = 'https://gositeme.com/api/alfred-search.php?q=' . urlencode($query) . '&mode=web&count=10';

    $ch = curl_init($searchUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'X-Internal-Secret: ' . (defined('INTERNAL_SECRET') ? INTERNAL_SECRET : '')
        ],
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || $httpCode !== 200) {
        return ['error' => $error ?: "HTTP {$httpCode}", 'results' => []];
    }

    $data = json_decode($response, true);
    return [
        'error'   => null,
        'results' => $data['results'] ?? [],
        'timing'  => $data['timing'] ?? null,
    ];
}

// ── Run Full Fleet Test ─────────────────────────────────────────
function runFleetTest(int $agentCount = 500): array {
    global $testCategories;

    $agents = generateFleetAgents($agentCount);
    $report = [
        'fleet_id'     => 'SEARCH-FLEET-' . date('Ymd-His'),
        'agent_count'  => $agentCount,
        'started_at'   => date('c'),
        'categories'   => [],
        'summary'      => [
            'total_tests'  => 0,
            'passed'       => 0,
            'failed'       => 0,
            'avg_score'    => 0,
            'critical_issues' => [],
        ],
    ];

    $totalScore = 0;
    $agentIdx = 0;

    foreach ($testCategories as $category => $tests) {
        $catResults = [
            'category'   => $category,
            'tests_run'  => 0,
            'passed'     => 0,
            'failed'     => 0,
            'avg_score'  => 0,
            'issues'     => [],
            'results'    => [],
        ];

        $catScore = 0;
        foreach ($tests as $test) {
            $agentIdx = ($agentIdx + 1) % count($agents);
            $agent = &$agents[$agentIdx];

            $query = $test['q'];
            if (empty(trim($query))) {
                $catResults['tests_run']++;
                $catResults['passed']++;
                $agent['tests_run']++;
                $agent['tests_passed']++;
                $report['summary']['total_tests']++;
                $report['summary']['passed']++;
                continue;
            }

            $searchResult = executeSearchTest($query);

            if ($searchResult['error']) {
                $evaluation = [
                    'passed'  => false,
                    'score'   => 0,
                    'issues'  => ["Search error: " . $searchResult['error']],
                    'details' => 'Search API error'
                ];
            } else {
                $evaluation = scoreSearchResult($searchResult['results'], $test);
            }

            $catResults['tests_run']++;
            $catResults['results'][] = [
                'query'      => $query,
                'score'      => $evaluation['score'],
                'passed'     => $evaluation['passed'],
                'issues'     => $evaluation['issues'],
                'details'    => $evaluation['details'],
                'result_count' => count($searchResult['results']),
                'agent_id'   => $agent['id'],
            ];

            if ($evaluation['passed']) {
                $catResults['passed']++;
                $agent['tests_passed']++;
                $report['summary']['passed']++;
            } else {
                $catResults['failed']++;
                $agent['tests_failed']++;
                $report['summary']['failed']++;
                if ($evaluation['score'] < 30) {
                    $report['summary']['critical_issues'][] = [
                        'category' => $category,
                        'query'    => $query,
                        'score'    => $evaluation['score'],
                        'issues'   => $evaluation['issues'],
                    ];
                }
            }

            $agent['tests_run']++;
            $agent['status'] = 'active';
            $report['summary']['total_tests']++;
            $catScore += $evaluation['score'];
            $totalScore += $evaluation['score'];

            // Brief pause to avoid overwhelming search API
            usleep(50000); // 50ms
        }

        $catResults['avg_score'] = $catResults['tests_run'] > 0
            ? round($catScore / $catResults['tests_run'], 1) : 0;
        $catResults['issues'] = array_values(array_unique(
            array_merge(...array_map(fn($r) => $r['issues'], $catResults['results']))
        ));
        $report['categories'][$category] = $catResults;
    }

    $report['summary']['avg_score'] = $report['summary']['total_tests'] > 0
        ? round($totalScore / $report['summary']['total_tests'], 1) : 0;
    $report['completed_at'] = date('c');
    $report['agents'] = $agents;

    return $report;
}

// ── Save Report ─────────────────────────────────────────────────
function saveFleetReport(array $report): string {
    $dir = dirname(__DIR__) . '/logs/search-quality';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $file = $dir . '/report-' . date('Y-m-d-His') . '.json';
    file_put_contents($file, json_encode($report, JSON_PRETTY_PRINT));
    return $file;
}

// ── File Autonomy Report ────────────────────────────────────────
function fileAutonomyReport(array $report): void {
    $summary = $report['summary'];
    $grade = $summary['avg_score'] >= 80 ? 'A' :
        ($summary['avg_score'] >= 60 ? 'B' :
        ($summary['avg_score'] >= 40 ? 'C' : 'F'));

    $payload = json_encode([
        'action'      => 'agent_report',
        'agent_id'    => 'SEARCH-QA-FLEET',
        'report_type' => 'quality_audit',
        'severity'    => $summary['avg_score'] < 50 ? 'critical' : ($summary['avg_score'] < 70 ? 'warning' : 'info'),
        'title'       => "Search Quality Report — Grade: {$grade} ({$summary['avg_score']}%)",
        'details'     => json_encode([
            'total_tests'     => $summary['total_tests'],
            'passed'          => $summary['passed'],
            'failed'          => $summary['failed'],
            'avg_score'       => $summary['avg_score'],
            'critical_count'  => count($summary['critical_issues']),
            'grade'           => $grade,
        ]),
        'metrics'     => json_encode([
            'pass_rate'       => round($summary['passed'] / max($summary['total_tests'], 1) * 100, 1),
            'avg_score'       => $summary['avg_score'],
            'category_scores' => array_map(fn($c) => $c['avg_score'], $report['categories']),
        ]),
    ]);

    $ch = curl_init('http://localhost/api/agent-autonomy.php');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Internal-Secret: ' . (defined('INTERNAL_SECRET') ? INTERNAL_SECRET : '')
        ],
        CURLOPT_TIMEOUT => 10,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// ── API Handler ─────────────────────────────────────────────────
switch ($action) {
    case 'status':
        echo json_encode([
            'success' => true,
            'fleet'   => 'Search Quality Test Fleet',
            'version' => '1.0',
            'agents'  => 500,
            'categories' => count($testCategories),
            'total_test_queries' => array_sum(array_map('count', $testCategories)),
            'status'  => 'ready',
        ]);
        break;

    case 'run':
        if (!hash_equals($validSecret, $secret)) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $count = min((int)($_GET['agents'] ?? 500), 500);
        set_time_limit(600); // 10 minutes max
        ini_set('memory_limit', '512M');

        $report = runFleetTest($count);
        $reportFile = saveFleetReport($report);
        fileAutonomyReport($report);

        echo json_encode([
            'success' => true,
            'report'  => [
                'fleet_id'    => $report['fleet_id'],
                'total_tests' => $report['summary']['total_tests'],
                'passed'      => $report['summary']['passed'],
                'failed'      => $report['summary']['failed'],
                'avg_score'   => $report['summary']['avg_score'],
                'critical_issues' => count($report['summary']['critical_issues']),
                'report_file' => basename($reportFile),
            ],
            'categories' => array_map(fn($c) => [
                'category'  => $c['category'],
                'tests_run' => $c['tests_run'],
                'passed'    => $c['passed'],
                'failed'    => $c['failed'],
                'avg_score' => $c['avg_score'],
            ], $report['categories']),
        ]);
        break;

    case 'quick':
        // Quick test — run just 50 queries across 5 random categories
        if (!hash_equals($validSecret, $secret)) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        set_time_limit(120);
        $allCats = array_keys($testCategories);
        shuffle($allCats);
        $quickCats = array_slice($allCats, 0, 5);
        $quickReport = ['queries' => [], 'total' => 0, 'passed' => 0, 'failed' => 0, 'scores' => []];

        foreach ($quickCats as $cat) {
            $tests = $testCategories[$cat];
            foreach ($tests as $test) {
                if (empty(trim($test['q']))) continue;
                $sr = executeSearchTest($test['q']);
                $eval = $sr['error']
                    ? ['passed' => false, 'score' => 0, 'issues' => [$sr['error']], 'details' => 'error']
                    : scoreSearchResult($sr['results'], $test);

                $quickReport['queries'][] = [
                    'category' => $cat,
                    'query'    => $test['q'],
                    'score'    => $eval['score'],
                    'passed'   => $eval['passed'],
                    'results'  => count($sr['results']),
                ];
                $quickReport['total']++;
                $quickReport['scores'][] = $eval['score'];
                if ($eval['passed']) $quickReport['passed']++;
                else $quickReport['failed']++;
                usleep(50000);
            }
        }

        $quickReport['avg_score'] = count($quickReport['scores']) > 0
            ? round(array_sum($quickReport['scores']) / count($quickReport['scores']), 1) : 0;

        echo json_encode(['success' => true, 'quick_test' => $quickReport]);
        break;

    case 'categories':
        echo json_encode([
            'success'    => true,
            'categories' => array_map(fn($c, $t) => [
                'name'  => $c,
                'tests' => count($t),
            ], array_keys($testCategories), $testCategories),
        ]);
        break;

    default:
        echo json_encode([
            'success' => false,
            'error'   => 'Unknown action. Use: status, run, quick, categories',
        ]);
}
