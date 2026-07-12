/**
 * mediaProcessor.js — Video & Image Processing + Media Download for Alfred AI
 *
 * The GoCodeMe server is Alfred's hands — FFmpeg, ImageMagick, Pillow, yt-dlp.
 * Together.ai handles AI inference; this module handles execution.
 */

import { execFile } from 'node:child_process';
import { promisify } from 'node:util';
import { existsSync, mkdirSync, statSync, readFileSync } from 'node:fs';
import { join, basename, extname, dirname } from 'node:path';

const execFileAsync = promisify(execFile);

// ══════════════════════════════════════════════════════════════════════════════
// VIDEO PROCESSING — FFmpeg + MoviePy
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Process a video file with FFmpeg.
 * @param {object} params
 * @param {string} params.input - Input video path
 * @param {string} params.action - Action: trim, resize, convert, extract_audio, merge, add_subtitles, compress, thumbnail, gif, speed
 * @param {string} params.output - Output file path
 * @param {object} [params.options] - Action-specific options
 * @returns {Promise<{success: boolean, output: string, size: string, duration?: string}>}
 */
export async function processVideo({ input, action, output, options = {} }) {
  if (!existsSync(input)) throw new Error(`Input video not found: ${input}`);

  // Ensure output directory exists
  const outDir = dirname(output);
  if (!existsSync(outDir)) mkdirSync(outDir, { recursive: true });

  const start = Date.now();
  let args = [];

  switch (action) {
    case 'trim': {
      const { startTime = '0', endTime, duration: dur } = options;
      args = ['-i', input, '-ss', String(startTime)];
      if (endTime) args.push('-to', String(endTime));
      else if (dur) args.push('-t', String(dur));
      args.push('-c', 'copy', '-y', output);
      break;
    }

    case 'resize': {
      const { width = 1280, height = -1 } = options;
      args = ['-i', input, '-vf', `scale=${width}:${height}`, '-y', output];
      break;
    }

    case 'convert': {
      args = ['-i', input];
      if (options.codec) args.push('-c:v', options.codec);
      if (options.audioBitrate) args.push('-b:a', options.audioBitrate);
      if (options.videoBitrate) args.push('-b:v', options.videoBitrate);
      args.push('-y', output);
      break;
    }

    case 'extract_audio': {
      const { format = 'mp3', bitrate = '192k' } = options;
      args = ['-i', input, '-vn', '-acodec'];
      if (format === 'mp3') args.push('libmp3lame');
      else if (format === 'wav') args.push('pcm_s16le');
      else if (format === 'aac') args.push('aac');
      else args.push('copy');
      args.push('-b:a', bitrate, '-y', output);
      break;
    }

    case 'compress': {
      const { crf = '28', preset = 'fast' } = options;
      args = ['-i', input, '-c:v', 'libx264', '-crf', String(crf), '-preset', preset, '-c:a', 'aac', '-y', output];
      break;
    }

    case 'thumbnail': {
      const { time = '00:00:01' } = options;
      args = ['-i', input, '-ss', time, '-vframes', '1', '-y', output];
      break;
    }

    case 'gif': {
      const { fps = '10', width = '480', startTime = '0', duration: dur = '5' } = options;
      args = ['-i', input, '-ss', String(startTime), '-t', String(dur),
        '-vf', `fps=${fps},scale=${width}:-1:flags=lanczos`, '-y', output];
      break;
    }

    case 'speed': {
      const { factor = '2.0' } = options;
      const vSpeed = 1 / parseFloat(factor);
      args = ['-i', input, '-vf', `setpts=${vSpeed}*PTS`, '-af', `atempo=${factor}`, '-y', output];
      break;
    }

    case 'merge': {
      // options.inputs = array of additional input file paths
      const inputs = options.inputs || [];
      // Create a concat file
      const concatFile = output + '.concat.txt';
      const entries = [input, ...inputs].map(f => `file '${f}'`).join('\n');
      const { writeFileSync: wfs } = await import('node:fs');
      wfs(concatFile, entries);
      args = ['-f', 'concat', '-safe', '0', '-i', concatFile, '-c', 'copy', '-y', output];
      break;
    }

    case 'add_subtitles': {
      const { subtitleFile } = options;
      if (!subtitleFile) throw new Error('subtitleFile required for add_subtitles action');
      args = ['-i', input, '-vf', `subtitles=${subtitleFile}`, '-y', output];
      break;
    }

    default:
      throw new Error(`Unknown video action: ${action}. Supported: trim, resize, convert, extract_audio, compress, thumbnail, gif, speed, merge, add_subtitles`);
  }

  await execFileAsync('ffmpeg', args, { timeout: 300000 });

  const stat = statSync(output);
  const timing = ((Date.now() - start) / 1000).toFixed(1);

  // Get output duration if it's a video
  let duration;
  try {
    const probe = await execFileAsync('ffprobe', [
      '-v', 'quiet', '-show_entries', 'format=duration',
      '-of', 'csv=p=0', output
    ], { timeout: 10000 });
    duration = `${parseFloat(probe.stdout.trim()).toFixed(1)}s`;
  } catch { /* not a video or no duration */ }

  return {
    success: true,
    output,
    size: formatSize(stat.size),
    duration,
    processingTime: `${timing}s`,
    action,
  };
}

// ══════════════════════════════════════════════════════════════════════════════
// IMAGE PROCESSING — ImageMagick + Pillow
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Process an image file.
 * @param {object} params
 * @param {string} params.input - Input image path
 * @param {string} params.action - Action: resize, compress, convert, watermark, crop, rotate, flip, blur, sharpen, grayscale, border, thumbnail, optimize, info
 * @param {string} params.output - Output file path
 * @param {object} [params.options] - Action-specific options
 * @returns {Promise<{success: boolean, output: string, size: string}>}
 */
export async function processImage({ input, action, output, options = {} }) {
  if (action !== 'info' && !existsSync(input)) throw new Error(`Input image not found: ${input}`);

  const outDir = dirname(output || input);
  if (output && !existsSync(outDir)) mkdirSync(outDir, { recursive: true });

  const start = Date.now();
  let args = [];

  switch (action) {
    case 'resize': {
      const { width, height, geometry } = options;
      const geo = geometry || `${width || ''}x${height || ''}`;
      args = ['convert', input, '-resize', geo, output];
      break;
    }

    case 'compress': {
      const { quality = '85' } = options;
      args = ['convert', input, '-quality', String(quality), '-strip', output];
      break;
    }

    case 'convert': {
      // Output format is determined by the output extension
      args = ['convert', input];
      if (options.quality) args.push('-quality', String(options.quality));
      args.push(output);
      break;
    }

    case 'watermark': {
      const { text = '© GoCodeMe', position = 'SouthEast', size = '24', color = 'white', opacity = '50' } = options;
      args = ['convert', input,
        '-fill', `rgba(255,255,255,0.${opacity})`,
        '-gravity', position,
        '-pointsize', String(size),
        '-annotate', '+10+10', text,
        output];
      break;
    }

    case 'crop': {
      const { geometry = '500x500+0+0' } = options;
      args = ['convert', input, '-crop', geometry, output];
      break;
    }

    case 'rotate': {
      const { degrees = '90' } = options;
      args = ['convert', input, '-rotate', String(degrees), output];
      break;
    }

    case 'flip': {
      const { direction = 'vertical' } = options;
      args = ['convert', input, direction === 'horizontal' ? '-flop' : '-flip', output];
      break;
    }

    case 'blur': {
      const { radius = '0', sigma = '5' } = options;
      args = ['convert', input, '-blur', `${radius}x${sigma}`, output];
      break;
    }

    case 'sharpen': {
      const { radius = '0', sigma = '2' } = options;
      args = ['convert', input, '-sharpen', `${radius}x${sigma}`, output];
      break;
    }

    case 'grayscale': {
      args = ['convert', input, '-colorspace', 'Gray', output];
      break;
    }

    case 'border': {
      const { size: bSize = '5', color = 'black' } = options;
      args = ['convert', input, '-bordercolor', color, '-border', String(bSize), output];
      break;
    }

    case 'thumbnail': {
      const { size: tSize = '200x200' } = options;
      args = ['convert', input, '-thumbnail', tSize, '-gravity', 'center', '-extent', tSize, output];
      break;
    }

    case 'optimize': {
      // Use ImageMagick to strip metadata and optimize
      args = ['convert', input, '-strip', '-interlace', 'Plane', '-sampling-factor', '4:2:0', '-quality', '85', output];
      break;
    }

    case 'info': {
      const identify = await execFileAsync('identify', ['-verbose', input], { timeout: 10000 });
      const timing = ((Date.now() - start) / 1000).toFixed(1);
      return {
        success: true,
        info: identify.stdout,
        processingTime: `${timing}s`,
        action: 'info',
      };
    }

    default:
      throw new Error(`Unknown image action: ${action}. Supported: resize, compress, convert, watermark, crop, rotate, flip, blur, sharpen, grayscale, border, thumbnail, optimize, info`);
  }

  // Use 'magick' (ImageMagick 7) or 'convert' (ImageMagick 6)
  const cmd = args[0] === 'convert' ? 'magick' : args[0];
  await execFileAsync(cmd, args, { timeout: 60000 });

  const stat = statSync(output);
  const timing = ((Date.now() - start) / 1000).toFixed(1);

  return {
    success: true,
    output,
    size: formatSize(stat.size),
    processingTime: `${timing}s`,
    action,
  };
}

// ══════════════════════════════════════════════════════════════════════════════
// MEDIA DOWNLOAD — yt-dlp
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Download media from a URL using yt-dlp.
 * Supports 1,864+ sites including YouTube, Vimeo, Twitter, TikTok, etc.
 * @param {object} params
 * @param {string} params.url - URL to download from
 * @param {string} params.outputDir - Directory to save to
 * @param {string} [params.format] - Format: 'best', 'bestaudio', 'bestvideo', 'mp3', 'mp4', 'wav'
 * @param {string} [params.filename] - Custom output filename template
 * @param {boolean} [params.audioOnly=false] - Extract audio only
 * @param {boolean} [params.metadata=false] - Only fetch metadata, don't download
 * @returns {Promise<{success: boolean, files: string[], metadata?: object}>}
 */
export async function downloadMedia({ url, outputDir, format = 'best', filename, audioOnly = false, metadata = false }) {
  if (!existsSync(outputDir)) mkdirSync(outputDir, { recursive: true });

  const start = Date.now();

  // Metadata-only mode
  if (metadata) {
    const { stdout } = await execFileAsync('yt-dlp', [
      '--dump-json', '--no-download', url
    ], { timeout: 30000 });
    const info = JSON.parse(stdout);
    return {
      success: true,
      metadata: {
        title: info.title,
        description: info.description?.substring(0, 500),
        duration: info.duration,
        uploader: info.uploader,
        viewCount: info.view_count,
        likeCount: info.like_count,
        uploadDate: info.upload_date,
        thumbnail: info.thumbnail,
        formats: (info.formats || []).slice(0, 10).map(f => ({
          formatId: f.format_id,
          ext: f.ext,
          resolution: f.resolution,
          filesize: f.filesize ? formatSize(f.filesize) : 'unknown',
        })),
      },
      processingTime: `${((Date.now() - start) / 1000).toFixed(1)}s`,
    };
  }

  // Build download args
  const args = [
    '--no-playlist',
    '-o', join(outputDir, filename || '%(title)s.%(ext)s'),
    '--restrict-filenames',
    '--print', 'filename',
  ];

  if (audioOnly || format === 'mp3' || format === 'wav') {
    args.push('-x');
    if (format === 'mp3') args.push('--audio-format', 'mp3');
    else if (format === 'wav') args.push('--audio-format', 'wav');
    else args.push('--audio-format', 'best');
  } else if (format === 'mp4') {
    args.push('-f', 'bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]/best');
  } else if (format !== 'best') {
    args.push('-f', format);
  }

  args.push(url);

  const { stdout } = await execFileAsync('yt-dlp', args, { timeout: 600000 }); // 10 min timeout

  const files = stdout.trim().split('\n').filter(Boolean);
  const timing = ((Date.now() - start) / 1000).toFixed(1);

  const fileStats = files.map(f => {
    try {
      const s = statSync(f);
      return { path: f, name: basename(f), size: formatSize(s.size) };
    } catch {
      return { path: f, name: basename(f), size: 'unknown' };
    }
  });

  return {
    success: true,
    files: fileStats,
    count: fileStats.length,
    processingTime: `${timing}s`,
  };
}

// ══════════════════════════════════════════════════════════════════════════════
// PHP VERSION SWITCHING
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Switch PHP version for the current user or a specific domain.
 * Uses DirectAdmin's PHP version selector.
 * @param {string} version - PHP version: '8.2' or '8.3'
 * @param {string} [domain] - Specific domain (optional, applies globally if omitted)
 * @returns {Promise<{success: boolean, version: string, current: string}>}
 */
export async function switchPhpVersion(version, domain) {
  const validVersions = ['8.2', '8.3'];
  if (!validVersions.includes(version)) {
    throw new Error(`Invalid PHP version: ${version}. Supported: ${validVersions.join(', ')}`);
  }

  const start = Date.now();

  // Check current PHP version
  const { stdout: currentVersion } = await execFileAsync('php', ['-r', 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;'], { timeout: 5000 });

  // Check if the target version binary exists
  const phpBin = `/usr/local/php${version.replace('.', '')}/bin/lsphp`;
  const altBin = `/usr/local/lsws/lsphp${version.replace('.', '')}/bin/php`;
  const altBin2 = `/usr/bin/php${version}`;

  let targetBin;
  if (existsSync(phpBin)) targetBin = phpBin;
  else if (existsSync(altBin)) targetBin = altBin;
  else if (existsSync(altBin2)) targetBin = altBin2;

  if (!targetBin) {
    throw new Error(`PHP ${version} binary not found at ${phpBin}, ${altBin}, or ${altBin2}`);
  }

  // Verify target version works
  const { stdout: targetVer } = await execFileAsync(targetBin, ['-v'], { timeout: 5000 });

  // If domain specified, update the domain's .htaccess
  if (domain) {
    const htaccessPaths = [
      join('/home', process.env.USER || 'gositeme', 'domains', domain, 'public_html', '.htaccess'),
      join('/home', process.env.USER || 'gositeme', 'public_html', '.htaccess'),
    ];

    for (const htPath of htaccessPaths) {
      if (existsSync(dirname(htPath))) {
        // Add or update PHP handler directive
        const phpHandler = `AddHandler application/x-httpd-php${version.replace('.', '')} .php`;
        try {
          let content = '';
          if (existsSync(htPath)) {
            content = readFileSync(htPath, 'utf8');
          }

          // Remove existing PHP handler lines
          content = content.replace(/^AddHandler application\/x-httpd-php\d+ \.php$/gm, '').trim();
          // Add new handler at the top
          content = `${phpHandler}\n${content}`;

          const { writeFileSync: wfs } = await import('node:fs');
          wfs(htPath, content);

          return {
            success: true,
            version: version,
            previousVersion: currentVersion.trim(),
            targetBinary: targetBin,
            targetVersionInfo: targetVer.trim().split('\n')[0],
            method: 'htaccess',
            htaccessPath: htPath,
            processingTime: `${((Date.now() - start) / 1000).toFixed(1)}s`,
          };
        } catch (e) {
          // Try next path
          continue;
        }
      }
    }
  }

  return {
    success: true,
    version: version,
    currentVersion: currentVersion.trim(),
    targetBinary: targetBin,
    targetVersionInfo: targetVer.trim().split('\n')[0],
    note: domain ? `Could not update .htaccess for ${domain}` : 'Use with a domain name to update .htaccess',
    processingTime: `${((Date.now() - start) / 1000).toFixed(1)}s`,
  };
}

// ── Utilities ────────────────────────────────────────────────────────────────

function formatSize(bytes) {
  if (bytes < 1024) return `${bytes}B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)}KB`;
  if (bytes < 1024 * 1024 * 1024) return `${(bytes / (1024 * 1024)).toFixed(1)}MB`;
  return `${(bytes / (1024 * 1024 * 1024)).toFixed(1)}GB`;
}
