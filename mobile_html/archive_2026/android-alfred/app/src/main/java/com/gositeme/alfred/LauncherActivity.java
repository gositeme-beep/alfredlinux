package com.gositeme.alfred;

import android.content.Intent;
import android.os.Bundle;

import androidx.appcompat.app.AppCompatActivity;
import androidx.core.splashscreen.SplashScreen;

/**
 * LauncherActivity — Splash screen that launches the Alfred Browser.
 */
public class LauncherActivity extends AppCompatActivity {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        SplashScreen.installSplashScreen(this);
        super.onCreate(savedInstanceState);

        Intent browserIntent = new Intent(this, AlfredBrowserActivity.class);

        Intent incoming = getIntent();
        if (incoming != null && incoming.getData() != null) {
            browserIntent.setData(incoming.getData());
        }

        startActivity(browserIntent);
        finish();
    }
}
