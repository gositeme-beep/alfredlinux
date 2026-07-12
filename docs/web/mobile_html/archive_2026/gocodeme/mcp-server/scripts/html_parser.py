#!/usr/bin/env python3
"""
html_parser.py — BeautifulSoup4 HTML parser for Alfred AI fetch_url upgrade.

Usage:
  echo '<html>...</html>' | python3 html_parser.py [--selector CSS_SELECTOR] [--links] [--images] [--tables] [--headings] [--metadata]

Reads HTML from stdin, outputs clean text to stdout.
"""

import sys
import json
import argparse
from bs4 import BeautifulSoup

def parse_html(html, selector=None, extract_links=False, extract_images=False,
               extract_tables=False, extract_headings=False, extract_metadata=False):
    soup = BeautifulSoup(html, 'html.parser')
    result = {}

    # Remove unwanted tags
    for tag in soup.find_all(['script', 'style', 'noscript', 'iframe', 'svg']):
        tag.decompose()

    # CSS selector mode — return only matching elements
    if selector:
        elements = soup.select(selector)
        result['selector'] = selector
        result['matches'] = len(elements)
        result['text'] = '\n\n'.join(el.get_text(strip=True, separator=' ') for el in elements)
        return result

    # Extract metadata
    if extract_metadata:
        meta = {}
        title_tag = soup.find('title')
        if title_tag:
            meta['title'] = title_tag.get_text(strip=True)
        for tag in soup.find_all('meta'):
            name = tag.get('name', tag.get('property', '')).lower()
            content = tag.get('content', '')
            if name and content:
                meta[name] = content
        result['metadata'] = meta

    # Extract headings hierarchy
    if extract_headings:
        headings = []
        for h in soup.find_all(['h1', 'h2', 'h3', 'h4', 'h5', 'h6']):
            headings.append({
                'level': int(h.name[1]),
                'text': h.get_text(strip=True)
            })
        result['headings'] = headings

    # Extract links
    if extract_links:
        links = []
        for a in soup.find_all('a', href=True):
            text = a.get_text(strip=True)
            if text or a['href']:
                links.append({'text': text, 'href': a['href']})
        result['links'] = links[:200]  # Cap at 200

    # Extract images
    if extract_images:
        images = []
        for img in soup.find_all('img'):
            src = img.get('src', '')
            alt = img.get('alt', '')
            if src:
                images.append({'src': src, 'alt': alt})
        result['images'] = images[:100]

    # Extract tables
    if extract_tables:
        tables = []
        for table in soup.find_all('table'):
            rows = []
            for tr in table.find_all('tr'):
                cells = [td.get_text(strip=True) for td in tr.find_all(['td', 'th'])]
                if cells:
                    rows.append(cells)
            if rows:
                tables.append(rows)
        result['tables'] = tables[:10]

    # Main content extraction — focus on article/main content
    main_content = (
        soup.find('main') or
        soup.find('article') or
        soup.find(attrs={'role': 'main'}) or
        soup.find(id='content') or
        soup.find(class_='content') or
        soup.body or
        soup
    )

    # Remove nav, header, footer, aside for cleaner text
    for tag in main_content.find_all(['nav', 'header', 'footer', 'aside']):
        tag.decompose()

    # Get clean text
    text = main_content.get_text(separator='\n', strip=True)
    # Collapse multiple blank lines
    lines = [l.strip() for l in text.split('\n')]
    text = '\n'.join(l for l in lines if l)

    result['text'] = text
    result['textLength'] = len(text)

    return result


def main():
    parser = argparse.ArgumentParser(description='Parse HTML with BeautifulSoup4')
    parser.add_argument('--selector', '-s', help='CSS selector to extract specific elements')
    parser.add_argument('--links', action='store_true', help='Extract all links')
    parser.add_argument('--images', action='store_true', help='Extract all images')
    parser.add_argument('--tables', action='store_true', help='Extract tables as arrays')
    parser.add_argument('--headings', action='store_true', help='Extract heading hierarchy')
    parser.add_argument('--metadata', action='store_true', help='Extract page metadata')
    parser.add_argument('--json', action='store_true', help='Output as JSON')
    args = parser.parse_args()

    html = sys.stdin.read()
    if not html.strip():
        print('(empty input)', file=sys.stderr)
        sys.exit(1)

    result = parse_html(
        html,
        selector=args.selector,
        extract_links=args.links,
        extract_images=args.images,
        extract_tables=args.tables,
        extract_headings=args.headings,
        extract_metadata=args.metadata,
    )

    # If JSON mode or structured data requested, output JSON
    if args.json or args.links or args.images or args.tables or args.headings or args.metadata:
        json.dump(result, sys.stdout, indent=2, ensure_ascii=False)
    else:
        # Plain text mode — just output the text
        print(result.get('text', ''))


if __name__ == '__main__':
    main()
