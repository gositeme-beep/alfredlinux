GoSiteMe web — canonical copies (mega-plan briefing gate)
==========================================================
These files mirror what runs under a domain’s **public_html/** and
**private/**. **gositeme.com** is not versioned in this repo on disk; keep
copies here so changes can be reviewed, pushed with Alfred Linux, then
deployed:

  cp contrib/gositeme-web/mega-plan-ecosystem.php   /path/to/domain/public_html/
  cp contrib/gositeme-web/includes/mega-plan-access.inc.php \
     /path/to/domain/public_html/includes/

  cp contrib/gositeme-web/private/MEGA-PLAN-LOGROTATE.example \
     /path/to/domain/private/
  # edit path inside the example, then install under /etc/logrotate.d/

Operator env (PHP **getenv**): **GOSITEME_MEGA_PLAN_INVITE_SECRET**

Canonical docs (this repo): **docs/MEGA-PLAN-L1-L9.txt**, **docs/MANIFEST-v1.txt**

QGSM whitepaper §12.2 (IPFS / attestation) lives only in live PHP today; see
**QGSM-WHITEPAPER-12.2-SNIPPET.txt** in this folder for the HTML block to merge
into **qgsm-whitepaper.php** on each mirror domain.
