<?php
// crawler.php

function fetchBookCategory($detailHref) {
    // Veenduge, et URL on absoluutne
    $baseUrl = 'http://books.toscrape.com/';
    $detailUrl = $baseUrl . $detailHref;

    // Määrake kohandatud päised User-Agentiga
    $options = [
        'http' => [
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:91.0) Gecko/20100101 Firefox/91.0\r\n"
        ]
    ];
    $context = stream_context_create($options);
    $html = @file_get_contents($detailUrl, false, $context);

    // Handle failure to load content
    if ($html === FALSE) {
        return 'Unknown';
    }

    // DOM parser
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);

    // Leia kategooria breadcrumbsist (teine li element)
    $categoryNode = $xpath->query("//ul[@class='breadcrumb']/li[3]/a")->item(0);
    if ($categoryNode) {
        return trim($categoryNode->nodeValue);
    } else {
        return 'Unknown';
    }
}

// Jätkake olemasoleva crawlWebsite funktsiooniga
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

    // If the site structure is not recognized, provide a graceful error message
    if (strpos($url, 'books.toscrape.com') === false && strpos($url, 'imdb.com') === false && strpos($url, 'amazon.com') === false) {
        return ['error' => 'Website structure not recognized. Cannot crawl this site.'];
    }

    // Initialize items array
    $items = [];

    // Check for Books to Scrape website structure
    if (strpos($url, 'books.toscrape.com') !== false) {
        $bookNodes = $xpath->query("//article[@class='product_pod']");
        foreach ($bookNodes as $node) {
            // Access the individual nodes correctly
            $titleNode = $xpath->query(".//h3/a", $node)->item(0);
            $priceNode = $xpath->query(".//p[@class='price_color']", $node)->item(0);
            $ratingNode = $xpath->query(".//p[contains(@class, 'star-rating')]", $node)->item(0);
            $detailHref = $titleNode ? $titleNode->getAttribute('href') : null;

            // Title
            $title = $titleNode ? trim($titleNode->getAttribute('title')) : 'N/A';

            // Price
            $price = $priceNode ? trim($priceNode->nodeValue) : 'N/A';

            // Rating: Extract class like 'star-rating Four'
            if ($ratingNode) {
                $ratingClasses = explode(" ", $ratingNode->getAttribute('class'));
                $rating = isset($ratingClasses[1]) ? $ratingClasses[1] : 'N/A';
            } else {
                $rating = 'N/A';
            }

            // Fetch category by visiting the detail page
            $category = fetchBookCategory($detailHref);

            // Add the scraped data to the items array
            $items[] = [
                'title' => $title,
                'price' => $price,
                'rating' => $rating,
                'category' => $category
            ];
        }

        // Pagination: Check if there's a next page and crawl it recursively
        $nextPage = $xpath->query("//li[@class='next']/a")->item(0);
        if ($nextPage) {
            $nextHref = $nextPage->getAttribute('href');
            $base = rtrim($url, '/');
            $nextUrl = dirname($base) . '/' . $nextHref;
            $nextData = crawlWebsite($nextUrl);
            if ($nextData && isset($nextData['items'])) {
                $items = array_merge($items, $nextData['items']);
            }
        }
    }
    else {
        return ['error' => 'Website structure not recognized'];
    }

    return [
        'url' => $url,
        'items' => $items
    ];
}
?>
