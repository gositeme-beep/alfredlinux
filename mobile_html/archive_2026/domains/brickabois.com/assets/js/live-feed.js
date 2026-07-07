/**
 * Live Activity Feed - Real-time Updates
 */

class LiveActivityFeed {
    constructor() {
        this.updateInterval = 30000; // 30 seconds
        this.init();
    }

    init() {
        this.updateFeed();
        setInterval(() => this.updateFeed(), this.updateInterval);
    }

    async updateFeed() {
        try {
            // Fetch latest posts
            const response = await fetch('/api/commons/posts?limit=3');
            const data = await response.json();
            
            if (data.posts && data.posts.length > 0) {
                this.animateNewPosts(data.posts);
            }
        } catch (error) {
            console.log('Live feed update failed:', error);
        }
    }

    animateNewPosts(posts) {
        const feedContainer = document.querySelector('.posts-list');
        if (!feedContainer) return;

        // Add pulse animation to indicate new content
        feedContainer.style.animation = 'pulse 0.5s ease';
        setTimeout(() => {
            feedContainer.style.animation = '';
        }, 500);
    }
}

// Initialize live feed
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.posts-list')) {
        new LiveActivityFeed();
    }
});

