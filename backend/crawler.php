<?php
// crawler.php

function crawlWebsite($url) {
    $html = file_get_contents($url);
    if ($html === FALSE) {
        return null;
    }

    // Lihtne DOM parser
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    // Näidis: Toodete nimekirja ekstraktimine
    $products = [];
    // Asenda vastavalt e-poe struktuurile
    $productNodes = $xpath->query("//div[@class='product']");
    foreach ($productNodes as $node) {
        $nameNode = $xpath->query(".//h2", $node)->item(0);
        $priceNode = $xpath->query(".//span[@class='price']", $node)->item(0);
        $categoryNode = $xpath->query(".//a[@class='category']", $node)->item(0);

        $name = $nameNode ? $nameNode->nodeValue : 'N/A';
        $price = $priceNode ? $priceNode->nodeValue : 'N/A';
        $category = $categoryNode ? $categoryNode->nodeValue : 'N/A';

        $products[] = [
            'name' => trim($name),
            'price' => trim($price),
            'category' => trim($category)
        ];
    }

    // Kategooriate list
    $categories = array_unique(array_map(function($product) {
        return $product['category'];
    }, $products));

    return [
        'url' => $url,
        'products' => $products,
        'categories' => $categories
    ];
}

?>
