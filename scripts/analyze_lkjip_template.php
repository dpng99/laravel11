<?php

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/analyze_lkjip_template.php <template.docx>\n");
    exit(1);
}

$path = $argv[1];
$archive = new PharData($path);
$xml = $archive['word/document.xml']->getContent();
unset($archive);

$document = new DOMDocument;
$document->loadXML($xml);
$xpath = new DOMXPath($document);
$xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

$text = static function (DOMNode $node) use ($xpath): string {
    $parts = [];

    foreach ($xpath->query('.//w:t', $node) as $textNode) {
        $parts[] = $textNode->nodeValue;
    }

    return trim((string) preg_replace('/\s+/u', ' ', implode('', $parts)));
};

$paragraphs = iterator_to_array($xpath->query('//w:body/w:p | //w:body/w:tbl/w:tr/w:tc/w:p'));
$interesting = [];

foreach ($paragraphs as $index => $paragraph) {
    $paragraphText = $text($paragraph);
    $hasHighlight = $xpath->query('.//w:highlight | .//w:shd[@w:fill="FFFF00" or @w:fill="yellow"]', $paragraph)->length > 0;
    $hasPlaceholder = preg_match('/\.{3,}|…{2,}|\$\{[^}]+\}/u', $paragraphText) === 1;

    if (! $hasHighlight && ! $hasPlaceholder) {
        continue;
    }

    $before = $index > 0 ? $text($paragraphs[$index - 1]) : '';
    $after = isset($paragraphs[$index + 1]) ? $text($paragraphs[$index + 1]) : '';
    $interesting[] = [
        'index' => $index + 1,
        'before' => $before,
        'text' => $paragraphText,
        'after' => $after,
        'highlight' => $hasHighlight,
    ];
}

echo 'FILE: '.basename($path).PHP_EOL;
echo 'INTERESTING PARAGRAPHS: '.count($interesting).PHP_EOL;

foreach ($interesting as $item) {
    echo PHP_EOL.'P'.$item['index'].($item['highlight'] ? ' [YELLOW]' : '').PHP_EOL;
    echo '  BEFORE: '.$item['before'].PHP_EOL;
    echo '  TEXT:   '.$item['text'].PHP_EOL;
    echo '  AFTER:  '.$item['after'].PHP_EOL;
}

echo PHP_EOL.'TABLES WITH PLACEHOLDERS'.PHP_EOL;

foreach ($xpath->query('//w:tbl') as $tableIndex => $table) {
    $tableText = $text($table);

    if (preg_match('/\.{3,}|…{2,}|\$\{[^}]+\}/u', $tableText) !== 1) {
        continue;
    }

    echo PHP_EOL.'TABLE '.($tableIndex + 1).PHP_EOL;
    foreach ($xpath->query('./w:tr', $table) as $row) {
        $cells = [];
        foreach ($xpath->query('./w:tc', $row) as $cell) {
            $cells[] = $text($cell);
        }
        echo '  '.implode(' | ', $cells).PHP_EOL;
    }
}
