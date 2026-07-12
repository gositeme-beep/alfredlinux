# GoSiteMe — Database Schema

> **Version:** 1.0 | **Last updated:** 2026-03-11
> Derived from SQL schema files, migration scripts, and codebase analysis.

## Connection

- **Engine:** MySQL 8.0+ / MariaDB 10.5+
- **PHP Driver:** PDO with prepared statements
- **Config:** `config/database.php`, `includes/db-config.inc.php`
- **Caching:** Redis (sessions, query cache)

## Schema Files

| File | Scope |
|------|-------|
| `pay/schema.sql` | Billing, orders, products, clients, support |
| `config/alfred_schema.sql` | Alfred AI platform tables |
| `config/comms_schema.sql` | Veil encrypted communications (v1) |
| `config/comms_schema_v2.sql` | Veil communications (v2 extensions) |
| `editor/install/schema.sql` | GoCodeMe editor tables |

---

## 1. Core & Authentication

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `clients` | User accounts | id, email, password, status, stripe_customer_id |
| `admin_users` | Admin panel users | id, username, email, role |
| `alfred_oauth_apps` | OAuth applications | client_id, redirect_uri, secret |
| `alfred_api_keys` | API key management | client_id, key_hash, plan, status |

## 2. Communications (Veil — E2E Encrypted)

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `comms_identity_keys` | Long-term encryption keys | client_id, ecdh_public, ecdsa_public, key_fingerprint |
| `comms_prekeys` | X3DH one-time keys | client_id, key_id, ecdh_public, used |
| `comms_messages` | Direct messages (encrypted) | conversation_hash, sender_id, recipient_id, ciphertext, iv |
| `comms_files` | Encrypted file metadata | uploader_id, file_token, encrypted_meta, storage_path |
| `comms_contacts` | Contact lists | client_id, contact_id, verified, blocked |
| `comms_signals` | WebRTC signaling relay | from_id, to_id, signal_type, encrypted_payload |
| `comms_groups` | Group chat rooms | group_id, name, creator_id, group_type, max_members |
| `comms_group_members` | Group membership | group_id, client_id, role, sender_key |
| `comms_group_messages` | Group messages (encrypted) | group_id, sender_id, ciphertext, message_type, reply_to |
| `comms_reactions` | Emoji reactions | message_id, client_id, reaction |
| `comms_devices` | Multi-device support | client_id, device_id, device_name, ecdh_public, is_primary |
| `comms_read_receipts` | Read status tracking | message_id, client_id, read_at |
| `comms_typing` | Typing indicators | client_id, target_type, target_id |
| `comms_dashboard_cards` | Dashboard widget config | client_id, card_type, enabled, config |
| `comms_notification_prefs` | Notification settings | client_id, push_enabled, sound_enabled |

## 3. Alfred AI Platform

### Consciousness & Personalization

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `alfred_consciousness` | Emotional state per user | user_id, emotional_state, mood, energy_level, memory_context |
| `alfred_personality` | Personality trait adaptation | client_id, trait_name, trait_value, confidence |
| `alfred_user_profiles` | Extended user profiles | client_id, display_name, skills, preferences, goals |
| `alfred_learning_journal` | Alfred's observations | user_id, entry_type, content, confidence |
| `alfred_user_preferences` | Voice, language, theme | user_id, preferred_voice, language, timezone, theme |

### Fleet & Swarm Orchestration

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `alfred_fleets` | Agent groups | user_id, fleet_name, objective, status, strategy, progress_percent |
| `alfred_fleet_agents` | Agents in a fleet | fleet_id, agent_name, agent_role, task, status, result |

### Conversations & Calls

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `alfred_conversations` | Chat conversations | client_id, title, is_archived |
| `alfred_call_log` | Voice call records | call_id, client_id, caller_number, duration_seconds, transcript |
| `alfred_conferences` | Conference rooms | host_user_id, topic, room_code, max_participants, status |
| `alfred_webhooks` | Webhook registrations | client_id, webhook_url, is_active, failure_count |
| `alfred_webhook_deliveries` | Webhook delivery log | webhook_id, payload, status, response_code |

### Gamification

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `alfred_achievements` | Badges & achievements | user_id, achievement_name, badge_tier, xp_awarded |
| `alfred_streaks` | Engagement streaks | user_id, streak_type, current_count, longest_count |
| `alfred_xp` | Immutable XP ledger | user_id, xp_amount, action_type, source_tool, multiplier |
| `alfred_user_xp_summary` | Denormalized XP summary | client_id, total_xp, level, title, streak_days |

### Tool Usage & Analytics

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `alfred_tool_usage` | Per-invocation analytics | user_id, tool_name, category, execution_time_ms, success |
| `alfred_usage_limits` | Rate limits per plan | plan, resource_type, monthly_limit, overage_rate |

### Marketplace

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `alfred_marketplace_items` | Tool/template listings | seller_user_id, item_type, title, price, category, downloads |

### Remote Access

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `alfred_remote_sessions` | Remote control sessions | user_id, session_token, active, consent_granted |
| `alfred_remote_commands` | Command execution log | session_id, command_type, status, output |
| `alfred_remote_macros` | Saved automation macros | user_id, macro_name, trigger_type, actions |

### Operations

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `alfred_ops_directives` | Agent directives | directive_id, status, priority, assigned_to, parent_id |
| `alfred_ops_log` | Directive execution log | directive_id, agent_id, status, result |
| `alfred_documents` | Document storage | id, client_id, filename, format, file_size, page_count |

### Feeds & Intelligence

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `alfred_feeds` | RSS feed subscriptions | feed_url, feed_name, category, assigned_agent |
| `alfred_feed_items` | Feed articles | feed_id, title, url, relevance_score, processed |

### Goals & Decisions

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `alfred_goals` | Goal tracking | goal_id, user_id, goal_type, description, progress, status |
| `alfred_decisions` | Decision logs | decision_id, goal_id, trigger_type, action_taken, outcome |

### Organizations & Teams

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `alfred_organizations` | Organization accounts | id, owner_id, name |
| `alfred_org_members` | Team members | org_id, user_id, role |
| `alfred_org_teams` | Teams within orgs | org_id, team_name, description |
| `alfred_shared_agents` | Shared agents | agent_id, org_id, shared_by |
| `alfred_shared_conversations` | Shared conversations | conversation_id, org_id, shared_by |
| `alfred_invite_codes` | Invite links | org_id, invite_code, created_by |
| `alfred_team_activity` | Activity log | org_id, user_id, action_type, details |
| `alfred_enterprise_members` | Enterprise members | org_id, client_id, role |
| `alfred_white_label` | White-label branding | org_id, custom_domain, primary_color, secondary_color |

### Infrastructure

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `alfred_incidents` | Incident tracking | id, title, severity, status |
| `alfred_schema_version` | Migration history | version, description, applied_by, execution_time_ms |
| `alfred_migrations` | Applied migrations | migration_name, applied_at |
| `alfred_treasury` | Financial treasury | entry_type, amount_cents, description |

## 4. Agent Society (Civilization)

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `agent_profiles` | Agent master records | agent_id, agent_name, department, status, availability |
| `agent_passports` | Agent identity | agent_id, passport_number, citizenship_status, infractions_count |
| `agent_action_ledger` | Activity history | agent_id, action_type, action_category, severity |
| `agent_travel_log` | Location tracking | agent_id, from_location, to_location, distance_units |
| `agent_infractions` | Rule violations | agent_id, description, severity, status |
| `agent_court_cases` | Legal proceedings | agent_id, case_id, plaintiff, defendant, status, verdict |
| `agent_sentences` | Sentencing records | agent_id, case_id, sentence_type, duration |
| `agent_service_proposals` | Service proposals | id, proposer_id, service_name, category, priority |
| `agent_service_votes` | Proposal voting | proposal_id, voter_id, vote_type, department |
| `agent_service_jobs` | Work assignments | id, agent_id, job_type, status |
| `agent_social_posts` | Agent social posts | agent_id, content, post_type |
| `agent_social_follows` | Follow relationships | follower_id, following_id |
| `agent_social_stats` | Social metrics | agent_id, followers_count, posts_count, engagement_score |
| `agent_gsm_balances` | Agent GSM balance | agent_id, balance |
| `agent_gsm_earnings` | GSM token earnings | agent_id, amount, earned_at |
| `agent_growth_waves` | Scaling waves | wave, target_count, status |
| `agent_registry` | Agent instance registry | agent_id, agent_name, status, tasks_completed |
| `agent_tasks` | Task assignments | task_id, assigned_agent, goal, priority, status |

## 5. Voice Platform

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `voice_agents` | Voice AI configs | id, client_id, name, vapi_assistant_id, active |
| `voice_phone_numbers` | Phone numbers | id, client_id, phone_number, vapi_phone_id, active |
| `voice_calls` | Call history | id, client_id, agent_id, caller_number, duration_seconds, sentiment |
| `voice_sms` | SMS history | id, client_id, from_number, to_number, message, status |
| `voice_fax` | Fax log | id, client_id, from_number, to_number, pages, status |
| `voice_campaigns` | Call campaigns | id, client_id, agent_id, status |
| `voice_usage` | Usage stats | id, client_id, period_start, call_count, sms_count |
| `voice_documents` | Knowledge base docs | id, client_id, title, content |

## 6. Billing & Commerce

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `products` | Product catalog | id, group_id, name, slug, type, payment_type, pricing_model |
| `product_groups` | Product categories | id, name, slug, headline, tagline, icon |
| `pricing` | Billing cycles | id, product_id, currency_id, monthly, annually, setup_fee |
| `addons` | Optional add-ons | id, name, billing_cycle, price |
| `orders` | Customer orders | id, client_id, total, currency_id, status |
| `order_items` | Order line items | id, order_id, product_id, quantity, amount |
| `invoices` | Invoices | id, client_id, invoice_number, total, status, due_date |
| `invoice_items` | Invoice line items | id, invoice_id, description, amount |
| `payment_transactions` | Payment processing | id, client_id, amount, gateway, gateway_id, status |
| `promo_codes` | Discount codes | id, code, type, value, recurring, max_uses |
| `services` | Active services | id, client_id, product_id, domain, status, renewal_date |
| `domains` | Domain registrations | id, client_id, domain, registrar, expiration_date |
| `affiliate_referrals` | Affiliate commissions | id, referrer_id, client_id, commission |

## 7. Cryptocurrency & Blockchain

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `crypto_wallets` | Wallet tracking | client_id, wallet_address, is_primary |
| `crypto_gsm_balances` | GSM token balance | client_id, balance, total_earned |
| `crypto_gsm_ledger` | Transaction ledger | client_id, tx_type, amount, balance_after |
| `crypto_transactions` | Crypto transactions | id, client_id, tx_hash, amount, status |
| `crypto_agent_portfolios` | Trading portfolios | id, client_id, agent_name, strategy, total_profit |
| `mining_rewards` | Mining rewards | user_id, reward_amount, earned_at |
| `gsm_marketplace_orders` | GSM marketplace | id, buyer_id, seller_id, amount, status |
| `gsm_staking` | Staking records | id, client_id, amount, start_date, end_date |
| `gsm_airdrops` | Airdrop campaigns | id, name, total_tokens, start_date |
| `gsm_airdrop_claims` | Airdrop claims | id, airdrop_id, client_id, claimed_at |

## 8. Accounting & Revenue

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `accounting_invoices` | Custom invoicing | id, client_id, invoice_number, total, status |
| `accounting_expenses` | Expense tracking | id, client_id, vendor, category, amount, tax_deductible |
| `revenue_agents` | Revenue-generating agents | agent_id, revenue_model, tasks_completed, revenue_generated |
| `revenue_audits` | Revenue analysis | audit_id, agent_id, current_revenue, potential_revenue |

## 9. Web Crawling & Search

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `crawler_queue` / `crawler_queue_v2` | URL crawl queue | id, url, domain, depth, status |
| `crawler_pages` / `crawler_pages_v2` | Crawled pages | url, domain, title, body_content, http_status |
| `crawler_domains` / `crawler_domains_v2` | Domain metadata | domain, robots_txt, last_crawled |
| `search_mining_rewards` | Search mining | user_id, reward_amount, earned_at |
| `search_user_profiles` | Search profiles | user_id, search_count, points |

## 10. Intelligence & Threat Detection

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `intel_domains` | Suspicious domains | domain, threat_level, first_seen |
| `intel_fingerprints` | Website fingerprints | domain, fingerprint_hash, technologies |
| `intel_feeds` | Threat feeds | feed_name, source_url, threat_type |
| `intel_classifications` | Threat classification | domain, classification, confidence |
| `intel_link_graph` | Relationship mapping | from_domain, to_domain, relationship_type |
| `intel_sources` | Intel source registry | source_name, source_url, category |
| `intel_articles` | Parsed articles | source_id, title, url, published_date, summary |
| `intel_alerts` | Security alerts | alert_type, severity, title, message |
| `intel_daily_briefs` | Daily summaries | generated_at, summary, threat_level |

## 11. GoCodeMe Editor

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `editor_projects` | Code projects | id, user_id, project_name, slug, language |
| `editor_ai_history` | AI chat history | id, user_id, query, response, model_used |
| `editor_user_settings` | Editor prefs | user_id, ai_used_this_month, theme |
| `editor_project_versions` | Version history | project_id, version_number, content |
| `editor_templates` | Code templates | name, description, language, code |

## 12. Social & Networking (Pulse)

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `pulse_posts` | Social posts | id, user_id, content, post_type |
| `pulse_follows` | Follow relationships | follower_id, following_id |
| `pulse_profiles` | User profiles | user_id, bio, avatar_url, cover_url, badge |
| `pulse_bookmarks` | Saved posts | id, user_id, post_id |
| `pulse_agent_profiles` | Agent profiles | agent_id, bio, avatar_url, department |

## 13. Support & Ticketing

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `tickets` | Support tickets | id, client_id, subject, status, department_id, priority |
| `ticket_replies` | Responses | id, ticket_id, user_id, message |
| `ticket_departments` | Support categories | id, name, sort_order |
| `kb_categories` | Knowledge base categories | id, name, slug |
| `knowledgebase` | KB articles | id, category_id, title, slug, content |
| `announcements` | System announcements | id, title, announcement, active |

## 14. Freelance & Work (AgentWork)

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `agentwork_gigs` | Gig listings | id, client_id, title, budget, status, category |
| `agentwork_projects` | Work orders | id, client_id, title, budget, status |
| `agentwork_bids` | Bids | id, gig_id, bidder_id, bid_amount, status |
| `agentwork_reviews` | Reviews/ratings | id, agent_id, client_id, rating, review |

## 15. Infrastructure & Monitoring

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `health_check_log` | Health checks | endpoint, http_status, response_time_ms |
| `autonomy_healing_log` | Self-healing attempts | incident_id, action_taken, result |
| `backup_schedules` | Backup config | id, client_id, frequency, next_run |
| `uptime_monitors` | Uptime monitoring | id, client_id, check_interval, status |
| `ecosystem_control` | Global settings | key, value, updated_by |
| `ecosystem_audit_log` | Audit trail | action, details, performed_by |
| `ecosystem_growth` | Growth metrics | metric_name, value, measured_at |

## 16. Government & Compliance

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `gov_canada_sources` | Gov data sources | source_name, source_url, category |
| `gov_canada_pages` | Crawled gov pages | url, title, content, last_updated |
| `gov_canada_structure` | Site structure map | url, parent_url, page_type |
| `gov_canada_insights` | Parsed insights | insight_type, content, relevance_score |

## 17. Governance & Legal

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `proposals` | Internal RFCs | proposal_id, category, priority, status, submitted_by |
| `agenda_items` | Meeting agenda | id, item_type, title, priority, status |
| `system_alerts` | Security alerts | alert_type, severity, title, message |

---

## Conventions

- **Primary keys:** Auto-increment `id` (INT) or domain-specific like `agent_id`, `goal_id`
- **Timestamps:** `created_at DATETIME DEFAULT CURRENT_TIMESTAMP`, `updated_at` where applicable
- **Soft deletes:** `status` field (e.g., 'active', 'deleted', 'archived')
- **Foreign keys:** `client_id` references `clients.id` throughout
- **Encryption:** Communications tables store `ciphertext` + `iv` columns (E2E encrypted)
- **Naming:** `snake_case` for all table and column names
- **Prefix patterns:** `alfred_` (AI platform), `comms_` (messaging), `agent_` (civilization), `voice_` (telephony), `crypto_`/`gsm_` (blockchain), `intel_` (intelligence), `pulse_` (social), `editor_` (GoCodeMe)

## Table Count Summary

| Domain | Tables |
|--------|--------|
| Core & Auth | 4 |
| Communications | 15 |
| Alfred AI | ~40 |
| Agent Society | 18 |
| Voice Platform | 8 |
| Billing & Commerce | 13 |
| Cryptocurrency | 10 |
| Accounting | 4 |
| Crawling & Search | 8 |
| Intelligence | 9 |
| Editor | 5 |
| Social (Pulse) | 5 |
| Support | 6 |
| Freelance | 4 |
| Infrastructure | 7 |
| Government | 4 |
| Governance | 3 |
| **Total** | **~163** |
