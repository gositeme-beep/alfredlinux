# Persistent Experience Overlay

Put durable user settings in state/experience-overlay/ so they survive clean chroot rebuilds.

How it works:
- scripts/sync-canonical-to-build.sh copies state/experience-overlay/ into build/config/includes.chroot/
- Files there become part of the live filesystem root in the final image

Examples:
- state/experience-overlay/etc/skel/.config/...
- state/experience-overlay/etc/motd
- state/experience-overlay/usr/local/bin/...

Do not store secrets there unless you intentionally want them baked into the image.
