#!/bin/bash
echo '#!/bin/sh' > /work/build/chroot/var/lib/dpkg/info/pulseaudio.prerm
echo 'exit 0' >> /work/build/chroot/var/lib/dpkg/info/pulseaudio.prerm
chmod +x /work/build/chroot/var/lib/dpkg/info/pulseaudio.prerm
chroot /work/build/chroot dpkg --configure -a
chroot /work/build/chroot apt-get -f install -y
