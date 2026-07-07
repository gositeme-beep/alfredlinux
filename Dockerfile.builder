FROM debian:trixie

# Pre-install all tools required for live-build and ISO assembly
# This ensures the image can run completely disconnected from the internet.
RUN apt-get update -qq && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y -qq \
    squashfs-tools \
    xorriso \
    grub-pc-bin \
    grub-efi-amd64-bin \
    grub-efi-amd64-signed \
    shim-signed \
    isolinux \
    syslinux-common \
    mtools \
    dosfstools \
    cpio \
    rsync \
    zstd \
    file \
    live-build && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

CMD ["bash"]
