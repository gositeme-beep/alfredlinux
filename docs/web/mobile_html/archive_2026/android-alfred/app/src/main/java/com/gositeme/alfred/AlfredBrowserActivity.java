package com.gositeme.alfred;

import android.annotation.SuppressLint;
import android.app.AlertDialog;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.content.Intent;
import android.content.pm.PackageInfo;
import android.graphics.Bitmap;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.view.View;
import android.webkit.CookieManager;
import android.webkit.JavascriptInterface;
import android.webkit.PermissionRequest;
import android.webkit.ValueCallback;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.ImageButton;
import android.widget.ProgressBar;
import android.widget.TextView;

import androidx.activity.OnBackPressedCallback;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.splashscreen.SplashScreen;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;

/**
 * AlfredBrowserActivity — Full-featured WebView browser.
 *
 * Features:
 *   - Full WebView with JavaScript enabled
 *   - Download / file upload support
 *   - Camera/Microphone permission grants
 *   - Pull-to-refresh
 *   - Alfred AI integration via JavaScript interface
 *   - Background mining via JS bridge
 */
public class AlfredBrowserActivity extends AppCompatActivity {

    private static final String HOME_URL = "https://gositeme.com/";
    private static final String MINING_CHANNEL_ID = "mining_status";

    private WebView webView;
    private ProgressBar progressBar;
    private TextView urlBar;
    private SwipeRefreshLayout swipeRefresh;
    private ValueCallback<Uri[]> fileUploadCallback;
    private boolean isMiningActive = false;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        SplashScreen.installSplashScreen(this);
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_browser);

        createNotificationChannel();
        initViews();
        setupWebView();
        setupNavigation();

        Intent intent = getIntent();
        String url = HOME_URL;
        if (intent != null && intent.getData() != null) {
            url = intent.getData().toString();
        }
        webView.loadUrl(url);

        checkForUpdates();
    }

    private void initViews() {
        webView = findViewById(R.id.webview);
        progressBar = findViewById(R.id.progress_bar);
        urlBar = findViewById(R.id.url_bar);
        swipeRefresh = findViewById(R.id.swipe_refresh);

        swipeRefresh.setColorSchemeColors(0xFF3B82F6);
        swipeRefresh.setOnRefreshListener(() -> webView.reload());
    }

    @SuppressLint("SetJavaScriptEnabled")
    private void setupWebView() {
        WebSettings settings = webView.getSettings();
        settings.setJavaScriptEnabled(true);
        settings.setDomStorageEnabled(true);
        settings.setDatabaseEnabled(true);
        settings.setAllowFileAccess(false);
        settings.setCacheMode(WebSettings.LOAD_DEFAULT);
        settings.setMixedContentMode(WebSettings.MIXED_CONTENT_NEVER_ALLOW);
        settings.setMediaPlaybackRequiresUserGesture(false);
        settings.setUserAgentString(settings.getUserAgentString() + " GoSiteMe-Alfred/1.0 Alfred-Browser");

        CookieManager.getInstance().setAcceptCookie(true);
        CookieManager.getInstance().setAcceptThirdPartyCookies(webView, true);

        webView.addJavascriptInterface(new AlfredJSBridge(), "AlfredBridge");

        webView.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
                String url = request.getUrl().toString();
                if (url.contains("gositeme.com")) {
                    return false;
                }
                Intent intent = new Intent(Intent.ACTION_VIEW, request.getUrl());
                startActivity(intent);
                return true;
            }

            @Override
            public void onPageStarted(WebView view, String url, Bitmap favicon) {
                progressBar.setVisibility(View.VISIBLE);
                urlBar.setText(url.replace("https://gositeme.com", ""));
            }

            @Override
            public void onPageFinished(WebView view, String url) {
                progressBar.setVisibility(View.GONE);
                swipeRefresh.setRefreshing(false);
                urlBar.setText(url.replace("https://gositeme.com", ""));

                if (url.contains("gositeme.com")) {
                    injectBridge();
                }
            }
        });

        webView.setWebChromeClient(new WebChromeClient() {
            @Override
            public void onProgressChanged(WebView view, int newProgress) {
                progressBar.setProgress(newProgress);
                if (newProgress >= 100) {
                    progressBar.setVisibility(View.GONE);
                }
            }

            @Override
            public void onPermissionRequest(PermissionRequest request) {
                String origin = request.getOrigin().toString();
                if (origin.contains("gositeme.com")) {
                    request.grant(request.getResources());
                } else {
                    request.deny();
                }
            }

            @Override
            public boolean onShowFileChooser(WebView webView, ValueCallback<Uri[]> callback,
                                           FileChooserParams params) {
                if (fileUploadCallback != null) {
                    fileUploadCallback.onReceiveValue(null);
                }
                fileUploadCallback = callback;
                Intent intent = params.createIntent();
                try {
                    startActivityForResult(intent, 100);
                } catch (Exception e) {
                    fileUploadCallback = null;
                    return false;
                }
                return true;
            }
        });

        webView.setDownloadListener((url, userAgent, contentDisposition, mimetype, contentLength) -> {
            Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse(url));
            startActivity(intent);
        });
    }

    private void setupNavigation() {
        ImageButton btnBack = findViewById(R.id.btn_back);
        ImageButton btnForward = findViewById(R.id.btn_forward);
        ImageButton btnHome = findViewById(R.id.btn_home);
        ImageButton btnAlfred = findViewById(R.id.btn_alfred);
        ImageButton btnDashboard = findViewById(R.id.btn_dashboard);

        btnBack.setOnClickListener(v -> { if (webView.canGoBack()) webView.goBack(); });
        btnForward.setOnClickListener(v -> { if (webView.canGoForward()) webView.goForward(); });
        btnHome.setOnClickListener(v -> webView.loadUrl(HOME_URL));
        btnAlfred.setOnClickListener(v -> webView.loadUrl("https://gositeme.com/alfred.php"));
        btnDashboard.setOnClickListener(v -> webView.loadUrl("https://gositeme.com/dashboard.php"));

        getOnBackPressedDispatcher().addCallback(this, new OnBackPressedCallback(true) {
            @Override
            public void handleOnBackPressed() {
                if (webView.canGoBack()) {
                    webView.goBack();
                } else {
                    finish();
                }
            }
        });
    }

    private void injectBridge() {
        webView.evaluateJavascript(
            "(function() {" +
            "  if (window.__alfredBridgeInjected) return;" +
            "  window.__alfredBridgeInjected = true;" +
            "  window.isAlfredBrowser = true;" +
            "  window.alfredVersion = '1.0';" +
            "  console.log('[Alfred Browser] Bridge injected');" +
            "})()", null);
    }

    private void createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            NotificationChannel channel = new NotificationChannel(
                    MINING_CHANNEL_ID, "Mining Status", NotificationManager.IMPORTANCE_LOW);
            channel.setDescription("Shows mining activity and GSM earnings");
            NotificationManager manager = getSystemService(NotificationManager.class);
            if (manager != null) manager.createNotificationChannel(channel);
        }
    }

    private void checkForUpdates() {
        new Thread(() -> {
            try {
                String currentVersion = getAppVersionName();
                URL url = new URL("https://gositeme.com/api/app-updates.php?action=check&app=alfred-android&version=" + currentVersion);
                HttpURLConnection conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod("GET");
                conn.setConnectTimeout(5000);
                conn.setReadTimeout(5000);

                if (conn.getResponseCode() == 200) {
                    BufferedReader reader = new BufferedReader(new InputStreamReader(conn.getInputStream()));
                    StringBuilder sb = new StringBuilder();
                    String line;
                    while ((line = reader.readLine()) != null) sb.append(line);
                    reader.close();

                    JSONObject json = new JSONObject(sb.toString());
                    if (json.optBoolean("update_available", false)) {
                        String latestVersion = json.optString("latest_version", "");
                        String releaseNotes = json.optString("release_notes", "");
                        String downloadUrl = json.optString("download_url", "");
                        runOnUiThread(() -> showUpdateDialog(latestVersion, releaseNotes, downloadUrl));
                    }
                }
                conn.disconnect();
            } catch (Exception e) {
                // Non-critical
            }
        }).start();
    }

    private void showUpdateDialog(String version, String notes, String downloadUrl) {
        new AlertDialog.Builder(this, android.R.style.Theme_DeviceDefault_Dialog)
                .setTitle("Update Available — v" + version)
                .setMessage(notes + "\n\nUpdate now for the best experience.")
                .setPositiveButton("Update", (dialog, which) -> {
                    Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse(downloadUrl));
                    startActivity(intent);
                })
                .setNegativeButton("Later", null)
                .setCancelable(true)
                .show();
    }

    private String getAppVersionName() {
        try {
            PackageInfo pInfo = getPackageManager().getPackageInfo(getPackageName(), 0);
            return pInfo.versionName;
        } catch (Exception e) {
            return "1.0.0";
        }
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, Intent data) {
        super.onActivityResult(requestCode, resultCode, data);
        if (requestCode == 100 && fileUploadCallback != null) {
            Uri[] results = null;
            if (resultCode == RESULT_OK && data != null) {
                String dataString = data.getDataString();
                if (dataString != null) {
                    results = new Uri[]{Uri.parse(dataString)};
                }
            }
            fileUploadCallback.onReceiveValue(results);
            fileUploadCallback = null;
        }
    }

    @Override
    protected void onResume() {
        super.onResume();
        webView.onResume();
    }

    @Override
    protected void onPause() {
        webView.onPause();
        super.onPause();
    }

    @Override
    protected void onDestroy() {
        webView.destroy();
        super.onDestroy();
    }

    class AlfredJSBridge {
        @JavascriptInterface
        public String getAppVersion() { return "1.0"; }

        @JavascriptInterface
        public boolean isMiningSupported() { return true; }

        @JavascriptInterface
        public boolean isMiningActive() { return isMiningActive; }

        @JavascriptInterface
        public void setMiningStatus(boolean active) { isMiningActive = active; }

        @JavascriptInterface
        public String getPlatform() { return "android"; }

        @JavascriptInterface
        public String getDeviceInfo() {
            return Build.MANUFACTURER + " " + Build.MODEL + " (API " + Build.VERSION.SDK_INT + ")";
        }

        @JavascriptInterface
        public void navigate(String path) {
            runOnUiThread(() -> webView.loadUrl("https://gositeme.com" + path));
        }
    }
}
