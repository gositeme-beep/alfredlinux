# AlfredLinux Public Status Site

This folder is a sanitized public feed and media surface for release anticipation.

## What is included

- `index.html` + `styles.css` + `app.js`: public progress website
- `data/public-status.json`: sanitized live status consumed by the page
- `data/about-alfredlinux.json`: safe public product facts
- `assets/generated/*.svg`: social images for posting

## Update pipeline

Run this command to refresh website data and generated images:

```bash
bash /home/gositeme/law/alfredlinux-com-source-live/scripts/ops/update-public-status-and-media.sh
```

This runs:

- `scripts/ops/export-public-status.py`
- `scripts/ops/generate-public-images.py`

## Suggested cron

```bash
*/5 * * * * /home/gositeme/law/alfredlinux-com-source-live/scripts/ops/update-public-status-and-media.sh >/dev/null 2>&1
```

## Publish

Serve this folder as static files from your web server:

- `/home/gositeme/law/alfredlinux-com-source-live/public-status-site`

Recommended URL:

- `https://status.alfredlinux.com`
