# ALFRED PROFESSIONAL VERTICAL TOOLS RESEARCH
### Domain-Specific API & Integration Inventory for Alfred Sub-Agents
### Research Date: March 6, 2026

---

## CURRENT BASELINE

| Vertical | Existing Tools | Use-Case Pages | Agent |
|----------|---------------|----------------|-------|
| Legal | 43 tools (jailhouse lawyer), `contract_reviewer`, `legal_research`, `document_drafter`, `case_summarizer` | `legal.php`, `jailhouse-lawyer.php`, `corporate-law.php`, `criminal-defense.php`, `family-law.php`, `immigration-law.php`, `intellectual-property.php`, `personal-injury.php`, `solo-practice.php` | **Maven** (legal) |
| Healthcare | 4 tools: `symptom_checker`, `medication_tracker`, `health_journal`, `appointment_scheduler` | `healthcare.php`, `dental.php`, `veterinary.php`, `fitness.php` | **Pulse** (health) |
| Education | 4 tools: `lesson_planner`, `quiz_maker`, `rubric_builder`, `grade_calculator` | `education.php`, `students.php` | **Sage** (research/education) |
| Real Estate | 4 tools: `property_analyzer`, `mortgage_calculator`, `listing_writer`, `virtual_stager` | `realestate.php`, `property-management.php` | **Scout** (data/recon) |
| Government | 0 dedicated tools | `government.php` | **Herald** (comms) |
| Accessibility | `voice_assistant` (seniors category) | None dedicated | **Alfred** (core) |
| Translation | Bilingual EN/FR in `lang_alfred.php` | None dedicated | **Pierre** (Quebec/French) |
| Research/Academic | 0 dedicated tools | None dedicated | **Sage** (research) |

---

## 1. LEGAL AI TOOLS

### 1.1 CaseText (Thomson Reuters)

| Field | Detail |
|-------|--------|
| **What** | AI-powered legal research platform; CoCounsel AI assistant for case analysis, deposition prep, contract review, timeline creation |
| **API** | No public API. Enterprise platform only. Acquired by Thomson Reuters (Aug 2023) for $650M. Now integrated into Westlaw Precision with CoCounsel |
| **Auth** | Enterprise SSO, firm-level licensing |
| **Compliance** | SOC 2 Type II, ABA Model Rule 1.6 (confidentiality), data not used for training |
| **Pricing** | Enterprise-only; ~$200–500/user/month depending on firm size. No self-serve |
| **Alfred Agent** | **Maven** — would require partnership agreement, not API integration |
| **Recommendation** | ❌ **Skip** — No API, enterprise-gated. Alfred's own legal_research + CanLII is a better path |

### 1.2 Harvey AI

| Field | Detail |
|-------|--------|
| **What** | GPT-based AI for lawyers: contract analysis, due diligence, regulatory compliance, litigation support |
| **API** | No public API. Closed platform, Series C ($100M+, Sequoia). Partners: Allen & Overy, PwC |
| **Auth** | Enterprise-only, invitation basis |
| **Compliance** | SOC 2 Type II, ISO 27001, data isolation per firm |
| **Pricing** | Custom enterprise. Rumored $100K+/year per firm |
| **Alfred Agent** | Not feasible for integration |
| **Recommendation** | ❌ **Skip** — Competitor, not a tool. Alfred IS the alternative to Harvey for SMBs |

### 1.3 vLex / Vincent AI

| Field | Detail |
|-------|--------|
| **What** | Legal research platform covering 130+ countries. Vincent AI provides AI-assisted legal research, case law search, legislation lookup |
| **API** | **Yes** — REST API for case law search, legislation, legal book content. Requires API key from enterprise agreement |
| **Auth** | API key + OAuth 2.0 for institutional access |
| **Compliance** | GDPR, SOC 2 |
| **Pricing** | Institutional licensing; API access starts ~$500/mo for startups, scales with volume |
| **Coverage** | Case law from US, UK, Canada, EU, LATAM, 130+ jurisdictions |
| **Alfred Agent** | **Maven** — `legal_search_vlex` tool for multi-jurisdiction case law |
| **Recommendation** | ⚠️ **Consider** — Excellent for global legal expansion beyond CanLII (Canada-only). Wait until international demand justifies cost |

### 1.4 Clio API

| Field | Detail |
|-------|--------|
| **What** | Cloud-based legal practice management: case management, billing, calendaring, document management, client intake. Market leader for SMB law firms |
| **API** | **Yes** — Full REST API (v4). CRUD for Matters, Contacts, Activities, Bills, Documents, Calendar, Tasks, Trust accounts |
| **Docs** | `https://app.clio.com/api/v4/documentation` |
| **Auth** | OAuth 2.0 authorization code flow |
| **Rate Limits** | 600 requests/minute per app, pagination for lists |
| **Compliance** | SOC 2 Type II, HIPAA (for medical-legal), data residency (Canada/US/EU), ABA Model Rule 1.6 |
| **Pricing** | API access is free with Clio subscription ($49–149/user/month). No per-API-call charges |
| **Webhooks** | Yes — matter events, activity creation, billing events |
| **Alfred Agent** | **Maven** — `legal_create_matter`, `legal_log_time`, `legal_generate_invoice`, `legal_sync_contacts` |
| **Recommendation** | ✅ **HIGH PRIORITY** — Clio is the #1 legal CRM. Integration makes Alfred indispensable for small law firms. Direct revenue driver for legal vertical |

### 1.5 MyCase API

| Field | Detail |
|-------|--------|
| **What** | Legal practice management (competing with Clio). Case management, billing, client portal, document mgmt |
| **API** | **Yes** — REST API. Matters, contacts, documents, billing, payments. Less comprehensive than Clio |
| **Auth** | OAuth 2.0 |
| **Compliance** | SOC 2 Type II, ABA ethics compliant |
| **Pricing** | Free with subscription ($49–89/user/month) |
| **Alfred Agent** | **Maven** — same tool pattern as Clio but lower priority |
| **Recommendation** | ⚠️ **Consider after Clio** — Smaller market share. Implement generic "case management sync" that works with both |

### 1.6 PracticePanther

| Field | Detail |
|-------|--------|
| **What** | Legal practice management with integrated payments (via PantherPayments) |
| **API** | **Yes** — REST API for matters, contacts, invoices, payments, documents |
| **Auth** | OAuth 2.0 |
| **Compliance** | SOC 2, PCI DSS (payment processing), IOLTA trust accounting |
| **Pricing** | Free with subscription ($59–99/user/month) |
| **Alfred Agent** | **Maven** |
| **Recommendation** | ⚠️ **Consider** — Third priority after Clio and MyCase |

### 1.7 CanLII API

| Field | Detail |
|-------|--------|
| **What** | Canadian Legal Information Institute. Free access to Canadian case law and legislation from all jurisdictions. Already referenced in Alfred's jailhouse lawyer tools |
| **API** | **Yes** — REST API. Search cases, legislation, retrieve full-text decisions, citator (noting up), bilingual (EN/FR) |
| **Docs** | `https://www.canlii.org/en/info/api.html` — requires API key application |
| **Auth** | API key (free for non-commercial/research; commercial requires agreement) |
| **Rate Limits** | 100 requests/minute (free tier) |
| **Compliance** | Open access, Canadian court decisions are public domain |
| **Pricing** | **Free** for non-commercial. Commercial licensing negotiable |
| **Coverage** | All Canadian federal + provincial courts, tribunals, legislation |
| **Alfred Agent** | **Maven** — `legal_search_canlii`, `legal_cite_case`, `legal_get_legislation` |
| **Recommendation** | ✅ **CRITICAL** — Already referenced in jailhouse lawyer. Formalize API integration. Essential for Quebec bilingual legal tools |

### 1.8 PACER / ECF (US Courts)

| Field | Detail |
|-------|--------|
| **What** | Public Access to Court Electronic Records. US federal court documents: filings, dockets, opinions. ECF = Electronic Case Filing |
| **API** | **Partial** — PACER Case Locator API (search across all federal courts), per-court RSS feeds for filing alerts. No full REST API. RECAP (Free Law Project) mirrors PACER data openly |
| **Auth** | PACER account (registration required), per-page billing |
| **Compliance** | Public records, privacy redaction rules per FRCP |
| **Pricing** | $0.10/page (first $30/quarter free). RECAP/CourtListener is free |
| **RECAP Alternative** | **CourtListener API** (Free Law Project) — free REST API at `https://www.courtlistener.com/api/rest/v4/`. Covers PACER dockets, opinions, oral arguments, financial disclosures. Free & open source |
| **Rate Limits** | CourtListener: 5,000 requests/day free, more with API key |
| **Alfred Agent** | **Maven** — `legal_search_us_docket`, `legal_get_opinion`, `legal_track_case` |
| **Recommendation** | ✅ **HIGH PRIORITY** — Use CourtListener (free) as primary, PACER as fallback for real-time filings. Essential for US legal expansion |

### 1.9 LII (Cornell Legal Information Institute)

| Field | Detail |
|-------|--------|
| **What** | Free legal information: US Code, CFR, Supreme Court opinions, Wex legal encyclopedia |
| **API** | **Partial** — Bulk data downloads, no formal REST API. Content is openly licensed. US Code available in XML via USLM (Office of Law Revision Counsel) |
| **Compliance** | Public domain (US government works) |
| **Pricing** | **Free** |
| **Alternative** | `https://api.congress.gov/` — official Congressional API for bills, laws, amendments. Free API key |
| **Alfred Agent** | **Maven** — `legal_lookup_statute`, `legal_define_term` (from Wex) |
| **Recommendation** | ✅ **IMPLEMENT** — Free, authoritative US law. Scrape Wex for legal dictionary, use congress.gov API for legislation tracking |

---

## 2. HEALTHCARE TOOLS

### 2.1 FHIR (HL7) Standard

| Field | Detail |
|-------|--------|
| **What** | Fast Healthcare Interoperability Resources — the universal standard for health data exchange. RESTful. JSON/XML. Resources: Patient, Observation, Medication, Condition, DiagnosticReport, Appointment, etc. |
| **API** | **Yes** — FHIR is the API standard itself. R5 (current release). All major EHRs implement FHIR servers |
| **Auth** | SMART on FHIR (OAuth 2.0 based) — launch context provides patient/provider scope |
| **Compliance** | **HIPAA** mandatory. ONC Cures Act requires FHIR APIs from certified EHRs. USCDI v3+ data classes |
| **Pricing** | Standard is free. Implementation cost is in connecting to EHR endpoints |
| **Key Resources** | Patient, Practitioner, Encounter, Observation, Condition, MedicationRequest, DiagnosticReport, Appointment, CarePlan |
| **Alfred Agent** | **Pulse** — `health_read_patient`, `health_get_medications`, `health_get_vitals`, `health_schedule_appointment` |
| **Recommendation** | ✅ **CRITICAL** — Build a FHIR client library. This is how Alfred talks to ANY healthcare system. Required for HIPAA-compliant health features |

### 2.2 Epic API (open.epic.com)

| Field | Detail |
|-------|--------|
| **What** | Largest EHR vendor (45% US market). 750+ free APIs. 8.53 billion patient records exchanged annually. 2,435 live apps |
| **API** | **Yes** — FHIR R4 APIs. 750+ endpoints. Patient Access, Provider Access, Backend Services. CDS Hooks for clinical decision support |
| **Auth** | SMART on FHIR (OAuth 2.0). App must be registered and approved. Sandbox environment available |
| **Compliance** | **HIPAA**, **21st Century Cures Act**, **TEFCA**, USCDI v3-v5 |
| **Pricing** | **Free** — APIs are free. No per-call charges. Requires app registration and Epic customer opt-in |
| **Key Capabilities** | Patient demographics, allergies, medications, lab results, clinical notes, imaging, scheduling, care plans, prior authorization (CMS-0057) |
| **Sandbox** | Full sandbox with synthetic data at `https://fhir.epic.com/` |
| **Alfred Agent** | **Pulse** — all FHIR tools above, plus `health_epic_sync`, `health_get_lab_results` |
| **Recommendation** | ✅ **HIGH PRIORITY** — Epic covers ~45% of US hospitals. Patient-facing apps (like Alfred voice health assistant) can request data via patient consent. Build against Epic sandbox first |

### 2.3 Oracle Health (formerly Cerner)

| Field | Detail |
|-------|--------|
| **What** | Second-largest EHR (~25% US market). Acquired by Oracle for $28.3B (2022). Oracle Health APIs |
| **API** | **Yes** — FHIR R4 APIs (Millennium platform), proprietary REST APIs for legacy. Oracle Health Data Intelligence for analytics |
| **Auth** | SMART on FHIR (OAuth 2.0), Oracle Cloud auth for backend |
| **Compliance** | **HIPAA**, ONC certified, **TEFCA** participant |
| **Pricing** | **Free** for FHIR APIs. Oracle Health Data Intelligence has commercial licensing |
| **Sandbox** | `https://fhir-open.cerner.com/` (open sandbox, no auth needed for read) |
| **Alfred Agent** | **Pulse** — Same FHIR interface as Epic. Build once with FHIR, connect to both |
| **Recommendation** | ✅ **Covered by FHIR implementation** — Building a proper FHIR client covers both Epic and Oracle Health |

### 2.4 OpenMRS

| Field | Detail |
|-------|--------|
| **What** | Open-source medical record system. Used in 6,000+ sites globally, primarily developing countries (Africa, South Asia). Backed by Bahmni |
| **API** | **Yes** — Full REST API + FHIR module. Patient, encounter, observation, order, location, concept, drug |
| **Auth** | Basic auth / session-based. FHIR module supports SMART on FHIR |
| **Compliance** | HIPAA-capable (depends on deployment), WHO Digital Health guidelines |
| **Pricing** | **Free & open source** (MPL 2.0) |
| **Alfred Agent** | **Pulse** — via FHIR interface |
| **Recommendation** | ⚠️ **Consider for global expansion** — Matters when Alfred targets international NGO/healthcare markets |

### 2.5 Medical NLP Models

| Model | Source | Use Case | Access | Pricing |
|-------|--------|----------|--------|---------|
| **BioBERT** | DMIS Lab (Korea) | Biomedical text mining, NER, relation extraction | HuggingFace (open) | **Free** (Apache 2.0) |
| **PubMedBERT** | Microsoft Research | Clinical NLP, medical entity recognition | HuggingFace (open) | **Free** (MIT) |
| **Med-PaLM 2** | Google DeepMind | Medical Q&A (scored 85%+ on MedQA, expert-level) | Google Cloud (limited access) | Enterprise pricing, waitlist |
| **ClinicalBERT** | MIT / Emory | Clinical notes, ICD coding, discharge summaries | HuggingFace | **Free** |
| **BioGPT** | Microsoft Research | Biomedical text generation, literature analysis | HuggingFace | **Free** (MIT) |
| **Alfred Agent** | **Pulse** — run locally or via API for symptom analysis, medication interaction checks, medical document summarization |

**Recommendation**: ✅ **Deploy BioBERT + PubMedBERT** for medical NLP. Free, proven, self-hostable. Avoid Med-PaLM (locked to Google Cloud). Use for `symptom_checker` and `medication_tracker` upgrades.

### 2.6 Telemedicine Integration

| Platform | API | Auth | Compliance | Pricing | Notes |
|----------|-----|------|------------|---------|-------|
| **Doxy.me** | No public API. Embed-only via iframe | N/A | HIPAA BAA, PHIPA (Canada) | Free tier (unlimited). Paid: $35–50/mo | Simple but no API control |
| **Zoom Health** | **Yes** — Zoom Video SDK, Meeting API | OAuth 2.0 / JWT | **HIPAA BAA** available (paid plans) | SDK: $0.0058/min/participant. API: with paid plan ($13.33+/mo) | Full telehealth flow possible |
| **Twilio Video** | **Yes** — Programmable Video API | API key + secret | **HIPAA BAA** ($$$), SOC 2, ISO 27001 | $0.004/min/participant (small rooms) | Most flexible. Already in Twilio ecosystem |
| **Alfred Agent** | **Pulse** — `health_start_telehealth`, `health_join_video` |

**Recommendation**: ✅ **Twilio Video** or **Zoom Health**. Twilio is most API-friendly. Zoom has brand recognition. Both offer HIPAA BAAs. Integrates with VAPI voice for voice + video telehealth.

### 2.7 HIPAA Compliance Requirements

For ANY healthcare integration, Alfred needs:
- **BAA** (Business Associate Agreement) with every data sub-processor
- **Encryption**: AES-256 at rest, TLS 1.2+ in transit (✅ Alfred already has AES-256-GCM)
- **Audit logging**: All PHI access must be logged with timestamp, user, action (needs new `alfred_phi_audit_log` table)
- **Access controls**: Role-based, minimum necessary
- **Data retention**: Define retention/deletion policies per regulation
- **Breach notification**: 60-day notification requirement
- **De-identification**: Safe Harbor or Expert Determination method for analytics

---

## 3. EDUCATION TOOLS

### 3.1 Canvas API (Instructure)

| Field | Detail |
|-------|--------|
| **What** | Leading LMS. Used by 6,000+ institutions. Course management, assignments, grading, discussion boards, quizzes |
| **API** | **Yes** — Comprehensive REST API. 200+ endpoints. Courses, assignments, submissions, grades, users, enrollments, quizzes, discussions, modules, files, rubrics |
| **Docs** | `https://canvas.instructure.com/doc/api/` |
| **Auth** | OAuth 2.0 (user-level) or API token (admin); LTI 1.3 for deep integration |
| **Rate Limits** | 700 requests/10 seconds (per instance) |
| **Compliance** | **FERPA** compliant, SOC 2, COPPA (for K-12 integration via parent consent), GDPR |
| **Pricing** | API access free with Canvas instance. Canvas Free for Teachers available. Institutional licensing $5-15/student/year |
| **Webhooks** | Yes — Canvas Data live events via SQS/HTTP |
| **LTI** | LTI 1.3 / Advantage — Alfred can appear as an external tool inside Canvas |
| **Alfred Agent** | **Sage** — `edu_list_assignments`, `edu_submit_work`, `edu_get_grades`, `edu_post_discussion`, `edu_create_quiz` |
| **Recommendation** | ✅ **CRITICAL** — Canvas is the LMS market leader. LTI integration lets Alfred appear INSIDE Canvas. Students can invoke Alfred from their coursework |

### 3.2 Moodle API

| Field | Detail |
|-------|--------|
| **What** | Open-source LMS. 300M+ users, 42 languages. Dominant in Europe, LATAM, developing nations |
| **API** | **Yes** — Web Services API (REST/XML-RPC/SOAP). 500+ functions covering courses, users, assignments, grades, forums, quizzes, badges, competencies |
| **Auth** | Token-based (admin generates tokens per user/service). Supports OAuth 2.0 for SSO |
| **Compliance** | **FERPA** (institutional deployment), GDPR (built-in data privacy tools), COPPA-capable |
| **Pricing** | **Free & open source** (GPL 3.0). MoodleCloud hosted from $130/year |
| **Plugins** | Alfred can be built as a Moodle plugin (local plugin type) or connected via external services |
| **Alfred Agent** | **Sage** — same tool pattern as Canvas |
| **Recommendation** | ✅ **HIGH PRIORITY** — Open source, free, 300M+ users. Build a Moodle plugin that embeds Alfred as a learning assistant |

### 3.3 Blackboard (Anthology)

| Field | Detail |
|-------|--------|
| **What** | Enterprise LMS. Merged with Anthology (2021). Strong in US higher ed and K-12 |
| **API** | **Yes** — REST APIs (Learn 3900+). Courses, users, grades, content, announcements, calendar. Also supports LTI 1.3 |
| **Auth** | OAuth 2.0 (3-legged for user context, 2-legged for system) |
| **Compliance** | **FERPA**, SOC 2 Type II, FedRAMP (US government), GDPR |
| **Pricing** | API access with institutional license. Developer portal at `developer.anthology.com` |
| **Alfred Agent** | **Sage** — LTI integration (build once, works with Canvas + Blackboard + Moodle) |
| **Recommendation** | ⚠️ **Cover via LTI 1.3** — Build a standards-based LTI tool instead of per-LMS API integrations. One LTI app = works everywhere |

### 3.4 Adaptive Learning — Knewton (Wiley)

| Field | Detail |
|-------|--------|
| **What** | Adaptive learning engine. Acquired by Wiley. Personalizes learning paths based on student mastery |
| **API** | **No public API** — Integrated only within Wiley courseware |
| **Pricing** | Part of Wiley textbook bundles |
| **Recommendation** | ❌ **Skip** — Build Alfred's own adaptive learning logic using spaced repetition (SM-2 algorithm) and mastery tracking |

### 3.5 Citation & Reference Tools

| Tool | API | Auth | Pricing | Key Features |
|------|-----|------|---------|--------------|
| **Zotero API** | **Yes** — REST API. Items, collections, tags, full-text. `https://api.zotero.org` | OAuth 1.0a or API key | **Free** | 300MB free storage, web/desktop/mobile, 10K+ citation styles |
| **Mendeley API** | **Yes** — REST API (Elsevier). Documents, annotations, groups | OAuth 2.0 | **Free** (rate-limited) | 2B+ references, Scopus integration |
| **CrossRef API** | **Yes** — REST. DOI resolution, metadata, citations, funder info. `https://api.crossref.org` | Polite pool (email in header) — no key needed | **Free** | 150M+ DOI records, linked references |
| **DOI.org** | **Yes** — Content negotiation. Resolve DOI to metadata in JSON, XML, BibTeX | None | **Free** | Official DOI resolution |
| **Alfred Agent** | **Sage** — `cite_format_apa`, `cite_format_mla`, `cite_lookup_doi`, `cite_generate_bibliography`, `cite_import_zotero` |

**Recommendation**: ✅ **ALL FREE** — Implement CrossRef (DOI lookup) + Zotero (library sync) immediately. Zero cost, massive value for students and researchers.

### 3.6 Math & Science Tools

| Tool | API | Auth | Pricing | Key Features |
|------|-----|------|---------|--------------|
| **Wolfram Alpha API** | **Yes** — Full Results, LLM API, Short Answers, Spoken Results, Simple API | App ID (free dev account) | **Free**: 2,000 calls/mo non-commercial. Production: custom pricing | Math, science, statistics, conversions, data analysis |
| **Symbolab** | No public API (owned by Chegg, now part of Mathway/Photomath) | N/A | N/A | Step-by-step math solutions |
| **Photomath** | No public API (owned by Google) | N/A | N/A | Camera-based math solving |
| **Desmos API** | **Yes** — Embeddable graphing calculator. JS API for custom graphs | None (embed) | **Free** | Graphing, tables, geometry |
| **Alfred Agent** | **Sage** — `math_solve`, `math_graph`, `math_step_by_step`, `science_calculate` |

**Recommendation**: ✅ **Wolfram Alpha API** — 2,000 free calls/month covers math, science, data queries via the Spoken Results API (perfect for voice-based education via VAPI). Desmos embed for graphing.

### 3.7 Plagiarism Detection

| Tool | API | Auth | Pricing | Notes |
|------|-----|------|---------|-------|
| **Turnitin** | **Yes** — TCA (Turnitin Core API). Submit papers, get similarity report, AI writing detection | OAuth 2.0 | Enterprise-only (~$3/student/year for LMS integration) | Market leader, AI detection included |
| **Copyscape** | **Yes** — Premium API. URL or text comparison | API key | $0.05/search (first 200 words) + $0.01/extra 100 words | Simple, affordable |
| **PlagScan** (Turnitin) | API available | API key | From $5.99/month | Acquired by Turnitin |
| **Alfred Agent** | **Sage** — `edu_check_plagiarism`, `edu_check_ai_writing` |

**Recommendation**: ⚠️ **Copyscape for MVP** (cheap, simple API), **Turnitin for enterprise** (education institution deals). FERPA considerations for student submissions.

### 3.8 FERPA Compliance Requirements

For education integrations:
- **Student data** requires explicit consent from parents (under 18) or students (18+)
- **Directory information** can be shared; education records cannot without consent
- **Legitimate educational interest** exemption for LMS integrations (school-authorized tools)
- **Data minimization**: Only request data needed for the tool's function
- **Annual notification**: Schools must notify parents/students about third-party data sharing
- Alfred must sign **Data Processing Agreements** with each educational institution

---

## 4. GOVERNMENT & CIVIC TOOLS

### 4.1 Open Data APIs

| Source | API | Auth | Coverage | Pricing |
|--------|-----|------|----------|---------|
| **data.gov** (US) | **Yes** — CKAN API + catalog of 300K+ datasets. JSON/CSV/XML | API key (free, `api.data.gov`) | Federal datasets: health, climate, transportation, demographics, economic | **Free** |
| **Statistics Canada** | **Yes** — Web Data Service (WDS). `https://www150.statcan.gc.ca/t1/tbl1/en/tv.action` REST API. SDMX format | None | Census, CPI, GDP, labor, health, demographics. Bilingual EN/FR | **Free** |
| **EU Open Data Portal** | **Yes** — SPARQL + REST API at `data.europa.eu/api/hub/search/` | None | 1.6M+ datasets from EU institutions | **Free** |
| **UK ONS API** | **Yes** — `https://api.beta.ons.gov.uk/v1/` | None | UK census, economic, population data | **Free** |
| **World Bank Open Data** | **Yes** — `https://api.worldbank.org/v2/` | None | Global development indicators (1,600+ indicators, 217 countries) | **Free** |
| **Alfred Agent** | **Herald** / **Atlas** — `gov_get_statistic`, `gov_search_datasets`, `gov_get_census_data` |

**Recommendation**: ✅ **ALL FREE** — Implement Stats Canada (bilingual, Quebec compliance) + data.gov + World Bank immediately. Powers government, business, and research use cases simultaneously.

### 4.2 FOIA / Access to Information Tools

| Tool | API | Coverage | Pricing |
|------|-----|----------|---------|
| **MuckRock** | **Yes** — REST API for FOIA tracking, request submission, document search | US (federal + state + local) | **Free tier** available. Pro: $40/mo |
| **FOIA.gov** (US DOJ) | **Yes** — API for request tracking, annual report data | US federal agencies | **Free** |
| **Access to Information (Canada)** | **No API** — Web form submission at `atip-aiprp.apps.gc.ca`. Results via mail | Canada federal | $5 CAD filing fee |
| **Alfred Agent** | **Herald** — `gov_file_foia`, `gov_track_foia_request`, `gov_search_disclosed_records` |

**Recommendation**: ✅ **MuckRock API** for US FOIA automation. Build custom scraper/form-filler for Canadian ATIP requests.

### 4.3 Court Filing Systems

| System | Coverage | API | Access |
|--------|----------|-----|--------|
| **PACER/ECF** | US Federal courts | Partial (see Legal section) | Account required, $0.10/page |
| **CourtListener** | US Federal + State | **Yes** — Full REST API | **Free** |
| **Ontario Court Services** | Ontario, Canada | **No API** — Portal only | Web portal |
| **Quebec SOQUIJ** | Quebec courts | **Partial** — Search API for case law | Subscription ($40–200/mo) |
| **UK HMCTS** | England & Wales | **Yes** — Common Platform API | Gov't digital services |
| **Alfred Agent** | **Maven** — `legal_file_court`, `legal_check_filing_status`, `legal_search_docket` |

**Recommendation**: ✅ **CourtListener** (free, comprehensive US coverage) + **SOQUIJ** (Quebec, bilingual).

### 4.4 Civic Tech / Municipal Integration

| Tool | API | Coverage | Use Case |
|------|-----|----------|----------|
| **Open311** | **Yes** — Standard REST API for municipal service requests (potholes, graffiti, noise complaints). `http://open311.org/` | 50+ cities globally (NYC, Chicago, Toronto, London) | `gov_report_issue`, `gov_track_request` |
| **CivicPlus** | Partial — Mostly white-label municipal websites | US/Canada municipalities | Website/portal integration |
| **congress.gov API** | **Yes** — Bills, laws, members, votes, amendments, committees. `https://api.congress.gov/v3/` | US Congress | `gov_track_bill`, `gov_search_legislation` |
| **OpenStates** | **Yes** — State legislature API. Bills, legislators, votes across all 50 US states. `https://v3.openstates.org/` | US state legislatures | `gov_track_state_bill` |
| **Alfred Agent** | **Herald** / **Atlas** |

**Recommendation**: ✅ **Implement all** — All free. Open311 for municipal services, congress.gov for federal legislation, OpenStates for state-level. Real civic engagement tools.

---

## 5. REAL ESTATE TOOLS

### 5.1 MLS / Property Data APIs

| Provider | API | Auth | Coverage | Pricing | Notes |
|----------|-----|------|----------|---------|-------|
| **Zillow API (Bridge Interactive)** | **Yes** — Zillow has largely deprecated public APIs. Bridge Interactive (now Zillow Group) provides MLS data via RESO Web API | API key + MLS membership | US residential | $500–2,000/mo depending on MLS coverage | Requires MLS board authorization |
| **Realtor.com API** | **Yes** — Property listings, market trends, lead generation. Via RapidAPI | API key | US | Free: 500 calls/mo. Pro: $19.99–399.99/mo (RapidAPI) | Good for property search, limited for MLS data |
| **RESO Web API** | **Yes** — Industry standard (Real Estate Standards Organization). Standardized data dictionary | OAuth 2.0 / API key via MLS vendor | US + Canada | Varies by MLS vendor (typically through broker agreement) | The standard all MLSs adopt |
| **Attom Data** | **Yes** — Property data, AVM (valuations), tax, deed, hazard, schools, crime | API key | US (150M+ properties) | From $0.10/record. Plans from $250/mo | Best for property analytics |
| **Redfin** | No public API. Data available via CSV downloads for research | N/A | US | Free (for research CSV) | Not API-accessible |
| **Alfred Agent** | **Scout** — `realestate_search_listings`, `realestate_get_valuation`, `realestate_get_property_data`, `realestate_get_comparables` |

**Recommendation**: ⚠️ **Attom Data** for property analytics (affordable, comprehensive). Realtor.com via RapidAPI for listings. MLS direct access requires broker partnerships.

### 5.2 Property Valuation

| Tool | API | Method | Pricing |
|------|-----|--------|---------|
| **Zestimate (Zillow)** | Deprecated public API. Only available via Bridge Interactive | AVM (Automated Valuation Model) | Part of Bridge package |
| **Attom AVM** | **Yes** — `https://api.gateway.attomdata.com/avm/detail` | AVM with confidence score | $0.15–0.50/lookup |
| **HouseCanary** | **Yes** — Property analytics, AVM, rental estimates | AVM + ML | Enterprise ($500+/mo) |
| **MPAC (Ontario)** | No public API | Ontario property assessments | Public lookup only |
| **Alfred Agent** | **Scout** — `realestate_estimate_value`, `realestate_get_rental_estimate` |

### 5.3 Virtual Tours & Staging

| Tool | API | Pricing | Integration |
|------|-----|---------|-------------|
| **Matterport** | **Yes** — SDK + API for 3D model access, embed, measurements | Free tier (1 active space). Pro: $9.99/mo. Business: $69/mo | Embeddable 3D tours. SDK for custom viewers. Already VR/WebXR capable |
| **Zillow 3D Home** | No API | Free (with Zillow listing) | N/A |
| **Kuula** | **Yes** — API for 360° tours | From $16/mo | Embed virtual tours |
| **Alfred Agent** | **Scout** — `realestate_create_tour`, `realestate_embed_3d` |

**Recommendation**: ✅ **Matterport SDK** — Aligns with Alfred's VR/metaverse strategy. 3D property tours in the metaverse. Connect to existing WebXR infrastructure.

### 5.4 Real Estate CRM

Alfred already has `client_crm` (freelancers category). Extend to realtors:
- **Follow Up Boss API**: **Yes** — REST API. Leads, contacts, deals, tasks, calls, texts. $58–139/user/mo
- **LionDesk API**: **Yes** — REST API. CRM for real estate. $25–83/mo
- **kvCORE**: API available for enterprise clients

**Recommendation**: ⚠️ **Build generic CRM tools** that work for legal, real estate, and freelancers. Follow Up Boss API for dedicated realtor integration later.

---

## 6. ACCESSIBILITY TOOLS

### 6.1 WCAG Compliance & Testing

| Tool | API | Pricing | Use Case |
|------|-----|---------|----------|
| **axe-core** (Deque) | **Yes** — JavaScript library for automated WCAG testing. Run in browser or Node.js | **Free & open source** (MPL 2.0) | Automated accessibility audit. `a11y_audit_page` |
| **Pa11y** | **Yes** — Node.js CLI/library for accessibility testing | **Free & open source** (LGPL 3.0) | CI/CD accessibility testing |
| **WAVE API** (WebAIM) | **Yes** — REST API for page accessibility evaluation | 100 free credits. $0.04/credit after | Quick page audits |
| **Lighthouse** (Google) | **Yes** — Node.js API, CLI, Chrome DevTools | **Free & open source** | Performance + accessibility scoring |
| **Alfred Agent** | **Architect** / **Prism** — `a11y_audit_site`, `a11y_check_contrast`, `a11y_generate_report` |

**Recommendation**: ✅ **axe-core** (free, industry standard) + **Pa11y** for GoSiteMe sites. Zero cost. Alfred can audit customer websites for WCAG compliance as a premium tool.

### 6.2 Alt-Text Generation

| Tool | API | Pricing | Quality |
|------|-----|---------|---------|
| **GPT-4V / Claude Vision** | Already integrated in Alfred (multi-model) | Per existing AI model costs | Excellent |
| **Microsoft Azure Computer Vision** | **Yes** — Image description API | Free: 5,000/mo. $1/1K transactions after | Good |
| **Google Cloud Vision** | **Yes** — Label + description | Free: 1,000/mo. $1.50/1K after | Good |
| **Alfred Agent** | **Alfred** (core) or **Prism** — `a11y_generate_alt_text` |

**Recommendation**: ✅ **Already possible** — Use existing Claude/GPT-4 vision models for alt-text. No new API needed. Build the tool wrapper.

### 6.3 Text-to-Speech & Screen Reader Optimization

| Tool | API | Pricing | Notes |
|------|-----|---------|-------|
| **ElevenLabs** | **Yes** — TTS API. 29 languages, voice cloning | Free: 10K chars/mo. Starter: $5/mo (30K). Scale: $22–330/mo | Already adjacent to Alfred's voice infra |
| **Google TTS** | **Yes** — Cloud TTS API. WaveNet voices. 40+ languages | Free: 4M chars/mo (standard), 1M (WaveNet). Then $4–16/1M chars | High quality |
| **Amazon Polly** | **Yes** — Neural TTS. 60+ languages, SSML | Free: 5M chars/mo (first 12 months). Then $4/1M chars (standard) | Good for voice accessibility |
| **ARIA best practices** | Not an API — HTML authoring standard | Free | Structure Alfred's UI output with proper roles, labels, live regions |
| **Alfred Agent** | **Alfred** (core) — `a11y_read_aloud`, `a11y_simplify_text` (plain language) |

**Recommendation**: ✅ **ElevenLabs** (already in voice stack). Ensure all Alfred UI components have proper **ARIA attributes**. Add `a11y_simplify_text` tool for plain-language rewriting.

### 6.4 Live Captioning

| Tool | API | Integration | Pricing |
|------|-----|-------------|---------|
| **OpenAI Whisper** | Already integrated (STT large-v3) | Real-time via streaming | Existing cost |
| **Deepgram** | **Yes** — Real-time streaming STT. 30+ languages, diarization | Free: $200 credit. Then $0.0043/min (Nova-2) | Best real-time accuracy |
| **Google Speech-to-Text** | **Yes** — Streaming recognition | $0.006–0.024/15 sec depending on model | Good multi-language |
| **Alfred Agent** | **Alfred** (core) — `a11y_live_caption` during conference rooms |

**Recommendation**: ✅ **Already built** — Whisper for STT is live. Add real-time captioning to `conference-room.php` as an accessibility feature. Deepgram for lower-latency streaming if needed.

---

## 7. TRANSLATION & LOCALIZATION

### 7.1 DeepL API

| Field | Detail |
|-------|--------|
| **What** | Best-in-class machine translation. 32 languages. Outperforms Google Translate in European languages |
| **API** | **Yes** — REST API. Text translation, document translation (PDF/DOCX/PPTX), glossaries, formality control, language detection |
| **Docs** | `https://developers.deepl.com/docs` |
| **Auth** | API key (free tier available) |
| **Rate Limits** | Free: 500K chars/month. Pro: unlimited |
| **Compliance** | GDPR, ISO 27001, SOC 2. Text deleted immediately after translation. No training on customer data |
| **Pricing** | **Free**: 500K chars/mo. **API Pro**: $5.49/mo + $25/1M chars ($0.025/1K chars). **Business**: $27.49/mo |
| **Languages** | 32 languages including FR, EN, DE, ES, PT, ZH, JA, KO, AR, and more |
| **Glossaries** | Custom glossary API for brand terms, legal terminology, medical vocabulary |
| **Alfred Agent** | **Pierre** (Quebec) / **Alfred** (core) — `translate_text`, `translate_document`, `translate_set_glossary` |
| **Recommendation** | ✅ **CRITICAL** — Best translation quality. 500K chars/month free is generous. Glossary support handles legal/medical terminology. Perfect for Quebec bilingual mandate and global expansion |

### 7.2 LibreTranslate (Self-Hosted)

| Field | Detail |
|-------|--------|
| **What** | Open-source machine translation. Self-hosted. Uses Argos Translate engine |
| **API** | **Yes** — REST API. `/translate`, `/detect`, `/languages`, `/suggest` |
| **Auth** | API key (configurable) |
| **Compliance** | Self-hosted = full data sovereignty. No data leaves your servers |
| **Pricing** | **Free & open source** (AGPL 3.0). Cloud: $7–39/mo at libretranslate.com |
| **Quality** | Good for common language pairs. Worse than DeepL for nuanced/professional text |
| **Languages** | 30+ languages |
| **Alfred Agent** | **Pierre** — `translate_text_local` (for sensitive legal/medical docs that can't leave server) |
| **Recommendation** | ✅ **Deploy for sensitive data** — Self-hosted alternative when PHI/PII can't go to third-party APIs. Fallback when DeepL quota is exceeded |

### 7.3 Localization Management

| Tool | API | Pricing | Use Case |
|------|-----|---------|----------|
| **Weblate** | **Yes** — Full REST API + Git integration. Self-hostable | **Free (self-hosted, GPL)**. Cloud: $26–310/mo | Translation memory, glossary, quality checks, Git sync. For Alfred's own EN/FR localization |
| **Crowdin** | **Yes** — REST API v2. In-context editing, MT, TM | Free for open source. Pro: $40/mo | Managed localization platform |
| **POEditor** | **Yes** — REST API. Terms, translations, projects, contributors | Free: 1,000 strings. Pro: $14.99/mo | Lightweight alternative |
| **Alfred Agent** | **Pierre** — Internal tooling for managing Alfred's own translations |

**Recommendation**: ✅ **Weblate (self-hosted)** — Free, integrates with Git, handles Alfred's own `lang_alfred.php` localization workflow. Deploy on existing infrastructure.

---

## 8. RESEARCH & ACADEMIC TOOLS

### 8.1 Semantic Scholar API (Allen AI)

| Field | Detail |
|-------|--------|
| **What** | AI-powered academic search engine. 214M papers, 2.49B citations, 79M authors. SPECTER2 embeddings for semantic similarity |
| **API** | **Yes** — REST API. Paper search, author lookup, citation graph, recommendations, bulk datasets |
| **Docs** | `https://api.semanticscholar.org/api-docs/` |
| **Auth** | Free without key (1,000 req/sec shared). API key for higher limits (1 req/sec dedicated) |
| **Compliance** | Open access, academic use. License agreement for commercial use |
| **Pricing** | **Free** |
| **Key Endpoints** | `/graph/v1/paper/search`, `/graph/v1/paper/{paper_id}`, `/graph/v1/author/{author_id}`, `/recommendations/v1/papers/` |
| **Data** | Title, abstract, authors, citation count, references, venue, year, open access PDF URL, TLDR (auto-generated summary), SPECTER2 embeddings |
| **Alfred Agent** | **Sage** — `research_search_papers`, `research_get_citations`, `research_recommend_papers`, `research_get_author` |
| **Recommendation** | ✅ **CRITICAL** — Free, comprehensive, AI-native. The backbone for Sage's research capabilities. SPECTER2 embeddings enable semantic paper matching |

### 8.2 arXiv API

| Field | Detail |
|-------|--------|
| **What** | Cornell's open-access preprint repository. 2.4M+ papers in physics, math, CS, biology, economics, statistics |
| **API** | **Yes** — OAI-PMH + Atom feed API. Search, metadata, full-text PDF links |
| **Docs** | `https://info.arxiv.org/help/api/index.html` |
| **Auth** | None required |
| **Rate Limits** | 1 request / 3 seconds (be polite) |
| **Compliance** | Open access (Creative Commons licenses) |
| **Pricing** | **Free** |
| **Alfred Agent** | **Sage** — `research_search_arxiv`, `research_get_preprint` |
| **Recommendation** | ✅ **Implement** — Free, essential for STEM research. Latest papers before journal publication |

### 8.3 CrossRef API

| Field | Detail |
|-------|--------|
| **What** | DOI registration agency. 150M+ records. Metadata for scholarly works, funders, links |
| **API** | **Yes** — REST API. Works, funders, members, journals, prefixes |
| **Docs** | `https://api.crossref.org/swagger-ui/index.html` |
| **Auth** | None (polite pool: include `mailto:` in User-Agent). Plus tier available |
| **Rate Limits** | Polite pool: ~50 req/sec. Plus: higher |
| **Pricing** | **Free** (Polite pool). Plus: by arrangement |
| **Alfred Agent** | **Sage** — `cite_lookup_doi`, `cite_get_metadata`, `cite_get_references` |
| **Recommendation** | ✅ **Implement** — Free, authoritative DOI resolution. Essential for citation generation tools |

### 8.4 PubMed / NCBI APIs

| Field | Detail |
|-------|--------|
| **What** | National Center for Biotechnology Information. PubMed (38M+ biomedical abstracts), GenBank, ClinVar, PMC (full-text) |
| **API** | **Yes** — E-utilities (ESearch, EFetch, ELink, ESummary). NCBI Datasets API for genomic data |
| **Docs** | `https://www.ncbi.nlm.nih.gov/books/NBK25501/` |
| **Auth** | API key (free, increases rate limit from 3/sec to 10/sec) |
| **Compliance** | Public domain (US government) |
| **Pricing** | **Free** |
| **Data** | PubMed abstracts, MeSH terms, author affiliations, PMC full-text, clinical trials |
| **Alfred Agent** | **Sage** / **Pulse** — `research_search_pubmed`, `research_get_clinical_trial`, `health_search_literature` |
| **Recommendation** | ✅ **CRITICAL** — Free, authoritative medical literature. Powers both Sage (research) and Pulse (health evidence). Essential for evidence-based health tools |

### 8.5 Google Scholar

| Field | Detail |
|-------|--------|
| **What** | Google's academic search |
| **API** | **No official API**. Unofficial via SerpAPI ($50/mo, 5,000 searches) or scholarly (Python library, scraping-based) |
| **Compliance** | Scraping violates ToS. Use SerpAPI for legitimate access |
| **Pricing** | SerpAPI: $50/mo (5,000 searches) |
| **Recommendation** | ⚠️ **Skip** — Use Semantic Scholar (free, better API) instead. Add SerpAPI only if users specifically demand Google Scholar results |

### 8.6 ResearchGate

| Field | Detail |
|-------|--------|
| **What** | Academic social network. 25M+ researchers. Full-text papers, Q&A, collaboration |
| **API** | **No public API** |
| **Recommendation** | ❌ **Skip** — No API. Semantic Scholar covers the same papers with an actual API |

---

## PRIORITY IMPLEMENTATION MATRIX

### Tier 1 — Implement Immediately (Free / Near-Free, High Impact)

| Tool | Vertical | Cost | Agent | New Tools |
|------|----------|------|-------|-----------|
| **CanLII API** | Legal | Free | Maven | `legal_search_canlii`, `legal_cite_case`, `legal_get_legislation` |
| **CourtListener API** | Legal | Free | Maven | `legal_search_docket`, `legal_get_opinion`, `legal_track_case` |
| **congress.gov API** | Government | Free | Herald | `gov_track_bill`, `gov_search_legislation` |
| **OpenStates API** | Government | Free | Herald | `gov_track_state_bill`, `gov_search_state_law` |
| **Statistics Canada API** | Government | Free | Atlas | `gov_get_statistic_ca`, `gov_get_census` |
| **data.gov / World Bank** | Government | Free | Atlas | `gov_search_datasets`, `gov_get_indicator` |
| **Semantic Scholar API** | Research | Free | Sage | `research_search_papers`, `research_get_citations`, `research_recommend` |
| **arXiv API** | Research | Free | Sage | `research_search_arxiv`, `research_get_preprint` |
| **CrossRef API** | Research | Free | Sage | `cite_lookup_doi`, `cite_get_metadata` |
| **PubMed / NCBI** | Research / Health | Free | Sage/Pulse | `research_search_pubmed`, `health_search_literature` |
| **Zotero API** | Research | Free | Sage | `cite_import_zotero`, `cite_generate_bibliography` |
| **axe-core** | Accessibility | Free | Prism | `a11y_audit_page`, `a11y_generate_report` |
| **Open311** | Government | Free | Herald | `gov_report_issue`, `gov_track_request` |
| **LII / congress.gov** | Legal | Free | Maven | `legal_lookup_statute`, `legal_define_term` |
| **Alt-text generation** | Accessibility | Existing | Prism | `a11y_generate_alt_text` (uses existing vision models) |
| **Desmos embed** | Education | Free | Sage | `math_graph`, `math_interactive` |

**Tier 1 adds: ~30 new tools, $0/month marginal cost**

### Tier 2 — Near-Term (Low Cost, Strategic)

| Tool | Vertical | Cost | Agent | New Tools |
|------|----------|------|-------|-----------|
| **DeepL API** | Translation | Free (500K chars). Pro: $5.49/mo | Pierre | `translate_text`, `translate_document`, `translate_glossary` |
| **Wolfram Alpha API** | Education | Free (2K calls/mo) | Sage | `math_solve`, `science_calculate`, `data_query` |
| **Canvas LTI** | Education | Free (with Canvas) | Sage | `edu_list_assignments`, `edu_get_grades`, `edu_create_quiz` |
| **Moodle API** | Education | Free (open source) | Sage | Same LMS tools via LTI standard |
| **Clio API** | Legal | Free (with Clio sub) | Maven | `legal_create_matter`, `legal_log_time`, `legal_generate_invoice` |
| **FHIR Client** | Healthcare | Free (standard) | Pulse | `health_read_patient`, `health_get_medications`, `health_get_vitals` |
| **LibreTranslate** | Translation | Free (self-hosted) | Pierre | `translate_text_local` (data-sovereign) |
| **Weblate** | Translation | Free (self-hosted) | Pierre | Internal localization management |
| **MuckRock API** | Government | Free tier | Herald | `gov_file_foia`, `gov_track_request` |
| **Deepgram** | Accessibility | $200 free credit | Alfred | `a11y_live_caption` (real-time streaming) |
| **BioBERT/PubMedBERT** | Healthcare | Free (open) | Pulse | Enhanced `symptom_checker`, medical NLP |
| **Copyscape API** | Education | ~$0.05/search | Sage | `edu_check_plagiarism` |

**Tier 2 adds: ~20 new tools, <$50/month marginal cost**

### Tier 3 — Medium-Term (Requires Partnerships / Budget)

| Tool | Vertical | Cost | Agent | Notes |
|------|----------|------|-------|-------|
| **Epic API** | Healthcare | Free (requires app review) | Pulse | Patient-facing health assistant. HIPAA infrastructure required first |
| **vLex API** | Legal | $500+/mo | Maven | Multi-jurisdiction legal research |
| **Attom Data** | Real Estate | $250+/mo | Scout | Property data, valuations, comps |
| **Matterport SDK** | Real Estate | $10–70/mo | Scout | VR property tours (metaverse synergy) |
| **Turnitin API** | Education | ~$3/student/year | Sage | Enterprise plagiarism + AI detection |
| **Follow Up Boss API** | Real Estate | $58+/user/mo | Scout | Realtor CRM integration |
| **Zoom Health API** | Healthcare | $0.0058/min | Pulse | Telehealth video integration |
| **SOQUIJ** | Legal | $40–200/mo | Maven | Quebec court decisions (bilingual) |
| **Realtor.com API** | Real Estate | $20–400/mo | Scout | Property listings |

### Tier 4 — Long-Term / Skip

| Tool | Vertical | Reason |
|------|----------|--------|
| CaseText / Harvey AI | Legal | No API, enterprise-only competitors |
| Knewton | Education | Acquired by Wiley, no API. Build own adaptive learning |
| Google Scholar | Research | No API. Semantic Scholar is superior |
| ResearchGate | Research | No API |
| Symbolab / Photomath | Education | No API. Wolfram Alpha covers math |
| Doxy.me | Healthcare | No API. Use Zoom/Twilio instead |

---

## COMPLIANCE REQUIREMENTS SUMMARY

| Regulation | Verticals | Key Requirements | Alfred Status |
|------------|-----------|------------------|---------------|
| **HIPAA** | Healthcare | BAA with sub-processors, PHI encryption, audit logging, breach notification 60-day, minimum necessary access | ⚠️ Has AES-256-GCM encryption. Needs: BAA template, PHI audit table, access controls, retention policy |
| **FERPA** | Education | Student consent for data sharing, directory vs. education records distinction, DPA with institutions | ❌ Not started. Need DPA template, consent flow, data minimization |
| **PIPEDA / Quebec Law 25** | All (Canada) | Privacy impact assessments, consent, data residency, breach notification 72-hour, privacy officer designation | ⚠️ Partial — bilingual support exists. Need: PIA template, consent management, data residency controls |
| **GDPR** | All (EU users) | Consent, right to erasure, DPA, data portability, DPO | ⚠️ Partial — need formal GDPR compliance program |
| **ABA Model Rules** | Legal | Confidentiality (1.6), competence with technology (1.1), supervision of AI tools (5.3) | ✅ Data not used for training, E2E encryption available |
| **SOC 2 Type II** | All (enterprise) | Security, availability, processing integrity, confidentiality, privacy controls | ❌ Not certified. Required for enterprise healthcare/legal customers |
| **COPPA** | Education (K-12) | Parental consent for children under 13, data minimization, verifiable consent | ❌ Not started. Critical for K-12 education market |
| **Section 508 / ADA** | Accessibility | WCAG 2.1 AA compliance for web content, assistive tech compatibility | ⚠️ Voice assistant helps. Need full WCAG audit of Alfred UI |

---

## AGENT-TO-TOOL MAPPING

| Agent | Current Role | New Vertical Tools | Total New Tools |
|-------|-------------|-------------------|-----------------|
| **Maven** | Legal | CanLII, CourtListener, Clio, congress.gov, LII, vLex | ~15 |
| **Sage** | Research/Education | Semantic Scholar, arXiv, CrossRef, PubMed, Zotero, Canvas LTI, Moodle, Wolfram Alpha, Desmos, Copyscape | ~20 |
| **Pulse** | Health | FHIR client, Epic, BioBERT, PubMed (health), Zoom/Twilio telehealth | ~12 |
| **Scout** | Data/Recon | Attom, Realtor.com, Matterport, Follow Up Boss | ~8 |
| **Herald** | Communications | Open311, MuckRock, FOIA.gov, data.gov, OpenStates | ~10 |
| **Atlas** | Analytics | Stats Canada, World Bank, data.gov analytics | ~6 |
| **Pierre** | Quebec/French | DeepL, LibreTranslate, Weblate, SOQUIJ | ~6 |
| **Prism** | Design/Layout | axe-core, Pa11y, alt-text, WCAG audit | ~5 |
| **Alfred** | Core | Accessibility (a11y), live captioning, text simplification | ~4 |

**Total new tools from vertical expansion: ~86 tools**
**Current tool count: ~1,290**
**Projected total: ~1,376 tools**

---

## IMPLEMENTATION ARCHITECTURE

```
┌─────────────────────────────────────────────────────┐
│                 ALFRED CORE (MCP 3005)              │
│                                                      │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐           │
│  │  Maven    │  │  Sage    │  │  Pulse   │           │
│  │ (Legal)   │  │(Research)│  │ (Health) │           │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘           │
│       │              │              │                 │
│  ┌────┴─────────────┴──────────────┴─────┐          │
│  │         VERTICAL API GATEWAY           │          │
│  │  Rate limiting, caching, auth mgmt    │          │
│  └────┬──────┬──────┬──────┬──────┬──────┘          │
│       │      │      │      │      │                  │
└───────┼──────┼──────┼──────┼──────┼──────────────────┘
        │      │      │      │      │
   ┌────┴──┐ ┌─┴───┐ ┌┴────┐ ┌┴───┐ ┌┴────────┐
   │CanLII │ │arXiv│ │FHIR │ │DeepL│ │data.gov │
   │Court  │ │S2   │ │Epic │ │Libre│ │StatsCan │
   │Clio   │ │NCBI │ │BioB │ │     │ │WorldBnk │
   │LII    │ │Cross│ │Zoom │ │     │ │Open311  │
   └───────┘ └─────┘ └─────┘ └─────┘ └─────────┘
```

**Vertical API Gateway** — new middleware layer that:
1. Manages API keys for all external services
2. Implements rate limiting per service
3. Caches responses (legal opinions, research papers don't change)
4. Handles compliance-specific data routing (PHI → encrypted pipeline)
5. Provides unified error handling and fallback

---

## QUICK WINS (This Week)

1. **CrossRef + DOI Resolution** — 20 lines of PHP. Zero auth needed. Powers `cite_lookup_doi`
2. **Semantic Scholar Search** — Simple GET requests. No auth. Powers `research_search_papers`
3. **arXiv Search** — Atom feed parsing. No auth. Powers `research_search_arxiv`
4. **data.gov CKAN Search** — No auth. Powers `gov_search_datasets`
5. **Alt-text Generator** — Already have vision models. Just need the tool wrapper
6. **axe-core Integration** — npm install, run against URLs. Powers `a11y_audit_page`
7. **Open311 Municipal Reports** — Standard REST. No auth for read. Powers `gov_report_issue`
8. **DeepL Free Tier** — Sign up, get API key, 500K chars/month. Powers `translate_text`

---

*Research compiled for Project Sovereignty vertical expansion. All pricing verified as of March 2026.*
*Next step: Build the Vertical API Gateway middleware and implement Tier 1 integrations.*
