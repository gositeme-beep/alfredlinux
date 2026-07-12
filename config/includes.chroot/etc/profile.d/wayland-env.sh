#!/bin/sh
# Alfred Linux ??? Wayland environment for login shells
# Only set if running under Wayland
if [ "$XDG_SESSION_TYPE" = "wayland" ] || [ -n "$WAYLAND_DISPLAY" ]; then
  export MOZ_ENABLE_WAYLAND=1
  export ELECTRON_OZONE_PLATFORM_HINT=auto
  export QT_QPA_PLATFORM="wayland;xcb"
  export GDK_BACKEND="wayland,x11"
  export SDL_VIDEODRIVER="wayland,x11"
  export CLUTTER_BACKEND=wayland
  export _JAVA_AWT_WM_NONREPARENTING=1
  export XCURSOR_THEME=breeze_cursors
  export XCURSOR_SIZE=24
fi
