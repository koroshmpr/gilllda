<?php
/**
 * Dependency-free HTML → Markdown converter (DOMDocument based).
 *
 * Headings (with level shift), paragraphs, nested lists, links (absolute), emphasis, code, fenced code,
 * tables (GFM, header row), blockquotes, images with alt (lazy-load aware), figures/captions,
 * details/summary, rules. Scripts, styles, forms, embeds and SVG are dropped.
 * Loaded lazily: only during a background rebuild or a .md request.
 *
 * @package GillldaAI
 */

if (!defined('ABSPATH')) {
    exit;
}

final class GAI_Markdown
{
    /** @var int Heading level shift. */
    private $shift = 0;

    /** @var int Minimum heading level. */
    private $min = 1;

    /** @var bool Keep images. */
    private $images = true;

    /** @var string Site base URL (no trailing slash). */
    private $base = '';

    /**
     * @param string $html HTML fragment.
     * @param array  $args shift (int), min (int), images (bool).
     */
    public static function convert($html, array $args = array())
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }
        $self         = new self();
        $self->shift  = (int) ($args['shift'] ?? 0);
        $self->min    = max(1, (int) ($args['min'] ?? 1));
        $self->images = (bool) ($args['images'] ?? true);
        $self->base   = untrailingslashit(home_url('/'));

        if (!class_exists('DOMDocument')) {
            return self::tidy(html_entity_decode(wp_strip_all_tags($html), ENT_QUOTES, 'UTF-8'));
        }
        $prev = libxml_use_internal_errors(true);
        $dom  = new DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML('<?xml encoding="utf-8"?><div id="gai-md-root">' . $html . '</div>', LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $dom->getElementById('gai-md-root');
        if (!$root) {
            return self::tidy(wp_strip_all_tags($html));
        }
        return self::tidy($self->children($root, 0));
    }

    /** Collapse blank lines/spaces outside code fences. */
    public static function tidy($md)
    {
        $lines = explode("\n", str_replace(array("\r\n", "\r", "\xC2\xA0"), array("\n", "\n", ' '), (string) $md));
        $fence = false;
        foreach ($lines as $i => $line) {
            if (strpos(ltrim($line), '```') === 0) {
                $fence = !$fence;
                continue;
            }
            if ($fence) {
                continue;
            }
            $line      = preg_replace('/(?<=\S) {2,}/u', ' ', $line);
            $line      = preg_replace('/ +([،؛:.!?؟)])/u', '$1', $line);
            $lines[$i] = rtrim(preg_match('/^\s+([-*]|\d+\.) /', $line) ? $line : ltrim($line));
        }
        return trim(preg_replace("/\n{3,}/", "\n\n", implode("\n", $lines)));
    }

    private function children(DOMNode $node, $depth)
    {
        $out = '';
        foreach ($node->childNodes as $child) {
            $out .= $this->node($child, $depth);
        }
        return $out;
    }

    private function inline(DOMNode $node)
    {
        return trim(preg_replace('/\s+/u', ' ', $this->children($node, 0)));
    }

    private function node(DOMNode $n, $depth)
    {
        if ($n->nodeType === XML_TEXT_NODE || $n->nodeType === XML_CDATA_SECTION_NODE) {
            return preg_replace('/\s+/u', ' ', $n->textContent);
        }
        if (!($n instanceof DOMElement)) {
            return '';
        }
        $tag = strtolower($n->nodeName);
        if ($n->getAttribute('aria-hidden') === 'true' || $n->hasAttribute('hidden')) {
            return '';
        }

        switch ($tag) {
            case 'script':
            case 'style':
            case 'noscript':
            case 'svg':
            case 'template':
            case 'form':
            case 'button':
            case 'input':
            case 'select':
            case 'textarea':
            case 'label':
            case 'iframe':
            case 'object':
            case 'embed':
            case 'video':
            case 'audio':
            case 'canvas':
            case 'nav':
            case 'head':
            case 'meta':
            case 'link':
                return '';

            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
                $level = min(6, max($this->min, (int) substr($tag, 1) + $this->shift));
                $text  = str_replace('#', '', $this->inline($n));
                return $text === '' ? '' : "\n\n" . str_repeat('#', $level) . ' ' . $text . "\n\n";

            case 'p':
            case 'div':
            case 'section':
            case 'article':
            case 'header':
            case 'footer':
            case 'main':
            case 'aside':
            case 'figure':
            case 'dl':
            case 'details':
            case 'center':
                $inner = trim($this->children($n, $depth));
                return $inner === '' ? '' : "\n\n" . $inner . "\n\n";

            case 'summary':
            case 'dt':
                $t = $this->inline($n);
                return $t === '' ? '' : "\n\n**" . $t . "**\n\n";

            case 'dd':
                $t = $this->inline($n);
                return $t === '' ? '' : "\n\n" . $t . "\n\n";

            case 'figcaption':
                $t = $this->inline($n);
                return $t === '' ? '' : "\n\n*" . $t . "*\n\n";

            case 'br':
                return "  \n";

            case 'hr':
                return "\n\n---\n\n";

            case 'strong':
            case 'b':
                $t = $this->inline($n);
                return $t === '' ? '' : ' **' . $t . '** ';

            case 'em':
            case 'i':
            case 'cite':
                $t = $this->inline($n);
                return $t === '' ? '' : ' *' . $t . '* ';

            case 'del':
            case 's':
            case 'strike':
                $t = $this->inline($n);
                return $t === '' ? '' : '~~' . $t . '~~';

            case 'code':
            case 'kbd':
                $t = trim($n->textContent);
                if ($t === '') {
                    return '';
                }
                $fence = strpos($t, '`') !== false ? '``' : '`';
                return ' ' . $fence . $t . $fence . ' ';

            case 'pre':
                $code = rtrim($n->textContent);
                $lang = '';
                $el   = $n->getElementsByTagName('code')->item(0);
                foreach (array($n, $el) as $c) {
                    if ($c instanceof DOMElement && preg_match('/(?:language|lang)-([a-z0-9+#-]+)/i', $c->getAttribute('class'), $m)) {
                        $lang = strtolower($m[1]);
                        break;
                    }
                }
                return "\n\n```" . $lang . "\n" . $code . "\n```\n\n";

            case 'a':
                $text = $this->inline($n);
                $href = trim($n->getAttribute('href'));
                if ($text === '') {
                    // Image-only link: keep the image.
                    return $this->images ? $this->children($n, $depth) : '';
                }
                if ($href === '' || $href[0] === '#' || stripos($href, 'javascript:') === 0) {
                    return $text;
                }
                return '[' . str_replace(array('[', ']'), array('(', ')'), $text) . '](' . $this->abs($href) . ')';

            case 'img':
                return $this->img($n);

            case 'picture':
                $img = $n->getElementsByTagName('img')->item(0);
                return $img instanceof DOMElement ? $this->img($img) : '';

            case 'ul':
            case 'ol':
                return "\n\n" . $this->listing($n, $depth) . "\n\n";

            case 'li':
                return "\n- " . $this->inline($n) . "\n";

            case 'blockquote':
                $inner = self::tidy($this->children($n, $depth));
                return $inner === '' ? '' : "\n\n" . preg_replace('/^/m', '> ', $inner) . "\n\n";

            case 'table':
                return "\n\n" . $this->table($n) . "\n\n";

            default:
                return $this->children($n, $depth);
        }
    }

    private function img(DOMElement $n)
    {
        if (!$this->images) {
            return '';
        }
        $src = '';
        foreach (array('data-src', 'data-lazy-src', 'src') as $attr) {
            $v = trim($n->getAttribute($attr));
            if ($v !== '' && strpos($v, 'data:') !== 0) {
                $src = $v;
                break;
            }
        }
        if ($src === '') {
            $set = trim($n->getAttribute('data-srcset') ?: $n->getAttribute('srcset'));
            if ($set !== '') {
                $src = trim(strtok($set, ' ,'));
            }
        }
        if ($src === '') {
            return '';
        }
        $alt = trim(preg_replace('/\s+/u', ' ', $n->getAttribute('alt')));
        $alt = str_replace(array('[', ']'), array('(', ')'), $alt);
        return ' ![' . $alt . '](' . $this->abs($src) . ') ';
    }

    private function listing(DOMElement $list, $depth)
    {
        $ordered = strtolower($list->nodeName) === 'ol';
        $i       = max(1, (int) ($list->getAttribute('start') ?: 1));
        $indent  = str_repeat('  ', $depth);
        $lines   = array();
        foreach ($list->childNodes as $li) {
            if (!($li instanceof DOMElement) || strtolower($li->nodeName) !== 'li') {
                continue;
            }
            $text   = '';
            $nested = '';
            foreach ($li->childNodes as $c) {
                if ($c instanceof DOMElement && in_array(strtolower($c->nodeName), array('ul', 'ol'), true)) {
                    $nested .= "\n" . $this->listing($c, $depth + 1);
                } else {
                    $text .= $this->node($c, $depth + 1);
                }
            }
            $text = trim(preg_replace('/\s+/u', ' ', $text));
            if ($text === '' && $nested === '') {
                continue;
            }
            $lines[] = $indent . ($ordered ? ($i++) . '. ' : '- ') . $text . $nested;
        }
        return implode("\n", $lines);
    }

    private function table(DOMElement $table)
    {
        $rows = array();
        foreach ($table->getElementsByTagName('tr') as $tr) {
            $cells = array();
            foreach ($tr->childNodes as $cell) {
                if ($cell instanceof DOMElement && in_array(strtolower($cell->nodeName), array('td', 'th'), true)) {
                    $txt  = str_replace('|', '\|', $this->inline($cell));
                    $span = max(1, (int) $cell->getAttribute('colspan'));
                    $cells[] = $txt;
                    for ($k = 1; $k < $span; $k++) {
                        $cells[] = '';
                    }
                }
            }
            if ($cells) {
                $rows[] = $cells;
            }
        }
        if (!$rows) {
            return '';
        }
        $cols = max(array_map('count', $rows));
        if ($cols === 1) {
            // A one-column "table" is really a list.
            return implode("\n", array_map(function ($r) {
                return '- ' . $r[0];
            }, $rows));
        }
        $out = array();
        foreach ($rows as $i => $cells) {
            $out[] = '| ' . implode(' | ', array_pad($cells, $cols, '')) . ' |';
            if ($i === 0) {
                $out[] = '|' . str_repeat(' --- |', $cols);
            }
        }
        return implode("\n", $out);
    }

    private function abs($url)
    {
        $url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');
        if (preg_match('#^(https?:|mailto:|tel:)#i', $url)) {
            return str_replace(array(' ', '(', ')'), array('%20', '%28', '%29'), $url);
        }
        if (strpos($url, '//') === 0) {
            return 'https:' . $url;
        }
        return str_replace(array(' ', '(', ')'), array('%20', '%28', '%29'), $this->base . '/' . ltrim($url, '/'));
    }
}
