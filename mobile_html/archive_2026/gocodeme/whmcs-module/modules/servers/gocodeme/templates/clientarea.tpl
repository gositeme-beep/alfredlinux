{* GoCodeMe client area widget *}
<style>
  .gc-widget { font-family: system-ui, -apple-system, sans-serif; max-width: 560px; }
  .gc-widget h4 { margin: 0 0 12px; font-size: 18px; }
  .gc-bar-bg { background: #f0f0f0; border-radius: 8px; overflow: hidden; height: 22px; margin: 10px 0; }
  .gc-bar-fg { height: 100%; transition: width .4s ease; border-radius: 8px; }
  .gc-bar-green  { background: linear-gradient(90deg, #2ecc71, #27ae60); }
  .gc-bar-yellow { background: linear-gradient(90deg, #f1c40f, #f39c12); }
  .gc-bar-red    { background: linear-gradient(90deg, #e74c3c, #c0392b); }
  .gc-plans { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 8px; margin: 16px 0; }
  .gc-plan { background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 8px; padding: 10px; text-align: center; font-size: 13px; }
  .gc-plan.active { background: #eef6ff; border-color: #3498db; }
  .gc-plan strong { display: block; font-size: 15px; color: #2c3e50; }
  .gc-plan small { color: #888; }
</style>

<div class="gc-widget">
  <h4>GoCodeMe — AI Token Usage</h4>
  <p style="margin:4px 0">Plan: <strong style="text-transform:capitalize">{$plan}</strong></p>

  <div class="gc-bar-bg">
    <div class="gc-bar-fg {if $percent >= 100}gc-bar-red{elseif $percent >= 80}gc-bar-yellow{else}gc-bar-green{/if}"
         style="width:{$percent|min:100}%"></div>
  </div>

  <p style="margin:4px 0;font-size:15px">
    <strong>{$used}</strong> / {$limit} tokens used this month
    <span style="color:#888">({$percent}%)</span>
  </p>

  {if $percent >= 100}
    <p style="color:#e74c3c;margin:8px 0"><strong>Monthly limit reached.</strong>
      <a href="{$whmcsUrl}index.php?m=gocodeme_topup">Buy a top-up pack</a> to continue using AI.</p>
  {elseif $percent >= 80}
    <p style="color:#f39c12;margin:8px 0">⚠ Approaching your monthly limit. Consider upgrading.</p>
  {/if}

  <h5 style="margin:16px 0 4px;font-size:14px;color:#666">Plan Token Allowances</h5>
  <div class="gc-plans">
    <div class="gc-plan {if $plan == 'starter'}active{/if}">
      <strong>300K</strong>
      Starter
      <small>$4.99/mo</small>
    </div>
    <div class="gc-plan {if $plan == 'professional'}active{/if}">
      <strong>600K</strong>
      Professional
      <small>$9.99/mo</small>
    </div>
    <div class="gc-plan {if $plan == 'power'}active{/if}">
      <strong>1.2M</strong>
      Power
      <small>$19.99/mo</small>
    </div>
    <div class="gc-plan {if $plan == 'team'}active{/if}">
      <strong>2.5M</strong>
      Team
      <small>$49.99/mo</small>
    </div>
    <div class="gc-plan {if $plan == 'agency'}active{/if}">
      <strong>5M</strong>
      Agency
      <small>$99.99/mo</small>
    </div>
  </div>

  <p style="margin-top:14px">
    <a href="{$whmcsUrl}index.php?m=gocodeme&a=sso_redirect"
       class="btn btn-primary btn-sm" style="padding:6px 16px">Open GoCodeMe Editor</a>
    &nbsp;
    <a href="{$whmcsUrl}clientarea.php?action=productdetails&id={$serviceId}&dosinglesignon=1&modop=custom&a=da_login"
       class="btn btn-default btn-sm" style="border:1px solid #555;padding:6px 16px">Login to DirectAdmin</a>
    &nbsp;
    <a href="https://gositeme.com/middleware/usage"
       class="btn btn-default btn-sm" style="border:1px solid #555;padding:6px 16px" target="_blank">Usage Details</a>
  </p>

  <p style="margin-top:10px;font-size:12px;color:#666">
    By using GoCodeMe, you agree to our
    <a href="https://gositeme.com/quebec-terms-of-use-english.html" target="_blank" rel="noopener">Quebec Terms of Use</a>.
  </p>
</div>
