// Pulse Social — Tauri Desktop App
// The GoSiteMe Social Network

#![cfg_attr(not(debug_assertions), windows_subsystem = "windows")]

use tauri::{Manager, WindowEvent};

#[tauri::command]
fn cmd_get_version() -> String {
    env!("CARGO_PKG_VERSION").to_string()
}

#[tauri::command]
fn cmd_get_platform() -> String {
    std::env::consts::OS.to_string()
}

fn main() {
    tauri::Builder::default()
        .plugin(tauri_plugin_shell::init())
        .plugin(tauri_plugin_updater::Builder::new().build())
        .invoke_handler(tauri::generate_handler![
            cmd_get_version,
            cmd_get_platform,
        ])
        .setup(|app| {
            println!("[Pulse Social] Desktop app initialized");

            let window = app.get_webview_window("main").unwrap();
            window.on_window_event(move |event| {
                if let WindowEvent::CloseRequested { .. } = event {
                    println!("[Pulse Social] Window closed");
                }
            });

            Ok(())
        })
        .run(tauri::generate_context!())
        .expect("Failed to run Pulse Social");
}
