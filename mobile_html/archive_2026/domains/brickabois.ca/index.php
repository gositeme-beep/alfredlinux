<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Érablière | brickabois.ca</title>
    <meta name="description" content="33 photos de notre érablière">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #000;
            overflow: hidden;
            height: 100vh;
            width: 100vw;
        }

        .gallery-container {
            position: relative;
            width: 100%;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .photo {
            position: absolute;
            width: 100%;
            height: 100%;
            object-fit: contain;
            opacity: 0;
            transition: opacity 3s ease-in-out;
            pointer-events: none;
        }

        .photo.active {
            opacity: 1;
            z-index: 1;
        }

        .photo.loading {
            opacity: 0;
        }

    </style>
</head>
<body>
    <div class="gallery-container" id="gallery"></div>

    <script>
        const totalPhotos = 33;
        const gallery = document.getElementById('gallery');
        let currentIndex = 0;
        let currentPhoto = null;
        let loadedImages = new Set();
        let preloadedImages = new Map();
        let fadeTimer = null;

        // Preload image function
        function preloadImage(index) {
            if (preloadedImages.has(index)) {
                return Promise.resolve(preloadedImages.get(index));
            }

            return new Promise((resolve, reject) => {
                const img = new Image();
                img.onload = () => {
                    preloadedImages.set(index, img);
                    loadedImages.add(index);
                    resolve(img);
                };
                img.onerror = reject;
                img.src = `images/photo${index}.png`;
            });
        }

        // Preload next few images
        function preloadNextImages(currentIdx) {
            const indicesToPreload = [];
            for (let i = 1; i <= 3; i++) {
                let nextIdx = (currentIdx + i) % totalPhotos;
                if (nextIdx === 0) nextIdx = totalPhotos;
                if (!loadedImages.has(nextIdx)) {
                    indicesToPreload.push(nextIdx);
                }
            }
            
            // Also preload some random ones
            for (let i = 0; i < 2; i++) {
                const randomIdx = Math.floor(Math.random() * totalPhotos) + 1;
                if (!loadedImages.has(randomIdx) && !indicesToPreload.includes(randomIdx)) {
                    indicesToPreload.push(randomIdx);
                }
            }

            indicesToPreload.forEach(idx => {
                preloadImage(idx).catch(() => {});
            });
        }

        // Show photo
        function showPhoto(index) {
            if (index < 1 || index > totalPhotos) return;

            preloadImage(index).then((img) => {
                // Remove old photo
                if (currentPhoto) {
                    currentPhoto.classList.remove('active');
                    setTimeout(() => {
                        if (currentPhoto && !currentPhoto.classList.contains('active')) {
                            currentPhoto.remove();
                        }
                    }, 3000);
                }

                // Create new photo element
                const photoEl = document.createElement('img');
                photoEl.className = 'photo loading';
                photoEl.src = img.src;
                photoEl.alt = `Photo ${index}`;
                gallery.appendChild(photoEl);

                // Fade in
                setTimeout(() => {
                    photoEl.classList.remove('loading');
                    photoEl.classList.add('active');
                }, 100);

                currentPhoto = photoEl;
                currentIndex = index;

                // Preload next images in background
                preloadNextImages(index);
            }).catch(() => {
                console.error(`Failed to load image ${index}`);
            });
        }

        function scheduleNextFade() {
            const delay = Math.random() * 5000 + 3000;
            
            fadeTimer = setTimeout(() => {
                // Randomly select next photo (never the same as current)
                let nextIndex;
                do {
                    nextIndex = Math.floor(Math.random() * totalPhotos) + 1;
                } while (nextIndex === currentIndex);
                
                showPhoto(nextIndex);
                scheduleNextFade();
            }, delay);
        }

        // Initialize - load random first image
        const randomStartIndex = Math.floor(Math.random() * totalPhotos) + 1;
        showPhoto(randomStartIndex);
        setTimeout(() => {
            scheduleNextFade();
        }, 1000);
    </script>
</body>
</html>
