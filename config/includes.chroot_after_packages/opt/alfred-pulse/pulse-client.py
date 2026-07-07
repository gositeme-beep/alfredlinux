#!/usr/bin/env python3
import sys
import os
from PyQt5.QtWidgets import QApplication, QMainWindow, QVBoxLayout, QWidget, QShortcut
from PyQt5.QtWebEngineWidgets import QWebEngineView
from PyQt5.QtCore import QUrl
from PyQt5.QtGui import QKeySequence, QIcon

class PulseClient(QMainWindow):
    def __init__(self):
        super().__init__()
        self.setWindowTitle('Alfred Pulse')
        self.setMinimumSize(1000, 700)
        
        # We load the web app directly but inject native OS features
        self.browser = QWebEngineView()
        self.browser.setUrl(QUrl('https://gositeme.com/pulse.php'))
        
        layout = QVBoxLayout()
        layout.setContentsMargins(0, 0, 0, 0)
        layout.addWidget(self.browser)
        
        container = QWidget()
        container.setLayout(layout)
        self.setCentralWidget(container)
        
        # Native Hotkey: Super+P to quick post
        self.shortcut = QShortcut(QKeySequence('Meta+P'), self)
        self.shortcut.activated.connect(self.quick_post)
        
    def quick_post(self):
        # In a full implementation, this opens a native quick-post popup
        # For now, it just focuses the composer
        script = 'document.getElementById(" pulsePostContent\).focus();'
 self.browser.page().runJavaScript(script)

if __name__ == '__main__':
 app = QApplication(sys.argv)
 
 icon_path = '/opt/alfred-pulse/pulse-icon.svg'
 if os.path.exists(icon_path):
 app.setWindowIcon(QIcon(icon_path))
 
 window = PulseClient()
 window.show()
 sys.exit(app.exec_())
