<?php
// backend/crawler.php

/**
 * Fetches the category of a book from its detail page or the main page.
 *
 * @param string|null $detailHref Relative URL to the book's detail page.
 * @param string|null $baseUrl Base URL of the website.
 * @param DOMXPath|null $xpath DOMXPath instance for the main page.
 * @return string Category name or 'Unknown' if not found.
 */
function fetchBookCategory($detailHref = null, $baseUrl = null, $xpath = null) {
    error_log("Entering fetchBookCategory with detailHref: " . var_export($detailHref, true));
    
    // If no detailHref provided, fallback to checking the main page's h1 element
    if ($detailHref === null && $xpath !== null) {
        error_log("No detailHref provided. Extracting category from main page.");
        return extractCategoryFromXPath($xpath);
    }

    // If a detailHref is provided, fetch category from the detail page
    if ($baseUrl === null) {
        error_log("Base URL is null. Cannot construct detail URL.");
        return 'Unknown';
    }

    $detailUrl = rtrim($baseUrl, '/') . '/' . ltrim($detailHref, '/');
    error_log("Constructed detail URL: " . $detailUrl);

    $options = ['http' => ['header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:91.0) Gecko/20100101 Firefox/91.0\r\n"]];
    $context = stream_context_create($options);
    $html = @file_get_contents($detailUrl, false, $context);

    if ($html === FALSE) {
        error_log("Failed to retrieve detail page: " . $detailUrl);
        // If we can't retrieve the detail page, fallback to the current page's h1
        return extractCategoryFromXPath($xpath);
    }

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();
    $xpathDetail = new DOMXPath($dom);

    // Extract category from breadcrumb, handling both <a> and <span> tags
    $breadcrumbCategory = $xpathDetail->query("//ul[contains(@class, 'breadcrumb')]/li[3]/a | //ul[contains(@class, 'breadcrumb')]/li[3]/span")->item(0);
    if ($breadcrumbCategory) {
        $category = trim($breadcrumbCategory->nodeValue);
        error_log("Extracted category from breadcrumb: " . $category);
        return $category;
    }

    // Extract category from meta or nav tags as fallback
    $metaCategory = $xpathDetail->query("//meta[@property='category' or @name='category']/@content")->item(0);
    $navCategory = $xpathDetail->query("//nav[contains(@class, 'breadcrumb') or contains(@class, 'nav')]//a")->item(0);

    if ($metaCategory) {
        $category = trim($metaCategory->nodeValue);
        error_log("Extracted category from meta: " . $category);
        return $category;
    } elseif ($navCategory) {
        $category = trim($navCategory->nodeValue);
        error_log("Extracted category from nav: " . $category);
        return $category;
    }

    // Final fallback: try extracting from an h1 element
    error_log("Falling back to extractCategoryFromXPath.");
    return extractCategoryFromXPath($xpathDetail);
}
/**
 * Extracts the category from the provided DOMXPath by looking for an h1 tag.
 *
 * @param DOMXPath $xpath DOMXPath instance.
 * @return string Category name or 'Unknown' if not found.
 */
function extractCategoryFromXPath($xpath) {
    // Extract from an h1 tag
    $h1Category = $xpath->query("//h1")->item(0);

    // Check if $h1Category is captured
    if ($h1Category) {
        // Log the captured h1 value
        error_log("Captured H1 Category: " . trim($h1Category->nodeValue));
        // Return the trimmed value
        return trim($h1Category->nodeValue);
    } else {
        // Log the absence of an H1 tag
        error_log("No H1 tag found.");
        return 'Unknown';
    }
}

/**
 * Main crawling function that handles both specific and generic websites.
 *
 * @param string $url The URL to crawl.
 * @return array Crawled data or error message.
 */
function crawlWebsite($url) {
    error_log("Starting crawlWebsite for URL: " . $url);

    $options = ['http' => ['header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:91.0) Gecko/20100101 Firefox/91.0\r\n"]];
    $context = stream_context_create($options);
    $html = @file_get_contents($url, false, $context);

    if ($html === FALSE) {
        error_log("Failed to retrieve data from " . $url);
        return ['error' => 'Failed to retrieve data from ' . $url];
    }

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);

    // If the website is books.toscrape.com, use the specific logic
    if (strpos($url, 'books.toscrape.com') !== false) {
        error_log("Detected books.toscrape.com. Using scrapeBooksToScrape.");
        $result = scrapeBooksToScrape($xpath);
        if (isset($result['items']) && !empty($result['items'])) {
            error_log("Successfully scraped books.toscrape.com.");
            return $result;
        } else {
            error_log("No items found in books.toscrape.com.");
            return ['error' => 'No items found in ' . $url];
        }
    }

    // Check if the website is a known one, otherwise use the generic scraper
    $result = scrapeGenericEcommerceSite($xpath, $url);
    if (isset($result['items']) && !empty($result['items'])) {
        error_log("Successfully scraped generic e-commerce site: " . $url);
        return $result;
    } else {
        error_log("No items found in generic e-commerce site: " . $url);
        return ['error' => 'No items found in ' . $url];
    }
}

// Scraper for books.toscrape.com
function scrapeBooksToScrape($xpath) {
    $items = [];
    $bookNodes = $xpath->query("//article[@class='product_pod']");
    $baseUrl = 'https://books.toscrape.com/'; // Define the base URL

    foreach ($bookNodes as $node) {
        $titleNode = $xpath->query(".//h3/a", $node)->item(0);
        $priceNode = $xpath->query(".//p[@class='price_color']", $node)->item(0);
        $ratingNode = $xpath->query(".//p[contains(@class, 'star-rating')]", $node)->item(0);
        $detailHref = $titleNode ? $titleNode->getAttribute('href') : null;

        $title = $titleNode ? trim($titleNode->getAttribute('title')) : 'N/A';
        $price = $priceNode ? trim($priceNode->nodeValue) : 'N/A';
        $rating = $ratingNode ? explode(" ", $ratingNode->getAttribute('class'))[1] : 'N/A';
        $category = fetchBookCategory($detailHref, $baseUrl, $xpath); // Pass baseUrl and xpath

        $items[] = [
            'title' => $title,
            'price' => $price,
            'rating' => $rating,
            'category' => $category
        ];
    }

    return ['url' => 'books.toscrape.com', 'items' => $items];
}

// Generic e-commerce site scraper
function scrapeGenericEcommerceSite($xpath, $url) {
    $items = [];
    $baseUrl = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST);

    // XPath queries for product containers across multiple structures
    $commonContainers = [
        "//div[contains(@class, 'catalogue-product-wrapper')]",   // Structure from Arvutitark
        "//div[contains(@class, 'product-list-item')]",           // Common for other e-commerce sites
        "//div[contains(@class, 'grid-item')]",                   // Grid-based products
        "//div[contains(@class, 'product')]",                     // Generic product wrapper
        "//li[contains(@class, 'item')]"                          // Products as list items
    ];

    // Loop through the common containers until products are found
    foreach ($commonContainers as $query) {
        $productNodes = $xpath->query($query);
        if ($productNodes->length > 0) {
            break; // Stop once we find the correct product container
        }
    }

    foreach ($productNodes as $node) {
        // Fallback for title extraction
        $titleNode = $xpath->query(".//h4[@class='_name']/@title | .//h2 | .//h3 | .//span[contains(@class, 'product-title')] | .//meta[@property='og:title']/@content", $node)->item(0);
        $title = $titleNode ? trim($titleNode->nodeValue) : 'N/A';

        // Fallback for price extraction
        $priceNode = $xpath->query(".//div[contains(@class, 'catalogue-product-price')]//text() | .//span[contains(@class, 'price')] | .//div[contains(@class, 'price')]", $node)->item(0);
        $price = $priceNode ? trim($priceNode->nodeValue) : 'N/A';

        // Fallback for image extraction
        $imageNode = $xpath->query(".//img[contains(@class, 'image-wrapper')]/@src | .//img[contains(@class, 'product-image')]/@src | .//meta[@property='og:image']/@content", $node)->item(0);
        $image = $imageNode ? trim($imageNode->nodeValue) : 'N/A';

        // If the image URL is relative, convert it to absolute using the base URL
        if ($image !== 'N/A' && strpos($image, 'http') === false) {
            $image = $baseUrl . ltrim($image, '/');
        }

        // Add extracted data to the items array
        $items[] = [
            'title' => $title,
            'price' => $price,
            'image' => $image,
        ];
    }

    // Check for pagination: Find "Next" or similar navigation
    $nextPageNode = $xpath->query("//a[contains(@class, 'next')]/@href | //li[contains(@class, 'pagination-next')]/a/@href")->item(0);
    $nextPageUrl = $nextPageNode ? trim($nextPageNode->nodeValue) : null;

    return ['items' => $items, 'next_page' => $nextPageUrl];
}
