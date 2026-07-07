import sys
import os
import re

def patch_file(filepath, replacements):
    if not os.path.exists(filepath):
        print(f"[Sentinel Auto-Heal V10] ERROR: File not found: {filepath}")
        return
    with open(filepath, 'r') as f:
        content = f.read()

    lock_str = "// Sentinel Auto-Heal V10 Applied"
    if lock_str in content:
        print(f"[Sentinel Auto-Heal V10] Already patched: {filepath}")
        return

    original = content
    for old, new in replacements:
        if old in content:
            content = content.replace(old, new)

    if content != original:
        content = lock_str + "\n" + content
        with open(filepath, 'w') as f:
            f.write(content)
        print(f"[Sentinel Auto-Heal V10] Patched {filepath}")

def patch_regex(filepath, replacements):
    if not os.path.exists(filepath):
        print(f"[Sentinel Auto-Heal V10] ERROR: File not found: {filepath}")
        return
    with open(filepath, 'r') as f:
        content = f.read()

    lock_str = "// Sentinel Auto-Heal V10 Regex Applied"
    if lock_str in content:
        print(f"[Sentinel Auto-Heal V10] Already regex patched: {filepath}")
        return

    original = content
    for pattern, repl in replacements:
        content = re.sub(pattern, repl, content)

    if content != original:
        content = lock_str + "\n" + content
        with open(filepath, 'w') as f:
            f.write(content)
        print(f"[Sentinel Auto-Heal V10] Regex Patched {filepath}")

def main():
    if len(sys.argv) < 2:
        print("Usage: patch_nvidia.py <SRC_DIR>")
        sys.exit(1)
    
    src = sys.argv[1]

    def universal_replace(filename):
        f = os.path.join(src, filename)
        patch_file(f, [
            ("in_irq()", "in_hardirq()"),
            ("del_timer_sync(", "timer_delete_sync(")
        ])

    universal_replace("nvidia/os-interface.c")
    universal_replace("common/inc/nv-time.h")
    universal_replace("common/inc/nv-timer.h")
    universal_replace("nvidia-modeset/nvidia-modeset-linux.c")

    patch_file(os.path.join(src, "nvidia/nv-caps.c"), [
        ("sys_close(", "close_fd(")
    ])

    patch_file(os.path.join(src, "nvidia-drm/nvidia-dma-resv-helper.c"), [
        ("dma_fence_signal(fence)", "dma_fence_signal(fence, NULL)")
    ])

    priv_h = os.path.join(src, "nvidia-drm/nvidia-drm-priv.h")
    patch_file(priv_h, [
        ("#ifndef __NVIDIA_DRM_PRIV_H__\n#define __NVIDIA_DRM_PRIV_H__", 
         "#ifndef __NVIDIA_DRM_PRIV_H__\n#define __NVIDIA_DRM_PRIV_H__\n#include <linux/version.h>\n#if LINUX_VERSION_CODE >= KERNEL_VERSION(7,0,0)\n#define DRM_INFO pr_info\n#define DRM_DEBUG pr_debug\n#define DRM_DEBUG_DRIVER pr_debug\n#define DRM_ERROR pr_err\n#define NV_DRM_DEV_DEBUG_DRIVER(dev, fmt, ...) pr_debug(fmt, ##__VA_ARGS__)\n#endif")
    ])

    patch_file(os.path.join(src, "nvidia-drm/nvidia-drm-drv.c"), [
        ("static struct drm_framebuffer *nv_drm_framebuffer_create(", 
         "#include <linux/version.h>\nstatic struct drm_framebuffer *nv_drm_framebuffer_create("),
        ("struct drm_device *dev,\n    struct drm_file *file,", 
         "struct drm_device *dev,\n    struct drm_file *file,\n#if LINUX_VERSION_CODE >= KERNEL_VERSION(7,0,0)\n    const struct drm_format_info *info,\n#endif"),
        ("&local_cmd);", 
         "#if LINUX_VERSION_CODE >= KERNEL_VERSION(7,0,0)\n            info,\n#endif\n            &local_cmd);")
    ])

    patch_file(os.path.join(src, "nvidia-drm/nvidia-drm-fb.h"), [
        ("struct drm_device *dev,\n    struct drm_file *file,", 
         "struct drm_device *dev,\n    struct drm_file *file,\n#if LINUX_VERSION_CODE >= KERNEL_VERSION(7,0,0)\n    const struct drm_format_info *info,\n#endif")
    ])

    patch_file(os.path.join(src, "nvidia-drm/nvidia-drm-fb.c"), [
        ("struct drm_device *dev,\n    struct drm_file *file,", 
         "struct drm_device *dev,\n    struct drm_file *file,\n#if LINUX_VERSION_CODE >= KERNEL_VERSION(7,0,0)\n    const struct drm_format_info *info,\n#endif"),
        ("&nv_fb->base,\n        cmd);", 
         "&nv_fb->base,\n#if LINUX_VERSION_CODE >= KERNEL_VERSION(7,0,0)\n        info,\n#endif\n        cmd);"),
        ("nv_fb = nv_drm_framebuffer_alloc(dev, file, cmd);", 
         "nv_fb = nv_drm_framebuffer_alloc(dev, file,\n#if LINUX_VERSION_CODE >= KERNEL_VERSION(7,0,0)\n        NULL,\n#endif\n        cmd);")
    ])

    patch_file(os.path.join(src, "nvidia-drm/nvidia-drm-crtc.c"), [
        ("drm_atomic_get_new_plane_state", "drm_atomic_get_plane_state")
    ])

    patch_file(os.path.join(src, "common/inc/nv-mm.h"), [
        ("#ifndef __NV_MM_H__\n#define __NV_MM_H__", "#ifndef __NV_MM_H__\n#define __NV_MM_H__\n#include <linux/version.h>"),
        ("vma->vm_flags |= flags;", 
         "#if LINUX_VERSION_CODE >= KERNEL_VERSION(6,3,0)\n    vm_flags_set(vma, flags);\n#else\n    vma->vm_flags |= flags;\n#endif"),
        ("vma->vm_flags &= ~flags;", 
         "#if LINUX_VERSION_CODE >= KERNEL_VERSION(6,3,0)\n    vm_flags_clear(vma, flags);\n#else\n    vma->vm_flags &= ~flags;\n#endif")
    ])

    patch_file(os.path.join(src, "nvidia/nv-dma.c"), [
        ("#include \"nv-linux.h\"", "#include \"nv-linux.h\"\n#include <linux/version.h>"),
        ("return (ops->map_resource != NULL);", 
         "#if LINUX_VERSION_CODE >= KERNEL_VERSION(7,0,0)\n    return NV_FALSE;\n#else\n    return (ops->map_resource != NULL);\n#endif")
    ])

    patch_regex(os.path.join(src, "nvidia-drm/nvidia-drm-helper.h"), [
        (r'\bfor_each_crtc_in_state\(', r'for_each_new_crtc_in_state('),
        (r'\bfor_each_plane_in_state\(', r'for_each_new_plane_in_state('),
        (r'\bfor_each_connector_in_state\(', r'for_each_new_connector_in_state(')
    ])

    patch_file(os.path.join(src, "nvidia-drm/nvidia-drm-helper.h"), [
        ("(__state)->crtcs[__i].state", "(__state)->crtcs[__i].new_state"),
        ("(__state)->planes[__i].state", "(__state)->planes[__i].new_state"),
        ("(__state)->connectors[__i].state", "(__state)->connectors[__i].new_state")
    ])

    patch_file(os.path.join(src, "nvidia-drm/nvidia-dma-fence-helper.h"), [
        ("#ifndef __NVIDIA_DMA_FENCE_HELPER_H__\n#define __NVIDIA_DMA_FENCE_HELPER_H__", 
         "#ifndef __NVIDIA_DMA_FENCE_HELPER_H__\n#define __NVIDIA_DMA_FENCE_HELPER_H__\n#include <linux/version.h>"),
        ("return dma_fence_signal(fence);", 
         "#if LINUX_VERSION_CODE >= KERNEL_VERSION(7,0,0)\n    dma_fence_signal(fence);\n    return 0;\n#else\n    return dma_fence_signal(fence);\n#endif"),
        ("return dma_fence_signal_locked(fence);", 
         "#if LINUX_VERSION_CODE >= KERNEL_VERSION(7,0,0)\n    dma_fence_signal_locked(fence);\n    return 0;\n#else\n    return dma_fence_signal_locked(fence);\n#endif")
    ])

    patch_file(os.path.join(src, "nvidia-drm/nvidia-drm-gem-nvkms-memory.h"), [
        ("#ifndef __NVIDIA_DRM_GEM_NVKMS_MEMORY_H__\n#define __NVIDIA_DRM_GEM_NVKMS_MEMORY_H__", 
         "#ifndef __NVIDIA_DRM_GEM_NVKMS_MEMORY_H__\n#define __NVIDIA_DRM_GEM_NVKMS_MEMORY_H__\n#include <linux/version.h>\n#if LINUX_VERSION_CODE >= KERNEL_VERSION(7,0,0)\nstruct drm_mode_create_dumb;\n#endif")
    ])

if __name__ == "__main__":
    main()
