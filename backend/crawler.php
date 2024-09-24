<?php
// crawler.php

// Keep the existing function for fetching book categories
function fetchBookCategory($detailHref) {
    $baseUrl = 'http://books.toscrape.com/';
    $detailUrl = $baseUrl . $detailHref;
    $options = ['http' => ['header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:91.0) Gecko/20100101 Firefox/91.0\r\n"]];
    $context = stream_context_create($options);
    $html = @file_get_contents($detailUrl, false, $context);
    
    if ($html === FALSE) {
        return 'Unknown';
    }

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);

    // Extract category from breadcrumb
    $categoryNode = $xpath->query("//ul[@class='breadcrumb']/li[3]/a")->item(0);
    return $categoryNode ? trim($categoryNode->nodeValue) : 'Unknown';
}

// Main crawling function that handles both specific and generic websites
function crawlWebsite($url) {
    $options = ['http' => ['header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:91.0) Gecko/20100101 Firefox/91.0\r\n"]];
    $context = stream_context_create($options);
    $html = @file_get_contents($url, false, $context);

    if ($html === FALSE) {
        return ['error' => 'Failed to retrieve data from ' . $url];
    }

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);

    // If the website is books.toscrape.com, use the specific logic
    if (strpos($url, 'books.toscrape.com') !== false) {
        return scrapeBooksToScrape($xpath);
    }

    // Check if the website is a known one, otherwise use the generic scraper
    return scrapeGenericEcommerceSite($xpath);
}

// Scraper for books.toscrape.com
function scrapeBooksToScrape($xpath) {
    $items = [];
    $bookNodes = $xpath->query("//article[@class='product_pod']");
    
    foreach ($bookNodes as $node) {
        $titleNode = $xpath->query(".//h3/a", $node)->item(0);
        $priceNode = $xpath->query(".//p[@class='price_color']", $node)->item(0);
        $ratingNode = $xpath->query(".//p[contains(@class, 'star-rating')]", $node)->item(0);
        $detailHref = $titleNode ? $titleNode->getAttribute('href') : null;

        $title = $titleNode ? trim($titleNode->getAttribute('title')) : 'N/A';
        $price = $priceNode ? trim($priceNode->nodeValue) : 'N/A';
        $rating = $ratingNode ? explode(" ", $ratingNode->getAttribute('class'))[1] : 'N/A';
        $category = fetchBookCategory($detailHref);

        $items[] = [
            'title' => $title,
            'price' => $price,
            'rating' => $rating,
            'category' => $category
        ];
    }

    return ['url' => 'books.toscrape.com', 'items' => $items];
}

// Generic scraper to handle unknown or complex e-commerce sites
function scrapeGenericEcommerceSite($xpath) {
    $items = [];

    // Look for common product containers
    $productNodes = $xpath->query("//div[contains(@class, 'product') or contains(@class, 'item') or contains(@class, 'product-card')]");
    
    foreach ($productNodes as $node) {
        // Common patterns for product details
        $titleNode = $xpath->query(".//h2 | .//h3 | .//a[contains(@class, 'title') or contains(@class, 'name')]", $node)->item(0);
        $priceNode = $xpath->query(".//span[contains(@class, 'price') or contains(@class, 'amount')]", $node)->item(0);
        $categoryNode = $xpath->query(".//a[contains(@class, 'category') or contains(@class, 'breadcrumb')]", $node)->item(0);

        $title = $titleNode ? trim($titleNode->nodeValue) : 'N/A';
        $price = $priceNode ? trim($priceNode->nodeValue) : 'N/A';
        $category = $categoryNode ? trim($categoryNode->nodeValue) : 'Unknown';

        $items[] = [
            'title' => $title,
            'price' => $price,
            'category' => $category
        ];
    }

    return ['url' => 'generic-site', 'items' => $items];
}
