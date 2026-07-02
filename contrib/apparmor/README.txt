Alfred Linux — AppArmor contrib (operator)
==========================================

`usr.local.bin.alfred-mesh` — **complain-mode stub** for `/usr/local/bin/alfred-mesh`.
Shipped in-repo for review; the live ISO installs its own copy from hooks — keep them aligned.

Quick try on a Debian/Ubuntu host with AppArmor enabled:

  sudo install -m 644 usr.local.bin.alfred-mesh /etc/apparmor.d/
  sudo apparmor_parser -r /etc/apparmor.d/usr.local.bin.alfred-mesh
  sudo aa-status | grep alfred-mesh

Then run **`aa-genprof alfred-mesh`** on a machine that exercises mesh commands; merge
suggestions before switching to **enforce**. Full playbook: **`docs/APPARMOR-ALFRED-MESH.txt`**.
