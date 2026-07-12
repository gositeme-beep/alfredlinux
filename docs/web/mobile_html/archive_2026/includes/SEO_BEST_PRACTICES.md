# GoSiteMe SEO Best Practices (Applied & Ongoing)

## What’s Already Implemented

### On-page & meta
- **Title**: ~58 chars, #1 + year + main offer (e.g. `#1 Web Hosting 2025 | AI Website Builder in 60 Sec | From $3.95 | GoSiteMe`).
- **Description**: ~155 chars, benefit-led, CTA, phone (award-winning, 60 seconds, prices, free SSL/domain/migration, 24/7, money-back).
- **Keywords**: Focused list (no stuffing); EN and FR versions.
- **Canonical + hreflang**: EN/FR alternates and x-default.
- **Open Graph**: og:image with width/height (1200×630) for better sharing and crawler handling.
- **Geo**: `geo.region` for US; schema `areaServed` and hreflang cover US & CA.
- **Trust/safety**: `referrer`, `format-detection` (telephone).

### Technical
- **Sitemap**: `/sitemap.xml` with key URLs, lastmod, priority, hreflang; linked in header and (after your update) in `robots.txt`.
- **robots.txt**: Allow all; multiple Sitemap lines including main `https://gositeme.com/sitemap.xml` first.
- **RSS**: Announcements feed linked for discovery.

### Schema (structured data)
- **Organization**: name, url, logo, image, description, knowsAbout, slogan, foundingDate, aggregateRating, hasOfferCatalog, contactPoint (phone, email, hours, areaServed, languages).
- **WebSite**: name, url, publisher, inLanguage, potentialAction (SearchAction).
- **SoftwareApplication**: GoCodeMe (category, OS, offers with price).
- **ItemList**: main nav (SiteNavigationElement).
- **BreadcrumbList**: homepage.
- **HowTo**: “Build a website in 60 seconds” (steps, totalTime).
- **FAQPage**: 6 Q&As with SpeakableSpecification for voice.
- **Review**: 3 customer reviews on Organization.
- **Service list**: Web Hosting, AI Builder, Domains, WordPress, Design, Migration.
- **TechArticle**: “Why GoSiteMe…” with dates, publisher.
- **WebPage**: name, description, primaryImageOfPage, isPartOf.
- **ImageObject**: hero image.
- **Offer**: 30-day money-back (WarrantyPromise).
- **Product/Offer**: pricing plans with prices and URLs.

### Content & UX
- Single H1 per page; clear H2/H3 hierarchy.
- FAQ section with FAQPage schema.
- Comparison table (GoSiteMe vs Bluehost, GoDaddy, Hostinger).
- Internal links to hosting, AI, domains, design, contact.
- Phone number visible (1-833-GOSITEME); contactPoint in schema.

---

## Ongoing Recommendations (to beat the competition)

1. **Content (E-E-A-T)**
   - Add a blog or “Resources” with guides: “Best WordPress hosting 2025”, “How to migrate to GoSiteMe”, “AI website builder vs traditional”.
   - Author bylines and “Last updated” dates on key pages.
   - Link to reputable sources where you cite stats or claims.

2. **Technical**
   - Keep Core Web Vitals in the green (LCP, FID/INP, CLS). You already preload hero and critical CSS.
   - Ensure all important store/product pages are in `/sitemap.xml` or the WHMCS sitemap and linked from `robots.txt`.
   - Use HTTPS everywhere (you do); keep certificates valid.

3. **Local / trust**
   - If you have a physical address, add it to Organization schema and (optionally) a “Contact” or “About” page.
   - Add `sameAs` in Organization schema for official social profiles (Twitter, Facebook, LinkedIn, YouTube) when you have them.

4. **Monitoring**
   - Google Search Console: confirm sitemaps, coverage, mobile usability, rich results.
   - Use Rich Results Test for key pages (home, main product pages).
   - Track rankings for “best web hosting”, “AI website builder”, “WordPress hosting Canada”, etc.

5. **robots.txt**
   - If the file was not updated by the script, add this line at the top of the Sitemap section:
     `Sitemap: https://gositeme.com/sitemap.xml`

---

## Quick checklist before each big launch

- [ ] Title 50–60 chars, description 150–160 chars.
- [ ] Canonical and hreflang correct for EN/FR.
- [ ] At least Organization + WebSite + one of (FAQPage, HowTo, Product/Offer) on key pages.
- [ ] Sitemap includes new URLs; robots.txt lists sitemaps.
- [ ] No broken links; phone and key CTAs work on mobile.
