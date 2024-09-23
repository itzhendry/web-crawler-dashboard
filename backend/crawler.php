<?php
// crawler.php

function crawlWebsite($url) {
    // Set custom headers with User-Agent to mimic a real browser
    $options = [
        'http' => [
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:91.0) Gecko/20100101 Firefox/91.0\r\n"
        ]
    ];
    $context = stream_context_create($options);
    $html = @file_get_contents($url, false, $context);

    // Handle failure to load content
    if ($html === FALSE) {
        return ['error' => 'Failed to retrieve data from ' . $url];
    }

    // Simple DOM parser
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    // Initialize product array
    $products = [];

    // Adjust XPath queries based on the site
    if (strpos($url, 'sportland.ee') !== false) {
        // Sportland.ee (Example XPath, inspect if needed)
        $productNodes = $xpath->query("//div[contains(@class, 'product-item')]");
    } elseif (strpos($url, 'arvutitark.ee') !== false) {
        // Arvutitark.ee (Example XPath, inspect if needed)
        $productNodes = $xpath->query("//div[contains(@class, 'group')]");
    } elseif (strpos($url, 'kaup24.ee') !== false) {
        // Kaup24.ee (Example XPath, inspect if needed)
        $productNodes = $xpath->query("//div[contains(@class, 'product-list')]");
    } else {
        return null; // URL structure not recognized
    }

    // Iterate through product nodes and extract details
    foreach ($productNodes as $node) {
        $nameNode = $xpath->query(".//h2", $node)->item(0);
        $priceNode = $xpath->query(".//span[contains(@class, 'price')]", $node)->item(0);
        $categoryNode = $xpath->query(".//a[contains(@class, 'category')]", $node)->item(0);

        $name = $nameNode ? trim($nameNode->nodeValue) : 'N/A';
        $price = $priceNode ? trim($priceNode->nodeValue) : 'N/A';
        $category = $categoryNode ? trim($categoryNode->nodeValue) : 'N/A';

        $products[] = [
            'name' => $name,
            'price' => $price,
            'category' => $category
        ];
    }

    // List of unique categories
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
