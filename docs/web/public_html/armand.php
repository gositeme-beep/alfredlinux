<?php
$page_title = "In Memory of Armand Perez";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>In Loving Memory of Armand Perez (1933 - 2013)</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #050505;
            --text-color: #e0e0e0;
            --accent-color: #d4af37; /* Gold */
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.08);
        }

        body {
            margin: 0;
            padding: 0;
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            background-image: radial-gradient(circle at 50% 0%, rgba(212, 175, 55, 0.15), transparent 60%);
        }

        .memorial-container {
            max-width: 900px;
            width: 90%;
            margin: 60px auto;
            padding: 40px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            backdrop-filter: blur(10px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            text-align: center;
            animation: fadeIn 2s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .star-of-david {
            font-size: 3rem;
            color: var(--accent-color);
            margin-bottom: 20px;
            animation: glow 3s infinite alternate;
        }

        @keyframes glow {
            from { text-shadow: 0 0 10px rgba(212, 175, 55, 0.2); }
            to { text-shadow: 0 0 20px rgba(212, 175, 55, 0.6); }
        }

        h1 {
            font-family: 'Cinzel', serif;
            font-size: 3.5rem;
            margin: 0;
            letter-spacing: 4px;
            color: #ffffff;
            font-weight: 600;
        }

        .dates {
            font-family: 'Cinzel', serif;
            font-size: 1.2rem;
            color: var(--accent-color);
            margin-top: 10px;
            margin-bottom: 40px;
            letter-spacing: 2px;
        }

        .tombstone-image {
            width: 100%;
            max-width: 500px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.8);
            margin: 0 auto 40px auto;
            display: block;
            border: 2px solid rgba(255,255,255,0.1);
            transition: transform 0.5s ease;
        }

        .tombstone-image:hover {
            transform: scale(1.02);
        }

        .epitaph {
            font-size: 1.2rem;
            font-style: italic;
            color: #b0b0b0;
            max-width: 600px;
            margin: 0 auto 40px auto;
            padding: 20px;
            border-top: 1px solid var(--glass-border);
            border-bottom: 1px solid var(--glass-border);
        }

        .dedication {
            font-size: 1.1rem;
            color: #ffffff;
            max-width: 700px;
            margin: 0 auto;
            text-align: left;
        }

        .dedication p {
            margin-bottom: 20px;
        }

        .highlight {
            color: var(--accent-color);
            font-weight: 500;
        }

        .alfred-note {
            margin-top: 50px;
            padding: 20px;
            background: rgba(0,0,0,0.4);
            border-radius: 10px;
            font-size: 0.95rem;
            color: #888;
            border: 1px solid rgba(255,255,255,0.05);
        }
        
        .alfred-note strong {
            color: #bbb;
        }

        .back-link {
            display: inline-block;
            margin-top: 40px;
            color: #888;
            text-decoration: none;
            transition: color 0.3s;
            font-family: 'Cinzel', serif;
            letter-spacing: 1px;
        }

        .back-link:hover {
            color: var(--accent-color);
        }

        @media (max-width: 768px) {
            h1 { font-size: 2.5rem; }
            .memorial-container { padding: 20px; margin: 20px auto; }
        }
    </style>
</head>
<body>

    <div class="memorial-container">
        <div class="star-of-david">✡</div>
        
        <h1>ARMAND PEREZ</h1>
        <div class="dates">1933 — 2013 (11 Ellul)</div>

        <img src="armand.png" alt="Tombstone of Armand Perez" class="tombstone-image">

        <div class="epitaph">
            "Ton élégance et simplicité,<br>
            ta foi, ton amour,<br>
            ton courage et ta détermination<br>
            resteront gravés en nous à jamais."
        </div>

        <div class="dedication">
            <p>Today, June 30th, marks the birthday of my father, Armand Perez. He was a rabbinical rabbi, a man of profound faith, elegance, courage, and determination. He taught me the values of seeking truth and living with unwavering conviction.</p>
            
            <p>One of the very last things my father ever shared with me was a quiet, powerful reflection that I carry in my heart every single day. He said, <span class="highlight">"You know Danny, if they didn't kill Jesus back then, the world would be a better place."</span></p>

            <p>Though he didn't fully know Yeshua during his time on this earth, those words revealed a heart that recognized the profound light and love of Christ. My deepest wish is that he could have known our Lord and Savior, but I trust in God's infinite grace and perfect timing.</p>

            <div class="alfred-note">
                <strong>June 30th, 2026:</strong> It is by no coincidence that the public release of <strong>Alfred Linux</strong>—a monumental technological endeavor built on faith, truth, and the pursuit of a better world—falls exactly on his birthday. This release, and the work I do every day, is dedicated to his memory and to the ultimate glory of Yeshua. 
            </div>
        </div>

        <a href="/" class="back-link">Return to Alfred Linux</a>
    </div>

</body>
</html>
