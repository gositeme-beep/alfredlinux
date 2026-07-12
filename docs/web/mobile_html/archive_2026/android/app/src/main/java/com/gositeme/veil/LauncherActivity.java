package com.gositeme.veil;

import android.content.Intent;
import android.net.Uri;
import android.os.Bundle;

import androidx.appcompat.app.AppCompatActivity;
import androidx.core.splashscreen.SplashScreen;

/**
 * LauncherActivity — Splash screen that launches the Veil Browser.
 *
 * Shows the splash screen, then opens VeilBrowserActivity.
 * Passes any deep link intents through to the browser.
 */
public class LauncherActivity extends AppCompatActivity {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        SplashScreen.installSplashScreen(this);
        super.onCreate(savedInstanceState);

        Intent browserIntent = new Intent(this, VeilBrowserActivity.class);

        // Forward deep link data
        Intent incoming = getIntent();
        if (incoming != null && incoming.getData() != null) {
            browserIntent.setData(incoming.getData());
        }

        startActivity(browserIntent);
        finish();
    }
}
