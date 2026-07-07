/**
 * GoSiteMe Veil v2 — Group Encryption (Sender Key Protocol)
 *
 * Signal-style Sender Key distribution for efficient group messaging.
 * Each member generates a Sender Key; distributes it encrypted to each other member.
 * One encrypt → all members decrypt. Much more efficient than N pairwise encryptions.
 *
 * Flow:
 * 1. Creator generates group Sender Key
 * 2. Sender Key is encrypted individually for each member (using their identity ECDH key)
 * 3. Each member stores the Sender Key for that group
 * 4. Messages: sender encrypts once with their Sender Key → all members decrypt
 * 5. When a member is removed, all remaining members rotate Sender Keys
 */
const CommsGroup = (() => {
    'use strict';

    const { ab2b64, b642ab, ab2hex, encryptMessage, decryptMessage } = CommsCrypto;

    // ═════════════════════════════════════════════════════════════════
    // SENDER KEY GENERATION
    // ═════════════════════════════════════════════════════════════════

    /**
     * Generate a new Sender Key for use in a group
     * Returns { senderKey, senderKeyId, exportedKey }
     */
    async function generateSenderKey() {
        const key = await crypto.subtle.generateKey(
            { name: 'AES-GCM', length: 256 },
            true,
            ['encrypt', 'decrypt']
        );

        const keyId = ab2hex(crypto.getRandomValues(new Uint8Array(16)));
        const exported = ab2b64(await crypto.subtle.exportKey('raw', key));

        return { senderKey: key, senderKeyId: keyId, exportedKey: exported };
    }

    /**
     * Import a Sender Key from raw base64
     */
    async function importSenderKey(keyB64) {
        const raw = b642ab(keyB64);
        return crypto.subtle.importKey(
            'raw', raw,
            { name: 'AES-GCM', length: 256 },
            false,
            ['encrypt', 'decrypt']
        );
    }

    /**
     * Store a Sender Key for a group in IndexedDB
     */
    async function storeSenderKey(groupId, senderId, senderKeyB64, senderKeyId) {
        const db = await openCommsDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('keys', 'readwrite');
            tx.objectStore('keys').put({
                id: `group_sk_${groupId}_${senderId}`,
                value: { key: senderKeyB64, keyId: senderKeyId, stored: Date.now() }
            });
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
        });
    }

    /**
     * Get stored Sender Key for a group member
     */
    async function getSenderKey(groupId, senderId) {
        const db = await openCommsDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('keys', 'readonly');
            const req = tx.objectStore('keys').get(`group_sk_${groupId}_${senderId}`);
            req.onsuccess = () => resolve(req.result?.value ?? null);
            req.onerror = () => reject(req.error);
        });
    }

    // ═════════════════════════════════════════════════════════════════
    // SENDER KEY DISTRIBUTION
    // ═════════════════════════════════════════════════════════════════

    /**
     * Distribute our Sender Key to all group members
     * Encrypts the Sender Key with each member's identity public key
     */
    async function distributeSenderKey(groupId, senderKeyB64, senderKeyId, members, apiFunction) {
        const myPriv = await getMyEcdhPrivate();
        if (!myPriv) throw new Error('No identity keys');

        for (const member of members) {
            // Get member's public key
            const memberKeys = await apiFunction('get_keys', { params: { id: member.client_id } });
            if (!memberKeys.success) continue;

            // Derive shared key with this member
            const sharedKey = await CommsCrypto.deriveSharedKey(myPriv, memberKeys.ecdh_public);

            // Encrypt our Sender Key with the shared key
            const encrypted = await encryptMessage(
                JSON.stringify({ senderKey: senderKeyB64, senderKeyId, groupId }),
                sharedKey
            );

            // Send as a system message (type 3)
            await apiFunction('group_distribute_key', {
                method: 'POST',
                body: {
                    group_id: groupId,
                    to_id: member.client_id,
                    ciphertext: encrypted.ciphertext,
                    iv: encrypted.iv,
                    sender_key_id: senderKeyId,
                }
            });
        }
    }

    /**
     * Accept and store a distributed Sender Key
     */
    async function acceptSenderKey(groupId, senderId, ciphertext, iv) {
        const myPriv = await getMyEcdhPrivate();
        if (!myPriv) throw new Error('No identity keys');

        // Get sender's public key
        const senderPub = await getStoredPublicKey(senderId);
        if (!senderPub) throw new Error('Unknown sender');

        // Derive shared key
        const sharedKey = await CommsCrypto.deriveSharedKey(myPriv, senderPub);

        // Decrypt the Sender Key
        const decrypted = await decryptMessage(ciphertext, iv, sharedKey);
        const data = JSON.parse(decrypted);

        // Store it
        await storeSenderKey(groupId, senderId, data.senderKey, data.senderKeyId);
        return data;
    }

    // ═════════════════════════════════════════════════════════════════
    // GROUP MESSAGE ENCRYPTION
    // ═════════════════════════════════════════════════════════════════

    /**
     * Encrypt a message for a group using our Sender Key
     */
    async function encryptGroupMessage(groupId, plaintext, myClientId) {
        const stored = await getSenderKey(groupId, myClientId);
        if (!stored) throw new Error('No Sender Key for this group — need to distribute first');

        const senderKey = await importSenderKey(stored.key);
        const encrypted = await encryptMessage(plaintext, senderKey);

        return {
            ciphertext: encrypted.ciphertext,
            iv: encrypted.iv,
            senderKeyId: stored.keyId,
        };
    }

    /**
     * Decrypt a group message from a specific sender
     */
    async function decryptGroupMessage(groupId, senderId, ciphertext, iv) {
        const stored = await getSenderKey(groupId, senderId);
        if (!stored) throw new Error('Missing Sender Key from sender ' + senderId);

        const senderKey = await importSenderKey(stored.key);
        return decryptMessage(ciphertext, iv, senderKey);
    }

    // ═════════════════════════════════════════════════════════════════
    // GROUP LIFECYCLE
    // ═════════════════════════════════════════════════════════════════

    /**
     * Initialize encryption for a new group
     */
    async function initGroupEncryption(groupId, members, apiFunction, myClientId) {
        // Generate our Sender Key
        const { senderKey, senderKeyId, exportedKey } = await generateSenderKey();

        // Store our own key
        await storeSenderKey(groupId, myClientId, exportedKey, senderKeyId);

        // Distribute to all members
        await distributeSenderKey(groupId, exportedKey, senderKeyId, members, apiFunction);

        return { senderKeyId };
    }

    /**
     * Rotate Sender Key (when a member is removed)
     */
    async function rotateSenderKey(groupId, remainingMembers, apiFunction, myClientId) {
        return initGroupEncryption(groupId, remainingMembers, apiFunction, myClientId);
    }

    // ═════════════════════════════════════════════════════════════════
    // HELPERS
    // ═════════════════════════════════════════════════════════════════

    function openCommsDB() {
        return new Promise((resolve, reject) => {
            const req = indexedDB.open('GoSiteMe_Comms_Keystore', 1);
            req.onupgradeneeded = (e) => {
                const db = e.target.result;
                if (!db.objectStoreNames.contains('keys')) {
                    db.createObjectStore('keys', { keyPath: 'id' });
                }
            };
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });
    }

    async function getMyEcdhPrivate() {
        const db = await openCommsDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('keys', 'readonly');
            const req = tx.objectStore('keys').get('identity_ecdh_private');
            req.onsuccess = () => resolve(req.result?.value ?? null);
            req.onerror = () => reject(req.error);
        });
    }

    async function getStoredPublicKey(clientId) {
        const db = await openCommsDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('keys', 'readonly');
            const req = tx.objectStore('keys').get('contact_pub_' + clientId);
            req.onsuccess = () => resolve(req.result?.value ?? null);
            req.onerror = () => reject(req.error);
        });
    }

    return {
        generateSenderKey,
        importSenderKey,
        storeSenderKey,
        getSenderKey,
        distributeSenderKey,
        acceptSenderKey,
        encryptGroupMessage,
        decryptGroupMessage,
        initGroupEncryption,
        rotateSenderKey,
    };
})();
