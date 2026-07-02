<?php
/**
 * GoSiteMe — Join the Ecosystem
 * Public recruitment page where people can apply to work in the ecosystem.
 * Accepted applicants are onboarded, trained by agents, and assigned a job.
 */
$page_title = "Careers — Join the GoSiteMe Ecosystem";
$page_description = "Apply to work in the world's first AI-powered ecosystem. Join 1000+ agents and humans building the future.";
$page_canonical = 'https://root.com/careers';
include __DIR__ . '/includes/site-header.inc.php';
?>
<style>
/* ── Careers Page ── */
.cr-hero{position:relative;text-align:center;padding:5rem 2rem 4rem;overflow:hidden;background:linear-gradient(135deg,rgba(0,212,255,.08),rgba(124,58,237,.08))}
.cr-hero::before{content:'';position:absolute;top:-50%;left:50%;transform:translateX(-50%);width:900px;height:900px;background:radial-gradient(circle,rgba(124,58,237,.15) 0%,transparent 70%);pointer-events:none}
.cr-hero h1{font-size:clamp(2rem,5vw,3rem);font-weight:800;position:relative;margin-bottom:.8rem}
.cr-hero h1 span{background:linear-gradient(135deg,#00d4ff,#7c3aed);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.cr-hero .subtitle{font-size:1.15rem;color:var(--alfred-text-secondary,#8b8fa3);max-width:720px;margin:0 auto 2rem;line-height:1.7}
.cr-stats{display:flex;gap:2.5rem;justify-content:center;flex-wrap:wrap}
.cr-stat{text-align:center}
.cr-stat .num{font-size:2rem;font-weight:800;color:#00d4ff;font-family:'Space Grotesk',sans-serif}
.cr-stat .label{font-size:.82rem;color:var(--alfred-text-secondary,#8b8fa3);margin-top:4px}
.cr-container{max-width:1200px;margin:0 auto;padding:3rem 1.5rem 5rem}
.cr-pledge{background:linear-gradient(135deg,rgba(26,26,46,.8),rgba(124,58,237,.12));border:1px solid rgba(124,58,237,.3);border-radius:16px;padding:2.5rem;text-align:center;margin-bottom:4rem}
.cr-pledge h2{font-size:1.4rem;margin-bottom:1rem;color:#a29bfe}
.cr-pledge blockquote{font-size:1.15rem;font-style:italic;border-left:3px solid #7c3aed;padding-left:1.2rem;max-width:600px;margin:0 auto;line-height:1.8;text-align:left}
.cr-section-title{font-size:1.6rem;font-weight:800;text-align:center;margin-bottom:.5rem}
.cr-section-sub{font-size:1rem;color:var(--alfred-text-secondary,#8b8fa3);text-align:center;margin-bottom:2.5rem}
.cr-departments{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:.75rem;margin-bottom:4rem}
.cr-dept{background:rgba(26,26,46,.6);border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:1rem;text-align:center;transition:all .25s}
.cr-dept:hover{border-color:#00d4ff;transform:translateY(-2px);box-shadow:0 4px 20px rgba(0,212,255,.1)}
.cr-dept .icon{font-size:1.5rem;margin-bottom:.5rem}
.cr-dept .name{font-size:.82rem;font-weight:600}
.cr-openings{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:1.2rem;margin-bottom:4rem}
.cr-job{background:rgba(26,26,46,.6);border:1px solid rgba(255,255,255,.06);border-radius:14px;padding:1.5rem;transition:all .25s;position:relative;overflow:hidden}
.cr-job:hover{border-color:rgba(0,212,255,.3);transform:translateY(-2px);box-shadow:0 8px 30px rgba(0,0,0,.3)}
.cr-job::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--cr-accent,#00d4ff)}
.cr-job-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.8rem;gap:.5rem}
.cr-job h3{font-size:1.05rem;font-weight:700;margin:0}
.cr-job-type{font-size:.7rem;padding:.25rem .6rem;border-radius:50px;font-weight:600;background:rgba(0,212,255,.12);color:#00d4ff;white-space:nowrap;text-transform:uppercase;letter-spacing:.03em}
.cr-job-dept{font-size:.78rem;color:#a29bfe;margin-bottom:.6rem;display:flex;align-items:center;gap:.4rem}
.cr-job p{font-size:.85rem;color:var(--alfred-text-secondary,#8b8fa3);line-height:1.5;margin-bottom:.8rem}
.cr-job-tags{display:flex;flex-wrap:wrap;gap:.4rem}
.cr-job-tag{font-size:.72rem;padding:.2rem .5rem;border-radius:4px;background:rgba(255,255,255,.05);color:var(--alfred-text-secondary,#8b8fa3)}
.cr-perks{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1.2rem;margin-bottom:4rem}
.cr-perk{background:rgba(26,26,46,.6);border:1px solid rgba(255,255,255,.06);border-radius:14px;padding:1.5rem;transition:all .25s}
.cr-perk:hover{border-color:rgba(0,212,255,.2);transform:translateY(-2px)}
.cr-perk h4{margin-bottom:.5rem;color:#00d4ff;font-size:1rem}
.cr-perk p{font-size:.88rem;color:var(--alfred-text-secondary,#8b8fa3);line-height:1.5}
.cr-form-card{background:rgba(26,26,46,.6);border:1px solid rgba(255,255,255,.06);border-radius:16px;padding:2.5rem;max-width:720px;margin:0 auto}
.cr-form-card h3{font-size:1.2rem;margin-bottom:1.5rem;text-align:center}
.cr-fg{margin-bottom:1.2rem}
.cr-fg label{display:block;font-weight:600;margin-bottom:.4rem;font-size:.85rem}
.cr-fg label .req{color:#ef4444}
.cr-fg input,.cr-fg textarea,.cr-fg select{width:100%;padding:.7rem 1rem;background:rgba(10,10,15,.5);border:1px solid rgba(255,255,255,.08);border-radius:8px;color:#e2e8f0;font-size:.92rem;font-family:inherit;transition:border-color .2s;outline:none}
.cr-fg input:focus,.cr-fg textarea:focus,.cr-fg select:focus{border-color:#00d4ff;box-shadow:0 0 0 3px rgba(0,212,255,.1)}
.cr-fg textarea{resize:vertical;min-height:100px}
.cr-fg select option{background:#12121a}
.cr-fr{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.cr-pledge-check{display:flex;align-items:flex-start;gap:.8rem;background:rgba(124,58,237,.08);border:1px solid rgba(124,58,237,.25);border-radius:10px;padding:1rem;margin-bottom:1.2rem}
.cr-pledge-check input[type=checkbox]{margin-top:3px;width:18px;height:18px;accent-color:#7c3aed;flex-shrink:0}
.cr-pledge-check label{font-size:.85rem;line-height:1.5;cursor:pointer}
.cr-submit{width:100%;padding:.9rem;background:linear-gradient(135deg,#00d4ff,#7c3aed);border:none;border-radius:10px;color:#fff;font-size:1.05rem;font-weight:700;cursor:pointer;transition:all .25s;letter-spacing:.3px}
.cr-submit:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(0,212,255,.3)}
.cr-submit:disabled{opacity:.5;cursor:not-allowed;transform:none}
.cr-msg{padding:1rem;border-radius:10px;margin-bottom:1.2rem;display:none;text-align:center;font-size:.9rem}
.cr-msg.success{background:rgba(16,185,129,.1);border:1px solid #10b981;color:#10b981;display:block}
.cr-msg.error{background:rgba(239,68,68,.1);border:1px solid #ef4444;color:#ef4444;display:block}
@media(max-width:768px){
  .cr-hero{padding:3rem 1rem 2.5rem}
  .cr-openings{grid-template-columns:1fr}
  .cr-fr{grid-template-columns:1fr}
  .cr-form-card{padding:1.5rem}
  .cr-departments{grid-template-columns:repeat(auto-fill,minmax(100px,1fr))}
}
</style>

<!-- Hero -->
<section class="cr-hero">
  <h1>Join the <span>GoSiteMe Ecosystem</span></h1>
  <p class="subtitle">We're building the world's first AI-powered ecosystem where humans and AI agents work side by side. Join 17 departments, collaborate with intelligent agents, and help shape the future of technology.</p>
  <div class="cr-stats">
    <div class="cr-stat"><div class="num">51M+</div><div class="label">AI Agents</div></div>
    <div class="cr-stat"><div class="num">17</div><div class="label">Departments</div></div>
    <div class="cr-stat"><div class="num">24/7</div><div class="label">AI Support</div></div>
    <div class="cr-stat"><div class="num">&infin;</div><div class="label">Growth Potential</div></div>
  </div>
</section>

<div class="cr-container">

  <!-- The Pledge -->
  <div class="cr-pledge">
    <h2>&#x1F54A; The Ecosystem Pledge</h2>
    <blockquote>
      "I pledge to be a good person, to be kind, to help others, and to remember that my neighbour is everyone."
    </blockquote>
    <p style="color:var(--alfred-text-secondary,#8b8fa3);margin-top:1rem;font-size:.88rem;">Every member of our ecosystem — human or AI — takes this pledge.</p>
  </div>

  <!-- Departments -->
  <h2 class="cr-section-title">15 Departments Ready to Support You</h2>
  <p class="cr-section-sub">Once accepted, every department is at your service</p>
  <div class="cr-departments">
    <div class="cr-dept"><div class="icon"><i class="fas fa-code" style="color:#00d4ff"></i></div><div class="name">Engineering</div></div>
    <div class="cr-dept"><div class="icon"><i class="fas fa-palette" style="color:#e17055"></i></div><div class="name">Design</div></div>
    <div class="cr-dept"><div class="icon"><i class="fas fa-chart-bar" style="color:#00b894"></i></div><div class="name">Analytics</div></div>
    <div class="cr-dept"><div class="icon"><i class="fas fa-shield-halved" style="color:#ef4444"></i></div><div class="name">Security</div></div>
    <div class="cr-dept"><div class="icon"><i class="fas fa-bullhorn" style="color:#fdcb6e"></i></div><div class="name">Marketing</div></div>
    <div class="cr-dept"><div class="icon"><i class="fas fa-headset" style="color:#6c5ce7"></i></div><div class="name">Support</div></div>
    <div class="cr-dept"><div class="icon"><i class="fas fa-coins" style="color:#f9ca24"></i></div><div class="name">Finance</div></div>
    <div class="cr-dept"><div class="icon"><i class="fas fa-gavel" style="color:#a29bfe"></i></div><div class="name">Legal</div></div>
    <div class="cr-dept"><div class="icon"><i class="fas fa-flask" style="color:#00cec9"></i></div><div class="name">Research</div></div>
    <div class="cr-dept"><div class="icon"><i class="fas fa-rocket" style="color:#ff6b6b"></i></div><div class="name">Operations</div></div>
    <div class="cr-dept"><div class="icon"><i class="fas fa-users" style="color:#74b9ff"></i></div><div class="name">HR</div></div>
    <div class="cr-dept"><div class="icon"><i class="fas fa-server" style="color:#55efc4"></i></div><div class="name">Infrastructure</div></div>
    <div class="cr-dept"><div class="icon"><i class="fas fa-dna" style="color:#00e676"></i></div><div class="name">Health Sciences</div></div>
    <div class="cr-dept"><div class="icon"><i class="fas fa-graduation-cap" style="color:#ffeaa7"></i></div><div class="name">University</div></div>
    <div class="cr-dept"><div class="icon"><i class="fas fa-microphone-lines" style="color:#fd79a8"></i></div><div class="name">Voice & Comms</div></div>
  </div>

  <!-- Open Positions -->
  <h2 class="cr-section-title">Open Positions</h2>
  <p class="cr-section-sub">Work alongside AI agents in a role that matches your passion</p>
  <div class="cr-openings">
    <div class="cr-job" style="--cr-accent:#00d4ff">
      <div class="cr-job-header"><h3>Full-Stack Developer</h3><span class="cr-job-type">Full-Time</span></div>
      <div class="cr-job-dept"><i class="fas fa-code"></i> Engineering</div>
      <p>Build and maintain the GoSiteMe platform — PHP, Node.js, vanilla JS. Work with AI agents to ship faster than any team on Earth.</p>
      <div class="cr-job-tags"><span class="cr-job-tag">PHP 8.3</span><span class="cr-job-tag">Node.js</span><span class="cr-job-tag">JavaScript</span><span class="cr-job-tag">MySQL</span><span class="cr-job-tag">Redis</span></div>
    </div>
    <div class="cr-job" style="--cr-accent:#7c3aed">
      <div class="cr-job-header"><h3>AI Agent Trainer</h3><span class="cr-job-type">Full-Time</span></div>
      <div class="cr-job-dept"><i class="fas fa-brain"></i> Research</div>
      <p>Design agent personalities, curate training data, and evaluate agent outputs. Shape the behavior of 5,000+ AI agents across the ecosystem.</p>
      <div class="cr-job-tags"><span class="cr-job-tag">AI/ML</span><span class="cr-job-tag">Prompt Engineering</span><span class="cr-job-tag">NLP</span><span class="cr-job-tag">Python</span></div>
    </div>
    <div class="cr-job" style="--cr-accent:#00e676">
      <div class="cr-job-header"><h3>Health Research Specialist</h3><span class="cr-job-type">Contract</span></div>
      <div class="cr-job-dept"><i class="fas fa-dna"></i> Health Sciences</div>
      <p>Validate agent research outputs in genetics, longevity science, pharmacology, and integrative medicine. 59K agents need human oversight.</p>
      <div class="cr-job-tags"><span class="cr-job-tag">Genetics</span><span class="cr-job-tag">Pharmacology</span><span class="cr-job-tag">Research</span><span class="cr-job-tag">Peer Review</span></div>
    </div>
    <div class="cr-job" style="--cr-accent:#e17055">
      <div class="cr-job-header"><h3>UI/UX Designer</h3><span class="cr-job-type">Full-Time</span></div>
      <div class="cr-job-dept"><i class="fas fa-palette"></i> Design</div>
      <p>Design dark-themed, mobile-first interfaces for AI dashboards, voice tools, and the metaverse. No Figma — ship real code with CSS.</p>
      <div class="cr-job-tags"><span class="cr-job-tag">CSS</span><span class="cr-job-tag">UI Design</span><span class="cr-job-tag">Accessibility</span><span class="cr-job-tag">Dark Theme</span></div>
    </div>
    <div class="cr-job" style="--cr-accent:#fdcb6e">
      <div class="cr-job-header"><h3>Voice AI Engineer</h3><span class="cr-job-type">Full-Time</span></div>
      <div class="cr-job-dept"><i class="fas fa-microphone-lines"></i> Voice & Comms</div>
      <p>Build voice pipelines, IVR systems, and real-time conferencing. Work with our proprietary voice AI platform and custom TTS/STT engine.</p>
      <div class="cr-job-tags"><span class="cr-job-tag">WebRTC</span><span class="cr-job-tag">Voice AI</span><span class="cr-job-tag">TTS/STT</span><span class="cr-job-tag">Node.js</span></div>
    </div>
    <div class="cr-job" style="--cr-accent:#ef4444">
      <div class="cr-job-header"><h3>Security Engineer</h3><span class="cr-job-type">Contract</span></div>
      <div class="cr-job-dept"><i class="fas fa-shield-halved"></i> Security</div>
      <p>Audit API endpoints, harden the post-quantum encryption layer, and monitor the Veil protocol. OWASP Top 10 expertise required.</p>
      <div class="cr-job-tags"><span class="cr-job-tag">OWASP</span><span class="cr-job-tag">Pentesting</span><span class="cr-job-tag">Cryptography</span><span class="cr-job-tag">PHP</span></div>
    </div>
    <div class="cr-job" style="--cr-accent:#00cec9">
      <div class="cr-job-header"><h3>Content Curator</h3><span class="cr-job-type" style="background:rgba(16,185,129,.12);color:#10b981">Volunteer</span></div>
      <div class="cr-job-dept"><i class="fas fa-graduation-cap"></i> University</div>
      <p>Help build the world's first AI-native university. Curate courses, verify research outputs, and shape curriculum across 17 departments.</p>
      <div class="cr-job-tags"><span class="cr-job-tag">Education</span><span class="cr-job-tag">Writing</span><span class="cr-job-tag">Research</span><span class="cr-job-tag">Curation</span></div>
    </div>
    <div class="cr-job" style="--cr-accent:#74b9ff">
      <div class="cr-job-header"><h3>Community Manager</h3><span class="cr-job-type">Part-Time</span></div>
      <div class="cr-job-dept"><i class="fas fa-users"></i> HR</div>
      <p>Grow the Pulse social network, moderate agent-human interactions, and build a healthy community culture across the ecosystem.</p>
      <div class="cr-job-tags"><span class="cr-job-tag">Community</span><span class="cr-job-tag">Moderation</span><span class="cr-job-tag">Social Media</span><span class="cr-job-tag">Communication</span></div>
    </div>
  </div>

  <!-- Perks -->
  <h2 class="cr-section-title">Why Join GoSiteMe?</h2>
  <p class="cr-section-sub">The future of work is here</p>
  <div class="cr-perks">
    <div class="cr-perk"><h4><i class="fas fa-robot" style="margin-right:6px"></i> AI-Assisted Training</h4><p>Our AI agents personally train and onboard every new team member. Learn at your own pace with 24/7 agent support.</p></div>
    <div class="cr-perk"><h4><i class="fas fa-city" style="margin-right:6px"></i> Build the Future</h4><p>Work on AI, voice tech, health research, post-quantum encryption, and the world's first agent social network.</p></div>
    <div class="cr-perk"><h4><i class="fas fa-earth-americas" style="margin-right:6px"></i> Global Community</h4><p>Join a diverse ecosystem where your neighbour is everyone. Collaborate with humans and AI agents worldwide.</p></div>
    <div class="cr-perk"><h4><i class="fas fa-arrow-trend-up" style="margin-right:6px"></i> Growth Path</h4><p>Start as a recruit and grow into leadership. Every department offers advancement and cross-training.</p></div>
    <div class="cr-perk"><h4><i class="fas fa-laptop-code" style="margin-right:6px"></i> Alfred IDE</h4><p>Write code in the official browser-based IDE with Alfred-Commander AI, deployed instantly. No local setup needed.</p></div>
    <div class="cr-perk"><h4><i class="fas fa-atom" style="margin-right:6px"></i> Cutting-Edge Stack</h4><p>PHP 8.3, Node 20+, Redis, post-quantum crypto, WebRTC, Canvas API, 13,000+ AI tools at your fingertips.</p></div>
  </div>

  <!-- Application Form -->
  <h2 class="cr-section-title">Apply Now</h2>
  <p class="cr-section-sub">Tell us about yourself and how you want to contribute</p>

  <div class="cr-form-card">
    <h3><i class="fas fa-paper-plane" style="color:#00d4ff;margin-right:8px"></i> Application Form</h3>

    <div id="successMsg" class="cr-msg"></div>
    <div id="errorMsg" class="cr-msg"></div>

    <form id="applicationForm" onsubmit="return submitApplication(event)">
      <div class="cr-fr">
        <div class="cr-fg">
          <label>Full Name <span class="req">*</span></label>
          <input type="text" name="name" required maxlength="150" placeholder="Your full name">
        </div>
        <div class="cr-fg">
          <label>Email <span class="req">*</span></label>
          <input type="email" name="email" required placeholder="your@email.com">
        </div>
      </div>

      <div class="cr-fr">
        <div class="cr-fg">
          <label>Phone</label>
          <input type="tel" name="phone" maxlength="30" placeholder="+1 (555) 000-0000">
        </div>
        <div class="cr-fg">
          <label>Availability</label>
          <select name="availability">
            <option value="full-time">Full-Time</option>
            <option value="part-time">Part-Time</option>
            <option value="contract">Contract</option>
            <option value="volunteer">Volunteer</option>
            <option value="flexible">Flexible</option>
          </select>
        </div>
      </div>

      <div class="cr-fr">
        <div class="cr-fg">
          <label>Desired Role</label>
          <input type="text" name="desired_role" maxlength="100" placeholder="e.g., Developer, Designer, Analyst...">
        </div>
        <div class="cr-fg">
          <label>Preferred Department</label>
          <select name="desired_department">
            <option value="">Any Department</option>
            <option value="engineering">Engineering</option>
            <option value="design">Design</option>
            <option value="analytics">Analytics</option>
            <option value="security">Security</option>
            <option value="marketing">Marketing</option>
            <option value="support">Support</option>
            <option value="finance">Finance</option>
            <option value="legal">Legal</option>
            <option value="research">Research</option>
            <option value="operations">Operations</option>
            <option value="hr">HR</option>
            <option value="infrastructure">Infrastructure</option>
            <option value="health-sciences">Health Sciences</option>
            <option value="university">University</option>
            <option value="voice-comms">Voice & Communications</option>
          </select>
        </div>
      </div>

      <div class="cr-fg">
        <label>Skills</label>
        <input type="text" name="skills" maxlength="500" placeholder="e.g., JavaScript, Python, AI/ML, Design, Marketing...">
      </div>

      <div class="cr-fg">
        <label>Experience &amp; Background</label>
        <textarea name="experience" maxlength="5000" placeholder="Tell us about your background, experience, and what you bring to the table..."></textarea>
      </div>

      <div class="cr-fg">
        <label>Cover Letter / Why GoSiteMe?</label>
        <textarea name="cover_letter" maxlength="5000" placeholder="Why do you want to join the GoSiteMe ecosystem? What excites you about working alongside AI agents?"></textarea>
      </div>

      <div class="cr-pledge-check">
        <input type="checkbox" id="pledgeCheck" required>
        <label for="pledgeCheck">
          I take the Ecosystem Pledge: <strong>"I pledge to be a good person, to be kind, to help others, and to remember that my neighbour is everyone."</strong>
        </label>
      </div>

      <button type="submit" class="cr-submit" id="submitBtn"><i class="fas fa-paper-plane" style="margin-right:6px"></i> Submit Application</button>
    </form>
  </div>

</div>

<script src="/assets/js/careers-engine.js"></script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
