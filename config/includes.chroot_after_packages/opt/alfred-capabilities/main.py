import sys
import os
from PyQt5.QtWidgets import (QApplication, QMainWindow, QWidget, QVBoxLayout, 
                             QHBoxLayout, QLabel, QPushButton, QListWidget, 
                             QStackedWidget, QScrollArea, QFrame, QProgressBar,
                             QLineEdit, QFileDialog, QMessageBox)
from PyQt5.QtCore import Qt, QThread, pyqtSignal
from PyQt5.QtGui import QFont

MODELS = {
    "Chat & Reasoning": [
        {"id": "phi3:mini", "name": "Phi-3 Mini", "desc": "Microsoft's highly capable 3.8B parameter model. Extremely fast.", "type": "ollama", "size": "2.4 GB", "gated": False},
        {"id": "llama3:8b", "name": "Llama 3 (8B)", "desc": "Meta's flagship open-weights model. Incredible reasoning capabilities.", "type": "ollama", "size": "4.7 GB", "gated": True},
    ],
    "Vision & Images": [
        {"id": "llava:13b", "name": "LLaVA 13B", "desc": "Vision-language model. Upload images and ask questions.", "type": "ollama", "size": "8.5 GB", "gated": False},
        {"id": "flux-dev", "name": "FLUX.1 Dev", "desc": "Incredible image generator for ComfyUI. (Requires HuggingFace Key).", "type": "comfyui", "size": "24 GB", "gated": True}
    ]
}

class DownloadThread(QThread):
    progress = pyqtSignal(int)
    finished = pyqtSignal(bool)
    def __init__(self, model_id, model_type):
        super().__init__()
        self.model_id = model_id
        self.model_type = model_type
    def run(self):
        try:
            import time
            for i in range(1, 101):
                time.sleep(0.05)
                self.progress.emit(i)
            self.finished.emit(True)
        except Exception:
            self.finished.emit(False)

class ModelCard(QFrame):
    def __init__(self, model_info, api_key_checker):
        super().__init__()
        self.model_info = model_info
        self.api_key_checker = api_key_checker
        self.setObjectName("ModelCard")
        self.setStyleSheet("""
            QFrame#ModelCard { background-color: #2a2a2e; border-radius: 10px; border: 1px solid #3f3f46; }
            QFrame#ModelCard:hover { border: 1px solid #0078D4; }
        """)
        layout = QVBoxLayout(self)
        
        header = QHBoxLayout()
        name_lbl = QLabel(model_info["name"])
        name_lbl.setFont(QFont("Inter", 14, QFont.Bold))
        name_lbl.setStyleSheet("color: white;")
        
        size_lbl = QLabel(model_info["size"])
        size_lbl.setStyleSheet("color: #a1a1aa;")
        header.addWidget(name_lbl)
        header.addStretch()
        header.addWidget(size_lbl)
        
        desc_lbl = QLabel(model_info["desc"])
        desc_lbl.setStyleSheet("color: #d4d4d8;")
        desc_lbl.setWordWrap(True)
        
        bottom = QHBoxLayout()
        self.progress_bar = QProgressBar()
        self.progress_bar.setVisible(False)
        self.progress_bar.setStyleSheet("""
            QProgressBar { border: none; border-radius: 4px; background-color: #3f3f46; height: 8px; color: transparent; }
            QProgressBar::chunk { background-color: #0078D4; border-radius: 4px; }
        """)
        
        self.btn = QPushButton("Install Capability" if not model_info["gated"] else "Install (Requires API Key)")
        self.btn.setStyleSheet("""
            QPushButton { background-color: #0078D4; color: white; border: none; border-radius: 5px; padding: 8px 16px; font-weight: bold; }
            QPushButton:hover { background-color: #106EBE; }
        """)
        self.btn.clicked.connect(self.install)
        
        bottom.addWidget(self.progress_bar)
        bottom.addWidget(self.btn)
        
        layout.addLayout(header)
        layout.addWidget(desc_lbl)
        layout.addLayout(bottom)
        
    def install(self):
        if self.model_info["gated"] and not self.api_key_checker():
            QMessageBox.warning(self, "API Key Required", "This model is gated. Please enter your HuggingFace API key in the Settings tab.")
            return

        self.btn.setEnabled(False)
        self.btn.setText("Downloading...")
        self.progress_bar.setVisible(True)
        self.progress_bar.setValue(0)
        self.thread = DownloadThread(self.model_info["id"], self.model_info["type"])
        self.thread.progress.connect(self.progress_bar.setValue)
        self.thread.finished.connect(self.on_finished)
        self.thread.start()
        
    def on_finished(self, success):
        self.progress_bar.setVisible(False)
        if success:
            self.btn.setText("Installed")
            self.btn.setStyleSheet("background-color: #107C10; color: white; border: none; border-radius: 5px; padding: 8px 16px; font-weight: bold;")
        else:
            self.btn.setText("Failed")
            self.btn.setEnabled(True)

class AlfredCapabilities(QMainWindow):
    def __init__(self):
        super().__init__()
        self.setWindowTitle("Alfred Capabilities")
        self.resize(1000, 700)
        self.setStyleSheet("background-color: #1e1e1e;")
        self.hf_api_key = ""
        
        central = QWidget()
        self.setCentralWidget(central)
        main_layout = QHBoxLayout(central)
        main_layout.setContentsMargins(0, 0, 0, 0)
        main_layout.setSpacing(0)
        
        sidebar = QWidget()
        sidebar.setFixedWidth(250)
        sidebar.setStyleSheet("background-color: #252526; border-right: 1px solid #333;")
        sidebar_layout = QVBoxLayout(sidebar)
        
        title = QLabel("Alfred Capabilities")
        title.setFont(QFont("Inter", 16, QFont.Bold))
        title.setStyleSheet("color: white; padding: 10px;")
        
        self.cat_list = QListWidget()
        self.cat_list.setStyleSheet("""
            QListWidget { background: transparent; border: none; color: #d4d4d8; font-size: 14px; outline: none; }
            QListWidget::item { padding: 12px; border-radius: 5px; }
            QListWidget::item:selected { background-color: #37373d; color: white; }
            QListWidget::item:hover { background-color: #2a2d2e; }
        """)
        
        sidebar_layout.addWidget(title)
        sidebar_layout.addWidget(self.cat_list)
        
        self.stack = QStackedWidget()
        self.stack.setStyleSheet("background-color: #1e1e1e;")
        
        for category, models in MODELS.items():
            self.cat_list.addItem(category)
            page = QScrollArea()
            page.setWidgetResizable(True)
            page.setStyleSheet("QScrollArea { border: none; }")
            container = QWidget()
            layout = QVBoxLayout(container)
            layout.setContentsMargins(30, 30, 30, 30)
            layout.setSpacing(15)
            cat_title = QLabel(category)
            cat_title.setFont(QFont("Inter", 24, QFont.Bold))
            cat_title.setStyleSheet("color: white; margin-bottom: 20px;")
            layout.addWidget(cat_title)
            
            for model in models:
                layout.addWidget(ModelCard(model, self.has_api_key))
            layout.addStretch()
            page.setWidget(container)
            self.stack.addWidget(page)
            
        # Add Settings Page
        self.cat_list.addItem("Settings & Import")
        settings_page = QWidget()
        s_layout = QVBoxLayout(settings_page)
        s_layout.setContentsMargins(30, 30, 30, 30)
        s_title = QLabel("Settings & Manual Import")
        s_title.setFont(QFont("Inter", 24, QFont.Bold))
        s_title.setStyleSheet("color: white; margin-bottom: 20px;")
        
        hf_lbl = QLabel("HuggingFace API Key (For Gated Models):")
        hf_lbl.setStyleSheet("color: #d4d4d8;")
        self.hf_input = QLineEdit()
        self.hf_input.setEchoMode(QLineEdit.Password)
        self.hf_input.setStyleSheet("background-color: #3f3f46; color: white; padding: 8px; border-radius: 4px;")
        self.hf_input.textChanged.connect(self.update_key)
        
        import_lbl = QLabel("Have an offline .gguf or .safetensors model?")
        import_lbl.setStyleSheet("color: #d4d4d8; margin-top: 30px;")
        import_btn = QPushButton("Manually Import Model")
        import_btn.setStyleSheet("background-color: #3f3f46; color: white; padding: 10px; border-radius: 5px;")
        import_btn.clicked.connect(self.manual_import)
        
        s_layout.addWidget(s_title)
        s_layout.addWidget(hf_lbl)
        s_layout.addWidget(self.hf_input)
        s_layout.addWidget(import_lbl)
        s_layout.addWidget(import_btn)
        s_layout.addStretch()
        self.stack.addWidget(settings_page)
        
        self.cat_list.currentRowChanged.connect(self.stack.setCurrentIndex)
        self.cat_list.setCurrentRow(0)
        
        main_layout.addWidget(sidebar)
        main_layout.addWidget(self.stack)

    def has_api_key(self):
        return len(self.hf_api_key) > 5

    def update_key(self, text):
        self.hf_api_key = text

    def manual_import(self):
        file, _ = QFileDialog.getOpenFileName(self, "Select Model File", "", "Models (*.gguf *.safetensors *.bin)")
        if file:
            QMessageBox.information(self, "Importing", f"Importing {os.path.basename(file)} into Alfred Vault...")

if __name__ == "__main__":
    app = QApplication(sys.argv)
    window = AlfredCapabilities()
    window.show()
    sys.exit(app.exec_())
