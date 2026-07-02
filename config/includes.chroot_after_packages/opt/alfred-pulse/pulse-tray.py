#!/usr/bin/env python3
import sys
import os
from PyQt5.QtWidgets import QApplication, QSystemTrayIcon, QMenu, QAction
from PyQt5.QtGui import QIcon

class PulseTray(QApplication):
    def __init__(self, sys_argv):
        super().__init__(sys_argv)
        self.setQuitOnLastWindowClosed(False)
        
        icon_path = '/opt/alfred-pulse/pulse-icon.svg'
        if not os.path.exists(icon_path):
            # Fallback for now
            icon_path = '/usr/share/icons/hicolor/scalable/apps/alfred-logo.svg'
            
        self.tray_icon = QSystemTrayIcon(QIcon(icon_path), self)
        self.tray_icon.setToolTip('Alfred Pulse - Connected')
        
        menu = QMenu()
        
        action_open = QAction('Open Pulse Feed', self)
        action_open.triggered.connect(self.open_app)
        
        action_post = QAction('New Post', self)
        action_post.triggered.connect(self.new_post)
        
        action_quit = QAction('Quit', self)
        action_quit.triggered.connect(self.quit)
        
        menu.addAction(action_open)
        menu.addAction(action_post)
        menu.addSeparator()
        menu.addAction(action_quit)
        
        self.tray_icon.setContextMenu(menu)
        self.tray_icon.show()
        
    def open_app(self):
        os.system('python3 /opt/alfred-pulse/pulse-client.py &')
        
    def new_post(self):
        # We can implement a quick popup here, or just open the app
        os.system('python3 /opt/alfred-pulse/pulse-client.py &')

if __name__ == '__main__':
    app = PulseTray(sys.argv)
    sys.exit(app.exec_())
