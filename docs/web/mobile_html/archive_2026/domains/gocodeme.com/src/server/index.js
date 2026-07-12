const express = require('express');
const http = require('http');
const socketIo = require('socket.io');
const path = require('path');
const cors = require('cors');
const helmet = require('helmet');
const compression = require('compression');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
require('dotenv').config();

const app = express();
const server = http.createServer(app);
const io = socketIo(server, {
  cors: {
    origin: "*",
    methods: ["GET", "POST"]
  }
});

// Middleware
app.use(helmet());
app.use(compression());
app.use(cors());
app.use(express.json({ limit: '10mb' }));

// Serve static files from public folder
app.use('/editor', express.static(path.join(__dirname, '../../public')));
app.use('/code-server', express.static(path.join(__dirname, '../../public')));

// Import AI service
const aiService = require('../ai/claude-service');

// In-memory user storage (replace with database in production)
const users = new Map();
const subscriptions = new Map();

// JWT secret
const JWT_SECRET = process.env.JWT_SECRET || 'your-super-secret-jwt-key';

// Subscription plans
const PLANS = {
  FREE: {
    name: 'Free Trial',
    price: 0,
    features: ['Basic AI assistance', '5 AI requests/day', 'Basic VS Code features'],
    limits: { aiRequests: 5, storage: '100MB' }
  },
  STARTER: {
    name: 'Starter',
    price: 29,
    features: ['Full AI assistance', '100 AI requests/day', 'Full VS Code features', 'SSL certificate'],
    limits: { aiRequests: 100, storage: '1GB' }
  },
  PRO: {
    name: 'Professional',
    price: 79,
    features: ['Unlimited AI requests', 'Team collaboration', 'Advanced AI features', 'Priority support'],
    limits: { aiRequests: -1, storage: '10GB' }
  },
  ENTERPRISE: {
    name: 'Enterprise',
    price: 199,
    features: ['Custom AI models', 'Unlimited everything', 'White-label solution', 'Dedicated support'],
    limits: { aiRequests: -1, storage: '100GB' }
  }
};

// Authentication middleware
const authenticateToken = (req, res, next) => {
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1];

  if (!token) {
    return res.status(401).json({ error: 'Access token required' });
  }

  jwt.verify(token, JWT_SECRET, (err, user) => {
    if (err) {
      return res.status(403).json({ error: 'Invalid token' });
    }
    req.user = user;
    next();
  });
};

// Routes
app.get('/', (req, res) => {
  // Serve the new homepage
  res.sendFile(path.join(__dirname, '../../index.html'));
});

app.get('/editor', (req, res) => {
  // Serve the actual editor for authenticated users
  res.sendFile(path.join(__dirname, '../../public/index.html'));
});

// User registration
app.post('/api/auth/register', async (req, res) => {
  try {
    const { email, password, name, plan = 'FREE' } = req.body;
    
    if (users.has(email)) {
      return res.status(400).json({ error: 'User already exists' });
    }

    const hashedPassword = await bcrypt.hash(password, 10);
    const user = {
      id: Date.now().toString(),
      email,
      name,
      password: hashedPassword,
      plan,
      createdAt: new Date(),
      aiRequestsToday: 0,
      lastRequestDate: null
    };

    users.set(email, user);
    
    // Create subscription
    subscriptions.set(email, {
      plan,
      status: 'active',
      startDate: new Date(),
      endDate: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000) // 30 days
    });

    const token = jwt.sign({ email, id: user.id }, JWT_SECRET, { expiresIn: '7d' });
    
    res.json({ 
      success: true, 
      token,
      user: { email, name, plan },
      message: 'Account created successfully!'
    });
  } catch (error) {
    console.error('Registration Error:', error);
    res.status(500).json({ error: 'Registration failed' });
  }
});

// User login
app.post('/api/auth/login', async (req, res) => {
  try {
    const { email, password } = req.body;
    const user = users.get(email);
    
    if (!user) {
      return res.status(401).json({ error: 'Invalid credentials' });
    }

    const validPassword = await bcrypt.compare(password, user.password);
    if (!validPassword) {
      return res.status(401).json({ error: 'Invalid credentials' });
    }

    const token = jwt.sign({ email, id: user.id }, JWT_SECRET, { expiresIn: '7d' });
    
    res.json({ 
      success: true, 
      token,
      user: { email, name: user.name, plan: user.plan },
      message: 'Login successful'
    });
  } catch (error) {
    console.error('Login Error:', error);
    res.status(500).json({ error: 'Login failed' });
  }
});

// Get user profile
app.get('/api/user/profile', authenticateToken, (req, res) => {
  try {
    const user = users.get(req.user.email);
    if (!user) {
      return res.status(404).json({ error: 'User not found' });
    }

    const subscription = subscriptions.get(req.user.email);
    
    res.json({
      success: true,
      user: {
        email: user.email,
        name: user.name,
        plan: user.plan,
        aiRequestsToday: user.aiRequestsToday,
        lastRequestDate: user.lastRequestDate,
        subscription
      }
    });
  } catch (error) {
    console.error('Profile Error:', error);
    res.status(500).json({ error: 'Failed to get profile' });
  }
});

// Get subscription plans
app.get('/api/plans', (req, res) => {
  res.json({ success: true, plans: PLANS });
});

// Upgrade subscription
app.post('/api/subscription/upgrade', authenticateToken, async (req, res) => {
  try {
    const { plan } = req.body;
    const user = users.get(req.user.email);
    
    if (!user) {
      return res.status(404).json({ error: 'User not found' });
    }

    if (!PLANS[plan]) {
      return res.status(400).json({ error: 'Invalid plan' });
    }

    user.plan = plan;
    users.set(req.user.email, user);

    // Update subscription
    subscriptions.set(req.user.email, {
      plan,
      status: 'active',
      startDate: new Date(),
      endDate: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)
    });

    res.json({ 
      success: true, 
      message: `Upgraded to ${PLANS[plan].name} plan`,
      plan: PLANS[plan]
    });
  } catch (error) {
    console.error('Upgrade Error:', error);
    res.status(500).json({ error: 'Upgrade failed' });
  }
});

// AI Chat API with usage tracking
app.post('/api/chat', authenticateToken, async (req, res) => {
  try {
    const { message, context, fileContent } = req.body;
    const user = users.get(req.user.email);
    
    if (!user) {
      return res.status(404).json({ error: 'User not found' });
    }

    // Check usage limits
    const plan = PLANS[user.plan];
    const today = new Date().toDateString();
    
    if (user.lastRequestDate !== today) {
      user.aiRequestsToday = 0;
      user.lastRequestDate = today;
    }

    if (plan.limits.aiRequests > 0 && user.aiRequestsToday >= plan.limits.aiRequests) {
      return res.status(429).json({ 
        error: 'Daily AI request limit reached. Upgrade your plan for unlimited access.',
        limit: plan.limits.aiRequests,
        used: user.aiRequestsToday
      });
    }

    const response = await aiService.chat(message, context, fileContent);
    
    // Update usage
    user.aiRequestsToday++;
    users.set(req.user.email, user);

    res.json({ 
      success: true, 
      response: response,
      usage: {
        requestsToday: user.aiRequestsToday,
        limit: plan.limits.aiRequests
      }
    });
  } catch (error) {
    console.error('AI Chat Error:', error);
    res.status(500).json({ success: false, error: 'Failed to get AI response' });
  }
});

// code-server proxy
app.use('/code-server', (req, res) => {
  const codeServerUrl = `http://localhost:${process.env.CODE_SERVER_PORT || 8080}`;
  req.pipe(require('http').request(codeServerUrl + req.url, {
    method: req.method,
    headers: req.headers
  }, (response) => {
    res.writeHead(response.statusCode, response.headers);
    response.pipe(res);
  }));
});

// WebSocket for real-time AI chat
io.on('connection', (socket) => {
  console.log('Client connected:', socket.id);
  
  socket.on('ai-chat', async (data) => {
    try {
      // Note: WebSocket doesn't have authentication middleware
      // In production, implement proper WebSocket auth
      const response = await aiService.chat(data.message, data.context, data.fileContent);
      socket.emit('ai-response', { success: true, response: response });
    } catch (error) {
      socket.emit('ai-response', { success: false, error: 'Failed to get AI response' });
    }
  });
  
  socket.on('disconnect', () => {
    console.log('Client disconnected:', socket.id);
  });
});

// Start server
const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
  console.log(`🚀 GoCodeMe.com server running on port ${PORT}`);
  console.log(`📁 Workspace: ${process.env.CODE_SERVER_WORKSPACE || '/home/gositeme/domains/gocodeme.com/public_html'}`);
  console.log(`🤖 AI Model: ${process.env.CLAUDE_MODEL || 'anthropic/claude-3-sonnet'}`);
  console.log(`💳 Plans: ${Object.keys(PLANS).join(', ')}`);
}); 