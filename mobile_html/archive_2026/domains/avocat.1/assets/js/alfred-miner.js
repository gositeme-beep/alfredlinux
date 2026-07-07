/**
 * Alfred Mining Controller
 * ────────────────────────
 * Main-thread manager for browser mining.
 * Handles Web Worker lifecycle, API communication, UI updates.
 *
 * Usage:
 *   const miner = new AlfredMiner({ onStats, onReward, onError });
 *   miner.start();    // Start mining (user opt-in)
 *   miner.stop();     // Pause mining
 *   miner.toggle();   // Toggle on/off
 *   miner.setThrottle(0.3);  // 30% CPU
 */

class AlfredMiner {
    constructor(options = {}) {
        this.worker = null;
        this.mining = false;
        this.throttle = options.throttle || 0.3;
        this.difficulty = options.difficulty || 4;
        this.apiBase = options.apiBase || '/api/mining.php';

        // Callbacks
        this.onStats = options.onStats || (() => {});
        this.onReward = options.onReward || (() => {});
        this.onError = options.onError || (() => {});
        this.onStateChange = options.onStateChange || (() => {});

        // State
        this.stats = {
            hashrate: 0,
            totalHashes: 0,
            blocks: 0,
            earned: 0,
            miningTime: 0,
        };
        this.startTime = 0;
        this.statsUpdateTimer = null;

        // Visibility tracking
        this._handleVisibility = this._handleVisibility.bind(this);
        document.addEventListener('visibilitychange', this._handleVisibility);
    }

    start() {
        if (this.mining) return;

        try {
            this.worker = new Worker('/assets/js/mining-worker.js');
            this.worker.onmessage = (e) => this._handleWorkerMessage(e);
            this.worker.onerror = (e) => {
                this.onError('Mining worker error: ' + e.message);
                this.stop();
            };

            this.worker.postMessage({
                type: 'start',
                difficulty: this.difficulty,
                throttle: this.throttle,
            });

            this.mining = true;
            this.startTime = Date.now();
            this.onStateChange(true);

            // Also notify server
            this._apiCall('toggle_mining', 'POST');
        } catch (err) {
            this.onError('Failed to start mining: ' + err.message);
        }
    }

    stop() {
        if (this.worker) {
            this.worker.postMessage({ type: 'stop' });
            setTimeout(() => {
                if (this.worker) {
                    this.worker.terminate();
                    this.worker = null;
                }
            }, 1000);
        }
        this.mining = false;
        this.onStateChange(false);

        // Notify server
        if (this.stats.totalHashes > 0) {
            this._apiCall('toggle_mining', 'POST');
        }
    }

    toggle() {
        if (this.mining) {
            this.stop();
        } else {
            this.start();
        }
    }

    setThrottle(value) {
        this.throttle = Math.max(0.05, Math.min(1.0, value));
        if (this.worker) {
            this.worker.postMessage({ type: 'set_throttle', value: this.throttle });
        }
    }

    destroy() {
        this.stop();
        document.removeEventListener('visibilitychange', this._handleVisibility);
    }

    // Get wallet balance + stats
    async getWallet() {
        return this._apiCall('wallet', 'GET');
    }

    // Get transaction history
    async getHistory(page = 1) {
        return this._apiCall(`history&page=${page}`, 'GET');
    }

    // Get leaderboard
    async getLeaderboard() {
        return this._apiCall('leaderboard', 'GET');
    }

    // Get pool stats
    async getPoolStats() {
        return this._apiCall('pool_stats', 'GET');
    }

    // Set Solana wallet address
    async setWalletAddress(address) {
        return this._apiCall('set_wallet', 'POST', { wallet_address: address });
    }

    // Award search reward (called on each search)
    async rewardSearch() {
        return this._apiCall('search_reward', 'POST');
    }

    // ── Private Methods ─────────────────────────────────────────

    _handleWorkerMessage(e) {
        const msg = e.data;

        switch (msg.type) {
            case 'stats':
                this.stats = {
                    hashrate: msg.hashrate,
                    totalHashes: msg.total,
                    blocks: msg.blocks,
                    earned: msg.earned,
                    miningTime: Math.floor((Date.now() - this.startTime) / 1000),
                };
                this.onStats(this.stats);
                break;

            case 'submit_work':
                this._submitWork(msg);
                break;

            case 'block_found':
                // Visual feedback for block discovery
                this.onReward({
                    type: 'block',
                    hash: msg.hash,
                    blockNumber: msg.blockNumber,
                });
                break;

            case 'stopped':
                this.stats.totalHashes = msg.total;
                this.stats.earned = msg.earned;
                break;

            case 'error':
                this.onError(msg.message);
                break;
        }
    }

    async _submitWork(work) {
        try {
            const result = await this._apiCall('submit_work', 'POST', {
                hashes: work.hashes,
                nonce: work.nonce,
                difficulty: work.difficulty,
                job_id: work.job_id,
            });

            if (result && result.ok && result.reward > 0) {
                // Tell worker about confirmed reward
                if (this.worker) {
                    this.worker.postMessage({
                        type: 'reward_confirmed',
                        amount: result.reward,
                    });
                }
                this.onReward({
                    type: 'reward',
                    amount: result.reward,
                    total: result.total_earned,
                    remaining: result.pool_remaining,
                });
            }
        } catch (err) {
            this.onError('Failed to submit work: ' + err.message);
        }
    }

    async _apiCall(action, method = 'GET', body = null) {
        const url = `${this.apiBase}?action=${action}`;
        const opts = {
            method,
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
        };
        if (body && method === 'POST') {
            opts.body = JSON.stringify(body);
        }
        try {
            const resp = await fetch(url, opts);
            return await resp.json();
        } catch (err) {
            this.onError('API error: ' + err.message);
            return null;
        }
    }

    _handleVisibility() {
        if (document.hidden && this.mining) {
            // Reduce throttle when tab is hidden
            if (this.worker) {
                this.worker.postMessage({ type: 'set_throttle', value: this.throttle * 0.5 });
            }
        } else if (!document.hidden && this.mining) {
            // Restore throttle when tab is visible
            if (this.worker) {
                this.worker.postMessage({ type: 'set_throttle', value: this.throttle });
            }
        }
    }
}

// Export for module and global use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AlfredMiner;
}
if (typeof window !== 'undefined') {
    window.AlfredMiner = AlfredMiner;
}
