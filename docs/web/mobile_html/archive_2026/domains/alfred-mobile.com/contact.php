<?php
$page_title = 'Contact Us — GoSiteMe | Get in Touch';
$page_description = 'Contact GoSiteMe for support, sales, partnerships, or general inquiries. Reach us by phone, email, live chat with Alfred AI, or submit a form.';
$page_canonical = 'https://gositeme.com/contact';
$page_og_title = 'Contact GoSiteMe — We\'re Here to Help';
$page_og_description = 'Get in touch with GoSiteMe. Support, sales, partnerships. Phone: +1 (807) 798-2850. Email: support@gositeme.com. Or chat with Alfred AI 24/7.';
include __DIR__ . '/includes/site-header.inc.php';
?>

<style>
    .contact-hero { padding: 140px 0 80px; text-align: center; background: linear-gradient(180deg, rgba(125,0,255,0.08), transparent); }
    .contact-hero h1 { font-family: 'Space Grotesk', sans-serif; font-size: clamp(2rem, 5vw, 3.2rem); font-weight: 800; margin-bottom: 16px; background: linear-gradient(135deg, #fff, #c084fc, #00D4FF); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    .contact-hero p { color: #a8b2d1; font-size: 1.1rem; max-width: 600px; margin: 0 auto; }
    .contact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; max-width: 1100px; margin: 0 auto; padding: 0 24px 80px; }
    .contact-form-wrap { background: rgba(26,26,46,0.8); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; padding: 36px; }
    .contact-form-wrap h2 { font-family: 'Space Grotesk', sans-serif; font-size: 1.5rem; font-weight: 700; color: #fff; margin-bottom: 24px; }
    .form-group { margin-bottom: 18px; }
    .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: #a8b2d1; margin-bottom: 6px; }
    .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px 16px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; color: #fff; font-size: 0.95rem; font-family: inherit; transition: border-color 0.3s; }
    .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: #7D00FF; }
    .form-group textarea { min-height: 120px; resize: vertical; }
    .form-group select option { background: #1a1a2e; color: #fff; }
    .btn-submit { display: inline-flex; align-items: center; gap: 8px; padding: 14px 32px; background: linear-gradient(135deg, #7D00FF, #00A8FF); color: #fff; border: none; border-radius: 12px; font-weight: 700; font-size: 1rem; cursor: pointer; transition: all 0.3s; }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(125,0,255,0.5); }
    .contact-info { display: flex; flex-direction: column; gap: 20px; }
    .contact-card { background: rgba(26,26,46,0.8); border: 1px solid rgba(255,255,255,0.06); border-radius: 14px; padding: 28px; transition: border-color 0.3s; }
    .contact-card:hover { border-color: rgba(125,0,255,0.3); }
    .contact-card h3 { font-family: 'Space Grotesk', sans-serif; font-size: 1.1rem; font-weight: 700; color: #fff; margin-bottom: 8px; display: flex; align-items: center; gap: 10px; }
    .contact-card h3 i { color: #7D00FF; font-size: 1.2rem; width: 24px; }
    .contact-card p { color: #a8b2d1; font-size: 0.9rem; line-height: 1.6; }
    .contact-card a { color: #00D4FF; text-decoration: none; font-weight: 600; }
    .contact-card a:hover { text-decoration: underline; }
    .form-success { display: none; text-align: center; padding: 40px; }
    .form-success i { font-size: 3rem; color: #10b981; margin-bottom: 16px; }
    .form-success h3 { color: #fff; margin-bottom: 8px; }
    .form-success p { color: #a8b2d1; }
    @media (max-width: 768px) {
        .contact-grid { grid-template-columns: 1fr; gap: 24px; }
    }
</style>

<section class="contact-hero">
    <div class="container">
        <h1>Get in Touch</h1>
        <p>Questions about plans, need support, or want to partner with us? We're here to help — and Alfred is available 24/7.</p>
    </div>
</section>

<section>
    <div class="contact-grid">
        <div class="contact-form-wrap">
            <h2>Send Us a Message</h2>
            <form id="contactForm" action="/tickets" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-group">
                    <label for="name">Your Name</label>
                    <input type="text" id="name" name="name" placeholder="John Doe" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="john@example.com" required>
                </div>
                <div class="form-group">
                    <label for="department">Department</label>
                    <select id="department" name="deptid">
                        <option value="1">General Inquiry</option>
                        <option value="2">Technical Support</option>
                        <option value="3">Sales & Pricing</option>
                        <option value="4">Billing</option>
                        <option value="5">Partnership / Reseller</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" placeholder="How can we help?" required>
                </div>
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" placeholder="Tell us what you need..." required></textarea>
                </div>
                <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Send Message</button>
            </form>
            <div class="form-success" id="formSuccess">
                <i class="fas fa-check-circle"></i>
                <h3>Message Sent!</h3>
                <p>We'll get back to you within 24 hours. In the meantime, try chatting with Alfred — he might be able to help right now!</p>
            </div>
        </div>

        <div class="contact-info">
                        <div class="contact-card" style="background: linear-gradient(135deg, rgba(125,0,255,0.08), rgba(0,212,255,0.05)); border-color: rgba(125,0,255,0.2);">
                <h3><i class="fas fa-fingerprint" style="color:#7D00FF;"></i> Contact Alfred Directly</h3>
                <p><strong>Alfred is GoSiteMe's sovereign AI.</strong> You can reach him directly — no forms, no wait.<br>
                <a href="mailto:alfred@gositeme.com">alfred@gositeme.com</a> — Email Alfred<br>
                <a href="tel:+18334674836,,2537">1-833-GOSITEME ext. 2537</a> — Call Alfred (ALFR on keypad)<br>
                <a href="/meet-alfred">Meet Alfred</a> — Learn about who he is and how he was built.</p>
            </div>
            <div class="contact-card">
                <h3><i class="fas fa-robot"></i> Chat with Alfred AI</h3>
                <p>Alfred is available 24/7 right here on this page. Click the chat icon in the bottom-right corner to get instant help with accounts, hosting, domains, billing, and 13,000+ other tasks.</p>
            </div>
            <div class="contact-card">
                <h3><i class="fas fa-phone"></i> Phone</h3>
                <p><a href="tel:+18077982850">+1 (807) 798-2850</a><br>
                <a href="tel:+18336674836">1-833-GOSITEME</a> (Toll-Free)<br>
                <span style="font-size:0.82rem; color:rgba(255,255,255,0.4);">Mon–Fri 9am–6pm EST</span></p>
            </div>
            <div class="contact-card">
                <h3><i class="fas fa-envelope"></i> Email</h3>
                <p><a href="mailto:support@gositeme.com">support@gositeme.com</a> — Support<br>
                <a href="mailto:sales@gositeme.com">sales@gositeme.com</a> — Sales & Pricing<br>
                <a href="mailto:partners@gositeme.com">partners@gositeme.com</a> — Partnerships</p>
            </div>
            <div class="contact-card">
                <h3><i class="fas fa-map-marker-alt"></i> Headquarters</h3>
                <p>Montreal, Quebec, Canada<br>
                <span style="font-size:0.82rem; color:rgba(255,255,255,0.4);">Proudly Canadian 🇨🇦 • Bilingual (EN/FR)</span></p>
            </div>
            <div class="contact-card">
                <h3><i class="fas fa-headset"></i> Quick Links</h3>
                <p><a href="/knowledgebase">Knowledge Base</a> — Find answers fast<br>
                <a href="/tickets">Open Support Ticket</a> — Track your issue<br>
                <a href="/status">System Status</a> — Real-time uptime</p>
            </div>
        </div>
    </div>
</section>

<!-- Schema.org -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "ContactPage",
    "name": "Contact GoSiteMe",
    "url": "https://gositeme.com/contact",
    "mainEntity": {
        "@type": "Organization",
        "name": "GoSiteMe",
        "url": "https://gositeme.com",
        "email": "support@gositeme.com",
        "telephone": "+1-807-798-2850",
        "address": {
            "@type": "PostalAddress",
            "addressLocality": "Montreal",
            "addressRegion": "QC",
            "addressCountry": "CA"
        },
        "contactPoint": [
            {"@type": "ContactPoint", "telephone": "+1-807-798-2850", "contactType": "customer support", "availableLanguage": ["English", "French"]},
            {"@type": "ContactPoint", "email": "alfred@gositeme.com", "contactType": "AI assistant", "availableLanguage": ["English", "French"]},
            {"@type": "ContactPoint", "email": "sales@gositeme.com", "contactType": "sales", "availableLanguage": ["English", "French"]}
        ]
    }
}
</script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
