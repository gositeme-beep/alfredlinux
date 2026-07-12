/**
 * imageGenerator.js — AI Image Generation for GoCodeMe MCP Server
 *
 * Generates images from text prompts using configurable AI providers:
 *   1. Together AI (FLUX.1-schnell — fast, high quality, free tier)
 *   2. OpenAI (DALL-E 3 — premium quality)
 *
 * Images are saved to the user's domain directory and returned as public URLs.
 *
 * Environment variables:
 *   TOGETHER_API_KEY  — Together AI API key (free $5 credit at together.ai)
 *   OPENAI_API_KEY    — OpenAI API key (for DALL-E 3)
 *   IMAGE_PROVIDER    — Force a specific provider: 'together' | 'openai'
 */

import { execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, writeFileSync, readFileSync } from 'node:fs';
import { join, basename } from 'node:path';
import axios from 'axios';

export class ImageGenerator {
  /**
   * @param {string} homeDir — User home directory path
   * @param {object} [daClient] — DirectAdmin client for file operations (required for cross-user access)
   */
  constructor(homeDir, daClient = null) {
    this.homeDir = homeDir;
    this.daClient = daClient;
    this.provider = this._detectProvider();
  }

  /**
   * Detect which image generation provider is available.
   * Priority: env IMAGE_PROVIDER override → Together AI → OpenAI
   */
  _detectProvider() {
    const forced = process.env.IMAGE_PROVIDER?.toLowerCase();
    if (forced === 'together' && process.env.TOGETHER_API_KEY) return 'together';
    if (forced === 'openai' && process.env.OPENAI_API_KEY) return 'openai';
    if (forced) {
      // Forced but key missing — fall through to auto-detect
    }
    if (process.env.TOGETHER_API_KEY) return 'together';
    if (process.env.OPENAI_API_KEY) return 'openai';
    return null;
  }

  /**
   * List available image generation providers and their status.
   */
  getProviderStatus() {
    return {
      currentProvider: this.provider,
      providers: {
        together: {
          available: !!process.env.TOGETHER_API_KEY,
          model: 'black-forest-labs/FLUX.1-schnell',
          description: 'Together AI — FLUX.1 Schnell (fast, high quality, free tier available)',
          signupUrl: 'https://api.together.xyz',
        },
        openai: {
          available: !!process.env.OPENAI_API_KEY,
          model: 'dall-e-3',
          description: 'OpenAI — DALL-E 3 (premium quality, photorealistic)',
          signupUrl: 'https://platform.openai.com',
        },
      },
    };
  }

  /**
   * Generate an image from a text prompt.
   *
   * @param {string} prompt     — Description of the image to generate
   * @param {string} domain     — Domain name to save the image under
   * @param {string} [style]    — Style hint: 'photo', 'illustration', 'logo', 'abstract'
   * @param {string} [size]     — Image size: '512x512', '1024x1024', '1792x1024'
   * @param {string} [filename] — Custom filename (auto-generated if omitted)
   * @returns {{ success, provider, url, path, prompt, size, generationTime }}
   */
  async generateImage(prompt, domain, style = 'photo', size = '1024x1024', filename = '') {
    if (!this.provider) {
      return {
        success: false,
        error: 'No image generation provider configured.',
        help: 'Add TOGETHER_API_KEY or OPENAI_API_KEY to your .env file.',
        providers: this.getProviderStatus().providers,
      };
    }

    const startTime = Date.now();

    // Enhance the prompt with style hints
    const enhancedPrompt = this._enhancePrompt(prompt, style);

    // Parse size
    const [width, height] = size.split('x').map(Number);

    // Generate the image data
    let imageBuffer;
    let actualProvider = this.provider;

    try {
      if (this.provider === 'together') {
        imageBuffer = await this._generateTogether(enhancedPrompt, width, height);
      } else if (this.provider === 'openai') {
        imageBuffer = await this._generateOpenAI(enhancedPrompt, size);
      }
    } catch (err) {
      return {
        success: false,
        error: `Image generation failed: ${err.message}`,
        provider: actualProvider,
        prompt: enhancedPrompt,
      };
    }

    // Save the image to the domain's public directory
    const saveResult = await this._saveImage(imageBuffer, domain, filename || this._generateFilename(prompt));
    const generationTime = ((Date.now() - startTime) / 1000).toFixed(1);
    const actualDomain = saveResult.resolvedDomain || domain;

    return {
      success: true,
      provider: actualProvider,
      model: this.provider === 'together' ? 'FLUX.1-schnell' : 'DALL-E 3',
      url: saveResult.url,
      domain: actualDomain,
      domainCorrected: actualDomain !== domain ? `"${domain}" was not found — saved to "${actualDomain}" instead` : undefined,
      path: saveResult.path,
      relativePath: saveResult.relativePath,
      prompt: enhancedPrompt,
      originalPrompt: prompt,
      _imageBuffer: imageBuffer,  // raw PNG buffer for inline display
      size: `${width}x${height}`,
      generationTime: `${generationTime}s`,
      tip: `Use this image in your HTML: <img src="${saveResult.url}" alt="${prompt}">`,
    };
  }

  /**
   * Enhance prompts with style-specific instructions for better results.
   */
  _enhancePrompt(prompt, style) {
    const enhancements = {
      photo: `Professional photography, highly detailed, natural lighting: ${prompt}`,
      illustration: `Digital illustration, clean vector style, vibrant colors: ${prompt}`,
      logo: `Clean minimalist logo design on transparent background, professional branding: ${prompt}`,
      abstract: `Abstract artistic interpretation, creative, bold shapes and colors: ${prompt}`,
      hero: `Wide cinematic hero banner image, professional website header: ${prompt}`,
      product: `Professional product photography, white background, studio lighting: ${prompt}`,
      avatar: `Professional portrait, friendly, modern style: ${prompt}`,
    };
    return enhancements[style] || prompt;
  }

  /**
   * Generate image via Together AI (FLUX.1-schnell model).
   * Free tier available, fast generation (~2-5s).
   */
  async _generateTogether(prompt, width = 1024, height = 1024) {
    const response = await axios.post(
      'https://api.together.xyz/v1/images/generations',
      {
        model: 'black-forest-labs/FLUX.1-schnell',
        prompt,
        width: Math.min(width, 1440),
        height: Math.min(height, 1440),
        steps: 4,
        n: 1,
        response_format: 'b64_json',
      },
      {
        headers: {
          Authorization: `Bearer ${process.env.TOGETHER_API_KEY}`,
          'Content-Type': 'application/json',
        },
        timeout: 60000,
      }
    );

    const b64Data = response.data?.data?.[0]?.b64_json;
    if (!b64Data) throw new Error('No image data in Together AI response');
    return Buffer.from(b64Data, 'base64');
  }

  /**
   * Generate image via OpenAI (DALL-E 3 model).
   */
  async _generateOpenAI(prompt, size = '1024x1024') {
    // DALL-E 3 only supports specific sizes
    const validSizes = ['1024x1024', '1792x1024', '1024x1792'];
    const actualSize = validSizes.includes(size) ? size : '1024x1024';

    const response = await axios.post(
      'https://api.openai.com/v1/images/generations',
      {
        model: 'dall-e-3',
        prompt,
        n: 1,
        size: actualSize,
        quality: 'standard',
        response_format: 'b64_json',
      },
      {
        headers: {
          Authorization: `Bearer ${process.env.OPENAI_API_KEY}`,
          'Content-Type': 'application/json',
        },
        timeout: 120000,
      }
    );

    const b64Data = response.data?.data?.[0]?.b64_json;
    if (!b64Data) throw new Error('No image data in OpenAI response');
    return Buffer.from(b64Data, 'base64');
  }

  /**
   * Save the generated image to the user's domain directory.
   * Uses DirectAdmin API when daClient is available (cross-user access),
   * falls back to direct filesystem for local access.
   */
  async _saveImage(buffer, domain, filename) {
    const safeName = filename.replace(/[^a-zA-Z0-9_-]/g, '_').substring(0, 80);
    const finalName = `${safeName}.png`;
    const relativePath = `ai-images/${finalName}`;
    const url = `https://${domain}/${relativePath}`;

    // Determine the target directory path
    const domainDir = join(this.homeDir, 'domains', domain, 'public_html');
    const imagesDir = join(domainDir, 'ai-images');
    const altDir = join(this.homeDir, 'public_html');
    const altImagesDir = join(altDir, 'ai-images');

    // If we have a DA client, use the API (works across users)
    if (this.daClient) {
      return this._saveViaDA(buffer, domain, finalName, relativePath, url);
    }

    // Direct filesystem fallback
    if (existsSync(domainDir)) {
      return this._saveToDir(buffer, imagesDir, finalName, domain);
    }
    if (existsSync(altDir)) {
      return this._saveToDir(buffer, altImagesDir, finalName, domain);
    }
    throw new Error(`Domain directory not found for ${domain}. The MCP server cannot access /home/${basename(this.homeDir)}/domains/${domain}/public_html/`);
  }

  /**
   * Save image via DirectAdmin File Manager API.
   * This works even when the MCP server user differs from the target DA user.
   */
  async _saveViaDA(buffer, domain, finalName, relativePath, url) {
    // Resolve the actual domain — fuzzy-match if the exact name doesn't exist
    let resolvedDomain = domain;
    let domainImagesPath = `domains/${domain}/public_html/ai-images`;

    try {
      // Check if the domain directory exists via DA
      await this.daClient.listDirectory(`domains/${domain}/public_html`);
    } catch {
      // Exact domain not found — try fuzzy-matching against available domains
      const matched = await this._fuzzyMatchDomain(domain);
      if (matched) {
        resolvedDomain = matched;
        domainImagesPath = `domains/${matched}/public_html/ai-images`;
      } else {
        throw new Error(
          `Domain "${domain}" not found. Check the spelling and try again. ` +
          `Use list_domains to see available domains.`
        );
      }
    }

    // Update URL if we resolved to a different domain
    if (resolvedDomain !== domain) {
      url = `https://${resolvedDomain}/${relativePath}`;
    }

    // Ensure ai-images directory exists
    try {
      await this.daClient.listDirectory(domainImagesPath);
    } catch {
      await this.daClient.createDirectory(domainImagesPath);
    }

    // Upload the image via DA File Manager API
    const filePath = `${domainImagesPath}/${finalName}`;
    await this.daClient.writeFile(filePath, buffer);

    const absPath = join(this.homeDir, filePath);
    return { path: absPath, relativePath, url, resolvedDomain };
  }

  /**
   * Fuzzy-match a domain name against available domains in DA.
   * Handles typos like "aideavire" matching "aideavivre".
   * Returns the best match or null if no close match found.
   */
  async _fuzzyMatchDomain(input) {
    try {
      const listing = await this.daClient.listDirectory('domains');
      // listing returns objects with .path like "/domains/example.com"
      const domains = listing
        .filter(e => e.type === 'dir')
        .map(e => basename(e.path));

      if (domains.length === 0) return null;

      // Exact match (case-insensitive)
      const exact = domains.find(d => d.toLowerCase() === input.toLowerCase());
      if (exact) return exact;

      // Levenshtein distance — find closest match
      let bestDomain = null;
      let bestDist = Infinity;
      for (const d of domains) {
        const dist = this._levenshtein(input.toLowerCase(), d.toLowerCase());
        if (dist < bestDist) {
          bestDist = dist;
          bestDomain = d;
        }
      }

      // Accept if edit distance is ≤ 3 (handles minor typos)
      if (bestDist <= 3 && bestDomain) {
        return bestDomain;
      }
      return null;
    } catch {
      return null;
    }
  }

  /** Simple Levenshtein distance for fuzzy domain matching. */
  _levenshtein(a, b) {
    const m = a.length, n = b.length;
    const dp = Array.from({ length: m + 1 }, () => new Array(n + 1).fill(0));
    for (let i = 0; i <= m; i++) dp[i][0] = i;
    for (let j = 0; j <= n; j++) dp[0][j] = j;
    for (let i = 1; i <= m; i++) {
      for (let j = 1; j <= n; j++) {
        dp[i][j] = a[i - 1] === b[j - 1]
          ? dp[i - 1][j - 1]
          : 1 + Math.min(dp[i - 1][j], dp[i][j - 1], dp[i - 1][j - 1]);
      }
    }
    return dp[m][n];
  }

  _saveToDir(buffer, dir, filename, domain) {
    if (!existsSync(dir)) {
      mkdirSync(dir, { recursive: true });
    }

    const filePath = join(dir, filename);
    writeFileSync(filePath, buffer);

    // Build public URL
    const relativePath = `ai-images/${filename}`;
    const url = `https://${domain}/${relativePath}`;

    return { path: filePath, relativePath, url };
  }

  /**
   * Generate a descriptive filename from the prompt.
   */
  _generateFilename(prompt) {
    const timestamp = Date.now();
    const slug = prompt
      .toLowerCase()
      .replace(/[^a-z0-9\s]/g, '')
      .split(/\s+/)
      .slice(0, 5)
      .join('-');
    return `${slug}-${timestamp}`;
  }

  /**
   * List all AI-generated images for a domain.
   */
  async listImages(domain) {
    // If we have a DA client, use the API
    if (this.daClient) {
      return this._listImagesViaDA(domain);
    }

    // Direct filesystem fallback
    const imagesDir = join(this.homeDir, 'domains', domain, 'public_html', 'ai-images');
    if (!existsSync(imagesDir)) {
      return { success: true, images: [], count: 0 };
    }

    try {
      const output = execFileSync('ls', ['-ltph', '--time-style=long-iso', imagesDir], {
        encoding: 'utf8',
        timeout: 5000,
      });

      const lines = output.trim().split('\n').slice(1); // skip "total" line
      const images = lines
        .filter((l) => l.endsWith('.png') || l.endsWith('.jpg') || l.endsWith('.webp'))
        .map((line) => {
          const parts = line.split(/\s+/);
          const name = parts[parts.length - 1];
          return {
            name,
            url: `https://${domain}/ai-images/${name}`,
            size: parts[4],
            date: `${parts[5]} ${parts[6]}`,
          };
        });

      return { success: true, images, count: images.length };
    } catch {
      return { success: true, images: [], count: 0 };
    }
  }

  async _listImagesViaDA(domain) {
    try {
      const entries = await this.daClient.listDirectory(`domains/${domain}/public_html/ai-images`);
      const images = entries
        .filter((e) => e.type === 'file' && (/\.(png|jpg|webp)$/i).test(e.path))
        .map((e) => {
          const name = basename(e.path);
          return {
            name,
            url: `https://${domain}/ai-images/${name}`,
            size: e.showsize || e.size || '?',
            date: e.date || '',
          };
        });
      return { success: true, images, count: images.length };
    } catch {
      return { success: true, images: [], count: 0 };
    }
  }
}
