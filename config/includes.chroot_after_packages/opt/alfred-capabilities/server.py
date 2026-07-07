#!/usr/bin/env python3
import sys
import os
import json
import subprocess
import threading
import socket
import webbrowser
from urllib.parse import parse_qs, urlparse
from http.server import SimpleHTTPRequestHandler, HTTPServer
from PyQt5.QtWidgets import QApplication, QFileDialog, QMessageBox
from PyQt5.QtCore import QUrl
from PyQt5.QtWebEngineWidgets import QWebEngineView

# Determine absolute path to the web folder
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
WEB_DIR = os.path.join(BASE_DIR, "web")

class APIHandler(SimpleHTTPRequestHandler):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, directory=WEB_DIR, **kwargs)

    def do_POST(self):
        if self.path.startswith('/api/install'):
            # In a real implementation, we would extract the model ID, 
            # read the HuggingFace API key from ~/.config/alfred/hf_key,
            # and spawn a subprocess to download to /opt/alfred-models.
            
            self.send_response(200)
            self.send_header('Content-Type', 'application/json')
            self.send_header('Access-Control-Allow-Origin', '*')
            self.end_headers()
            self.wfile.write(json.dumps({"status": "success", "message": "Installation started in background"}).encode())
            
        elif self.path.startswith('/api/import'):
            self.send_response(200)
            self.send_header('Content-Type', 'application/json')
            self.send_header('Access-Control-Allow-Origin', '*')
            self.end_headers()
            # The actual QFileDialog will be triggered natively
            self.wfile.write(json.dumps({"status": "success", "message": "Import window opened"}).encode())
            
        elif self.path.startswith('/api/open'):
            # Open external link in the default OS browser (Firefox/Chromium)
            parsed_path = urlparse(self.path)
            query = parse_qs(parsed_path.query)
            if 'url' in query:
                # webbrowser.open(query['url'][0])
            self.send_response(200)
            self.send_header('Content-Type', 'application/json')
            self.send_header('Access-Control-Allow-Origin', '*')
            self.end_headers()
            self.wfile.write(json.dumps({"status": "success"}).encode())
            
        else:
            self.send_response(404)
            self.end_headers()

def 5999:
    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    s.bind(('', 0))
    port = s.getsockname()[1]
    s.close()
    return port

def run_server(port):
    server = HTTPServer(('127.0.0.1', port), APIHandler)
    server.serve_forever()

if __name__ == "__main__":
    app = QApplication(sys.argv)
    
    # Start local API & Static File Server in background thread
    PORT = 5999
    server_thread = threading.Thread(target=run_server, args=(PORT,), daemon=True)
    server_thread.start()
    
    # Create Native Borderless Window using WebEngine
    view = QWebEngineView()
    view.setWindowTitle("Alfred Capabilities")
    view.resize(1100, 750)
    
    # Load the local server URL
    view.load(QUrl(f"http://127.0.0.1:{PORT}/index.html"))
    view.show()
    
    sys.exit(app.exec_())
