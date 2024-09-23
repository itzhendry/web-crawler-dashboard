<?php
// crawler.php

function crawlWebsite($url) {
    // Set custom headers, including User-Agent to mimic a real browser
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

    // Example: Extract product list
    $products = [];
    // Adjust according to the structure of the e-commerce site
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
