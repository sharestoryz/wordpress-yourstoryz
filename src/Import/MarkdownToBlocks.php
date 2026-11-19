<?php

declare(strict_types=1);

namespace YourStoryz\Import;

use DOMDocument;
use DOMElement;
use DOMNode;
use YourStoryz_Vendor_Parsedown;

final class MarkdownToBlocks
{
    public function fromMarkdown(string $markdown): string
    {
        $parsedown = new YourStoryz_Vendor_Parsedown;
        $parsedown->setSafeMode(true);

        $html = $parsedown->text($markdown);

        if (trim($html) === '') {
            return '';
        }

        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><body>'.$html.'</body>', LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $blocks = [];

        foreach ($document->getElementsByTagName('body')->item(0)->childNodes as $node) {
            if ($node instanceof DOMElement) {
                $blocks[] = $this->block($document, $node);
            }
        }

        return implode("\n\n", $blocks);
    }

    public function fromPlainText(string $text): string
    {
        $paragraphs = preg_split('/\R{2,}/', trim($text)) ?: [];
        $blocks = [];

        foreach ($paragraphs as $paragraph) {
            if (trim($paragraph) === '') {
                continue;
            }

            $lines = array_map(
                fn (string $line): string => htmlspecialchars(trim($line), ENT_QUOTES, 'UTF-8'),
                preg_split('/\R/', trim($paragraph)) ?: [],
            );

            $blocks[] = $this->wrap('paragraph', '<p>'.implode('<br>', $lines).'</p>');
        }

        return implode("\n\n", $blocks);
    }

    private function block(DOMDocument $document, DOMElement $element): string
    {
        $tag = $element->tagName;

        if ($tag === 'p') {
            return $this->wrap('paragraph', '<p>'.$this->innerHtml($document, $element).'</p>');
        }

        if (preg_match('/^h([1-6])$/', $tag, $matches) === 1) {
            $level = (int) $matches[1];

            return $this->wrap(
                'heading',
                '<'.$tag.' class="wp-block-heading">'.$this->innerHtml($document, $element).'</'.$tag.'>',
                $level === 2 ? [] : ['level' => $level],
            );
        }

        if ($tag === 'ul' || $tag === 'ol') {
            return $this->list($document, $element);
        }

        return $this->wrap('html', (string) $document->saveHTML($element));
    }

    private function list(DOMDocument $document, DOMElement $list): string
    {
        $items = [];

        foreach ($list->childNodes as $item) {
            if (! $item instanceof DOMElement || $item->tagName !== 'li') {
                continue;
            }

            $items[] = $this->wrap('list-item', '<li>'.$this->listItemHtml($document, $item).'</li>');
        }

        return $this->wrap(
            'list',
            '<'.$list->tagName.' class="wp-block-list">'.implode('', $items).'</'.$list->tagName.'>',
            $list->tagName === 'ol' ? ['ordered' => true] : [],
        );
    }

    private function listItemHtml(DOMDocument $document, DOMElement $item): string
    {
        $html = '';

        foreach ($item->childNodes as $child) {
            if ($child instanceof DOMElement && ($child->tagName === 'ul' || $child->tagName === 'ol')) {
                $html .= $this->list($document, $child);
            } elseif ($child instanceof DOMElement && $child->tagName === 'p') {
                $html .= $this->innerHtml($document, $child);
            } else {
                $html .= $document->saveHTML($child);
            }
        }

        return trim($html);
    }

    private function innerHtml(DOMDocument $document, DOMNode $node): string
    {
        $html = '';

        foreach ($node->childNodes as $child) {
            $html .= $document->saveHTML($child);
        }

        return $html;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function wrap(string $block, string $html, array $attributes = []): string
    {
        $comment = $block.($attributes === [] ? '' : ' '.json_encode($attributes, JSON_UNESCAPED_SLASHES));

        return "<!-- wp:{$comment} -->\n{$html}\n<!-- /wp:{$block} -->";
    }
}
