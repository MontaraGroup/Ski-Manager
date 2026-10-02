<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Markdown Content Negotiation Filter for AI Agents.
 *
 * Implements the standard Markdown Content Negotiation specification:
 * - https://isitagentready.com/.well-known/agent-skills/markdown-negotiation/SKILL.md
 * - https://developers.cloudflare.com/fundamentals/reference/markdown-for-agents/
 *
 * When an incoming request includes `Accept: text/markdown`, this filter transforms
 * the rendered HTML response into clean, semantic Markdown stripped of extraneous navigation,
 * styling, and scripts while preserving page structure, metadata frontmatter, tables, and JSON-LD.
 */
class MarkdownNegotiationFilter implements FilterInterface
{
    /**
     * Non-content HTML tags that should be completely removed along with their contents.
     */
    private const STRIP_TAGS = [
        'head',
        'script',
        'style',
        'noscript',
        'svg',
        'iframe',
        'canvas',
        'template',
        'nav',
        'header',
        'footer',
        'dialog',
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        // No action needed before controller execution.
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // 1. Inspect Accept header
        $accept = $request->getHeaderLine('Accept');
        if (! str_contains(strtolower($accept), 'text/markdown')) {
            return $response;
        }

        // Ignore if explicitly disabled with q=0
        if (preg_match('/text\/markdown\s*;\s*q=0(?:\.0+)?(?:\b|,)/i', $accept)) {
            return $response;
        }

        // 2. Only transform successful or displayable HTML responses (skip redirects 3xx)
        $statusCode = $response->getStatusCode();
        if ($statusCode >= 300 && $statusCode < 400) {
            return $response;
        }

        $contentType = $response->getHeaderLine('Content-Type');
        $isHtml = ($contentType === '' || str_contains(strtolower($contentType), 'text/html') || str_contains(strtolower($contentType), 'application/xhtml+xml'));
        if (! $isHtml) {
            return $response;
        }

        $html = $response->getBody();
        if ($html === null || trim($html) === '') {
            return $response;
        }

        // 3. Compute original token heuristic before stripping
        $originalTokens = max(1, (int) ceil(strlen($html) / 4));

        // 4. Convert HTML into clean, semantic Markdown
        $markdown = $this->convertToMarkdown($html);
        $markdownTokens = max(1, (int) ceil(strlen($markdown) / 4));

        // 5. Update response body & headers
        $response->setBody($markdown);
        $response->setHeader('Content-Type', 'text/markdown; charset=UTF-8');
        $response->setHeader('X-Markdown-Tokens', (string) $markdownTokens);
        $response->setHeader('X-Original-Tokens', (string) $originalTokens);
        $response->setHeader('Content-Signal', 'ai-train=yes, search=yes, ai-input=yes');

        // Manage Vary header so edge/browser caches vary content by Accept
        $vary = $response->getHeaderLine('Vary');
        if ($vary === '') {
            $response->setHeader('Vary', 'Accept');
        } elseif (! str_contains($vary, 'Accept')) {
            $response->setHeader('Vary', $vary . ', Accept');
        }

        return $response;
    }

    /**
     * Convert an HTML string into structured Markdown.
     */
    public function convertToMarkdown(string $html): string
    {
        // A. Extract JSON-LD script blocks before stripping tags
        $jsonLdBlocks = [];
        if (preg_match_all('/<script\b[^>]*type=[\'"]application\/ld\+json[\'"][^>]*>(.*?)<\/script>/is', $html, $matches)) {
            foreach ($matches[1] as $block) {
                $trimmed = trim($block);
                if ($trimmed !== '') {
                    $jsonLdBlocks[] = $trimmed;
                }
            }
        }

        // B. Extract metadata for YAML Frontmatter
        $frontmatter = $this->extractFrontmatter($html);

        // C. Clean up cookie banners, modals, and screen-reader anchors
        $body = $html;
        $body = preg_replace('/<!--\s*Cookie Consent Banner\s*-->.*?<\/div>\s*<\/div>\s*<\/div>/is', ' ', $body) ?? $body;
        $body = preg_replace('/<div\b[^>]*id=[\'"]cookieConsent[\'"][^>]*>.*?<\/div>\s*<\/div>\s*<\/div>/is', ' ', $body) ?? $body;
        $body = preg_replace('/<(i|span|b|strong|em)\b[^>]*>\s*<\/\1>/i', '', $body) ?? $body;

        // D. Strip standard non-content structural tags (and their contents)
        foreach (self::STRIP_TAGS as $tag) {
            $body = preg_replace('/<' . $tag . '\b[^>]*>.*?<\/' . $tag . '>/is', ' ', $body) ?? $body;
        }

        // Strip HTML comments
        $body = preg_replace('/<!--.*?-->/s', ' ', $body) ?? $body;

        // E. Convert tables to Markdown pipe syntax
        $body = preg_replace_callback('/<table\b[^>]*>(.*?)<\/table>/is', function ($tblMatches) {
            return $this->convertTableToMarkdown($tblMatches[1]);
        }, $body) ?? $body;

        // F. Headings (h1 - h6)
        for ($i = 1; $i <= 6; $i++) {
            $body = preg_replace_callback('/<h' . $i . '\b[^>]*>(.*?)<\/h' . $i . '>/is', function ($hMatches) use ($i) {
                $text = trim(strip_tags($hMatches[1]));
                return $text !== '' ? "\n\n" . str_repeat('#', $i) . ' ' . $text . "\n\n" : '';
            }, $body) ?? $body;
        }

        // G. Code blocks & inline code
        $body = preg_replace_callback('/<pre\b[^>]*><code(?:\s+class=[\'"](?:language-)?([a-zA-Z0-9_-]+)[\'"])?[^>]*>(.*?)<\/code><\/pre>/is', function ($cMatches) {
            $lang = $cMatches[1] ?? '';
            $code = html_entity_decode(strip_tags($cMatches[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return "\n\n```" . $lang . "\n" . trim($code) . "\n```\n\n";
        }, $body) ?? $body;

        $body = preg_replace_callback('/<pre\b[^>]*>(.*?)<\/pre>/is', function ($cMatches) {
            $code = html_entity_decode(strip_tags($cMatches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return "\n\n```\n" . trim($code) . "\n```\n\n";
        }, $body) ?? $body;

        $body = preg_replace_callback('/<code\b[^>]*>(.*?)<\/code>/is', function ($cMatches) {
            $code = html_entity_decode(strip_tags($cMatches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return '`' . trim($code) . '`';
        }, $body) ?? $body;

        // H. Blockquotes
        $body = preg_replace_callback('/<blockquote\b[^>]*>(.*?)<\/blockquote>/is', function ($bMatches) {
            $text = trim(strip_tags($bMatches[1]));
            $lines = explode("\n", $text);
            $quoted = array_map(fn($line) => '> ' . trim($line), $lines);
            return "\n\n" . implode("\n", $quoted) . "\n\n";
        }, $body) ?? $body;

        // I. Lists (ul, ol)
        $body = preg_replace_callback('/<(ul|ol)\b[^>]*>(.*?)<\/\1>/is', function ($listMatches) {
            $isOl = strtolower($listMatches[1]) === 'ol';
            $content = $listMatches[2];
            if (preg_match_all('/<li\b[^>]*>(.*?)<\/li>/is', $content, $liMatches)) {
                $items = [];
                $counter = 1;
                foreach ($liMatches[1] as $itemHtml) {
                    $itemText = trim(strip_tags($itemHtml));
                    if ($itemText !== '') {
                        $prefix = $isOl ? ($counter++ . '. ') : '* ';
                        $items[] = $prefix . $itemText;
                    }
                }
                return "\n\n" . implode("\n", $items) . "\n\n";
            }
            return '';
        }, $body) ?? $body;

        // J. Formatting (strong, em)
        $body = preg_replace('/<(strong|b)\b[^>]*>(.*?)<\/\1>/is', '**$2**', $body) ?? $body;
        $body = preg_replace('/<(em|i)\b[^>]*>(.*?)<\/\1>/is', '*$2*', $body) ?? $body;

        // K. Images (process before links so linked images work: [![alt](src)](url))
        $body = preg_replace_callback('/<img\b([^>]*)>/is', function ($imgMatches) {
            $attrs = $imgMatches[1];
            $src = '';
            $alt = '';
            if (preg_match('/src=[\'"](.*?)[\'"]/i', $attrs, $srcMatch)) {
                $src = trim($srcMatch[1]);
            }
            if (preg_match('/alt=[\'"](.*?)[\'"]/i', $attrs, $altMatch)) {
                $alt = trim($altMatch[1]);
            }
            if ($src === '') {
                return '';
            }
            return '![' . ($alt !== '' ? $alt : 'image') . '](' . $src . ')';
        }, $body) ?? $body;

        // L. Links
        $body = preg_replace_callback('/<a\b([^>]*)>(.*?)<\/a>/is', function ($aMatches) {
            $attrs = $aMatches[1];
            $inner = trim(strip_tags($aMatches[2]));
            $href = '';
            if (preg_match('/href=[\'"](.*?)[\'"]/i', $attrs, $hrefMatch)) {
                $href = trim($hrefMatch[1]);
            }
            // Discard jump links, bot traps, and javascript actions
            if ($href === '' || str_starts_with($href, 'javascript:') || str_starts_with($href, '#') || str_contains($href, '/cdn-cgi/')) {
                return (str_starts_with($href, '#') || str_contains($href, '/cdn-cgi/')) ? '' : $inner;
            }
            $label = $inner !== '' ? $inner : $href;
            return '[' . $label . '](' . $href . ')';
        }, $body) ?? $body;

        // M. Buttons
        $body = preg_replace_callback('/<button\b[^>]*>(.*?)<\/button>/is', function ($btnMatches) {
            $btnText = trim(strip_tags($btnMatches[1]));
            return $btnText !== '' ? '[' . $btnText . ']' : '';
        }, $body) ?? $body;

        // N. Structural breaks
        $body = preg_replace('/<hr\b[^>]*>/i', "\n\n---\n\n", $body) ?? $body;
        $body = preg_replace('/<br\b[^>]*>/i', "\n", $body) ?? $body;
        $body = preg_replace('/<(p|div|section|article|main|aside)\b[^>]*>/i', "\n\n", $body) ?? $body;
        $body = preg_replace('/<\/(p|div|section|article|main|aside)>/i', "\n\n", $body) ?? $body;

        // O. Strip any residual HTML tags
        $body = strip_tags($body);

        // P. Decode HTML entities
        $body = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Q. Clean up whitespace and stray formatting artifacts
        $body = preg_replace('/\*\*\s*\*\*/', '', $body) ?? $body;
        $rawLines = explode("\n", $body);
        $cleanLines = [];
        foreach ($rawLines as $line) {
            $trimmed = trim(preg_replace('/[ \t]+/', ' ', $line));
            // Filter out decorative bullets or stray single characters
            if ($trimmed !== '' && ! in_array($trimmed, ['•', '|', '*', '**', '[]'], true)) {
                $cleanLines[] = $trimmed;
            }
        }
        $body = implode("\n\n", $cleanLines);
        $body = preg_replace('/\n{3,}/', "\n\n", $body) ?? $body;
        $body = trim($body);

        // R. Assemble Markdown output
        $markdown = $frontmatter . $body;

        // Append JSON-LD structured data block
        if (! empty($jsonLdBlocks)) {
            $markdown .= "\n\n```json\n" . implode("\n", $jsonLdBlocks) . "\n```";
        }

        return trim($markdown) . "\n";
    }

    /**
     * Extract page metadata and format as YAML frontmatter.
     */
    private function extractFrontmatter(string $html): string
    {
        // Title: name="title" -> <title> -> property="og:title"
        $title = $this->getMetaContent($html, 'name', 'title');
        if ($title === '') {
            if (preg_match('/<title\b[^>]*>(.*?)<\/title>/is', $html, $m)) {
                $title = strip_tags($m[1]);
            }
        }
        if ($title === '') {
            $title = $this->getMetaContent($html, 'property', 'og:title');
        }

        // Description: name="description" -> property="og:description"
        $description = $this->getMetaContent($html, 'name', 'description');
        if ($description === '') {
            $description = $this->getMetaContent($html, 'property', 'og:description');
        }

        // Image: property="og:image" -> name="image"
        $image = $this->getMetaContent($html, 'property', 'og:image');
        if ($image === '') {
            $image = $this->getMetaContent($html, 'name', 'image');
        }

        $title = trim(html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $description = trim(html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $image = trim($image);

        if ($title === '' && $description === '' && $image === '') {
            return '';
        }

        $yaml = "---\n";
        if ($title !== '') {
            $yaml .= 'title: ' . json_encode($title, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        }
        if ($description !== '') {
            $yaml .= 'description: ' . json_encode($description, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        }
        if ($image !== '') {
            $yaml .= 'image: ' . json_encode($image, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        }
        $yaml .= "---\n\n";

        return $yaml;
    }

    /**
     * Retrieve content attribute value of a <meta> tag by attribute key and value.
     */
    private function getMetaContent(string $html, string $attrName, string $attrValue): string
    {
        if (preg_match_all('/<meta\b([^>]*)>/i', $html, $matches)) {
            $pattern = '/\b' . preg_quote($attrName, '/') . '\s*=\s*[\'"]' . preg_quote($attrValue, '/') . '[\'"]/i';
            foreach ($matches[1] as $tagAttrs) {
                if (preg_match($pattern, $tagAttrs)) {
                    if (preg_match('/\bcontent\s*=\s*[\'"](.*?)[\'"]/i', $tagAttrs, $contentMatch)) {
                        return $contentMatch[1];
                    }
                }
            }
        }
        return '';
    }

    /**
     * Convert an HTML <table> into Markdown pipe table syntax.
     */
    private function convertTableToMarkdown(string $tableHtml): string
    {
        if (! preg_match_all('/<tr\b[^>]*>(.*?)<\/tr>/is', $tableHtml, $rowMatches)) {
            return '';
        }

        $markdownRows = [];
        $isFirstRow = true;

        foreach ($rowMatches[1] as $rowContent) {
            if (! preg_match_all('/<(th|td)\b[^>]*>(.*?)<\/\1>/is', $rowContent, $cellMatches)) {
                continue;
            }

            $cells = [];
            foreach ($cellMatches[2] as $cellContent) {
                $cellText = trim(strip_tags($cellContent));
                $cellText = html_entity_decode($cellText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $cellText = str_replace(["\r", "\n", '|'], [' ', ' ', '\\|'], $cellText);
                $cellText = preg_replace('/[ \t]+/', ' ', $cellText);
                $cells[] = $cellText;
            }

            if (empty($cells)) {
                continue;
            }

            $markdownRows[] = '| ' . implode(' | ', $cells) . ' |';

            if ($isFirstRow) {
                $headerColumnCount = count($cells);
                $separators = array_fill(0, $headerColumnCount, '---');
                $markdownRows[] = '| ' . implode(' | ', $separators) . ' |';
                $isFirstRow = false;
            }
        }

        if (empty($markdownRows)) {
            return '';
        }

        return "\n\n" . implode("\n", $markdownRows) . "\n\n";
    }
}
