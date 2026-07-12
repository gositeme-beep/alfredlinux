'use strict';

const { getRedis } = require('../redis');

const UPGRADE_URL = process.env.AI_UPGRADE_URL || 'https://gositeme.com/pricing.php';
const TOPUP_URL = process.env.AI_TOPUP_URL || 'https://gositeme.com/pricing.php#topup';

function utcMonth() {
  return new Date().toISOString().slice(0, 7);
}

async function getPaywallVariant(clientId) {
  const redis = getRedis();
  const month = utcMonth();
  const key = `paywall:variant:${month}:${clientId}`;
  const cached = await redis.get(key);
  if (cached === 'A' || cached === 'B') return cached;

  const variant = Math.random() < 0.5 ? 'A' : 'B';
  await redis.set(key, variant, 'EX', 40 * 24 * 60 * 60);
  return variant;
}

function baseCardForKind(kind, details = {}) {
  if (kind === 'daily_usd_cap') {
    return {
      title: 'Daily AI budget reached',
      subtitle: `Used $${details.spent_usd || 0} of $${details.cap_usd || 0} today.`,
      cta_label: 'Upgrade Plan',
      cta_url: UPGRADE_URL,
    };
  }
  if (kind === 'daily_token_cap') {
    return {
      title: 'Daily token limit reached',
      subtitle: `Used ${Number(details.used_tokens || 0).toLocaleString()} of ${Number(details.cap_tokens || 0).toLocaleString()} tokens today.`,
      cta_label: 'Upgrade Plan',
      cta_url: UPGRADE_URL,
    };
  }
  if (kind === 'monthly_token_cap' || kind === 'monthly_token_cap_hard' || kind === 'monthly_token_cap_free') {
    return {
      title: 'Monthly token limit reached',
      subtitle: 'Add tokens now or upgrade your plan to keep coding.',
      cta_label: 'Add Tokens',
      cta_url: TOPUP_URL,
    };
  }
  if (kind === 'team_budget_cap') {
    return {
      title: 'Team budget limit reached',
      subtitle: 'Your team spending guardrail has been reached. Contact your team admin.',
      cta_label: 'View Team Usage',
      cta_url: UPGRADE_URL,
    };
  }

  return {
    title: 'Limit reached',
    subtitle: 'This request hit a usage limit.',
    cta_label: 'Upgrade Plan',
    cta_url: UPGRADE_URL,
  };
}

function applyVariant(card, variant) {
  if (variant === 'B') {
    return {
      ...card,
      title: card.title.replace('reached', 'hit'),
      subtitle: card.subtitle + ' Unlock more capacity instantly.',
      cta_label: card.cta_label === 'Add Tokens' ? 'Top Up Now' : 'Upgrade Now',
    };
  }
  return card;
}

async function buildSmartOffer(clientId, kind, details = {}) {
  const variant = await getPaywallVariant(clientId);
  const baseCard = baseCardForKind(kind, details);
  const card = applyVariant(baseCard, variant);
  return { variant, card };
}

module.exports = {
  buildSmartOffer,
  getPaywallVariant,
};
