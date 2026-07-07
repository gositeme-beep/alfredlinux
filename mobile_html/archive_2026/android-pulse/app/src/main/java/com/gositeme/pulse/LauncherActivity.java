package com.gositeme.pulse;

import android.content.Intent;
import android.os.Bundle;

import androidx.appcompat.app.AppCompatActivity;
import androidx.core.splashscreen.SplashScreen;

/**
 * LauncherActivity — Splash screen that launches Pulse Social.
 */
public class LauncherActivity extends AppCompatActivity {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        SplashScreen.installSplashScreen(this);
        super.onCreate(savedInstanceState);

        Intent socialIntent = new Intent(this, PulseSocialActivity.class);

        Intent incoming = getIntent();
        if (incoming != null && incoming.getData() != null) {
            socialIntent.setData(incoming.getData());
        }

        startActivity(socialIntent);
        finish();
    }
}
