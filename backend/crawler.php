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

function scrapeGenericEcommerceSite($xpath) {
    $items = [];

    // 1. First, try to detect if there are table rows (for sites like IMDb)
    $rows = $xpath->query("//table//tr");
    if ($rows->length > 0) {
        // Loop through each table row and extract meaningful data
        foreach ($rows as $row) {
            $titleNode = $xpath->query(".//td/a | .//th/a | .//td[contains(@class, 'titleColumn')]/a", $row)->item(0);
            $ratingNode = $xpath->query(".//td[contains(@class, 'ratingColumn')]//strong", $row)->item(0);
            $yearNode = $xpath->query(".//span[contains(@class, 'secondaryInfo')]", $row)->item(0);

            // Extract values from the nodes or fallback to N/A
            $title = $titleNode ? trim($titleNode->nodeValue) : 'N/A';
            $rating = $ratingNode ? trim($ratingNode->nodeValue) : 'N/A';
            $year = $yearNode ? trim($yearNode->nodeValue, '()') : 'N/A';

            // Skip header rows that might not contain movie data
            if ($title !== 'N/A') {
                $items[] = [
                    'title' => $title,
                    'rating' => $rating,
                    'year' => $year
                ];
            }
        }
        return ['url' => 'generic-site', 'items' => $items];
    }

    // 2. If no table rows, try common product containers (like div, li, or span elements
    $productNodes = $xpath->query(
        "//div[contains(@class, 'product') or 
               contains(@class, 'item') or 
               contains(@class, 'product-card') or 
               contains(@class, 's-result-item') or 
               contains(@class, 'product-box') or 
               contains(@class, 'grid-item') or 
               contains(@class, 'list-item') or 
               contains(@class, 'product-list-item') or 
               contains(@class, 'result-item')]"
    );

    // If no product nodes are found, attempt a more generic div selection
    if ($productNodes->length === 0) {
        $productNodes = $xpath->query("//div");
    }

    foreach ($productNodes as $node) {
        // Title Extraction: Try multiple approaches including Open Graph and meta tags
        $titleNode = $xpath->query(".//h1 | .//h2 | .//h3 | .//a[contains(@class, 'title') or 
                        contains(@class, 'name') or 
                        contains(@class, 'product-title') or 
                        contains(@class, 'heading') or 
                        contains(@class, 'product-heading') or 
                        contains(@class, 'product-link')]", $node)->item(0);
        if (!$titleNode) {
            // Fallback: Try meta tags or Open Graph titles
            $titleNode = $xpath->query("//meta[@property='og:title']/@content | //meta[@name='title']/@content")->item(0);
        }

        // Price Extraction: Try broader price patterns including discounts
        $priceNode = $xpath->query(
            ".//span[contains(@class, 'price') or 
                     contains(@class, 'a-price') or 
                     contains(@class, 'amount') or 
                     contains(@class, 'price-current') or 
                     contains(@class, 'current-price') or 
                     contains(@class, 'discount-price') or
                     contains(@class, 'a-offscreen') or 
                     contains(@class, 'price-value')]",
            $node
        )->item(0);
        if (!$priceNode) {
            // Fallback: Check for nested price containers (e.g., original and discounted prices)
            $priceNode = $xpath->query(".//span[contains(@class, 'price-inner') or contains(@class, 'price-discount')]", $node)->item(0);
        }

        // Extract discounted price, if available
        $discountPriceNode = $xpath->query(".//span[contains(@class, 'discount-price')]", $node)->item(0);
        $discountPrice = $discountPriceNode ? trim($discountPriceNode->nodeValue) : 'N/A';

        // Categories/Breadcrumbs: Try to extract from category links or breadcrumbs
        $categoryNode = $xpath->query(".//a[contains(@class, 'category') or 
                                              contains(@class, 'breadcrumb') or 
                                              contains(@class, 'nav')]", $node)->item(0);

        // Ratings: Extract rating information (stars or text-based)
        $ratingNode = $xpath->query(".//span[contains(@class, 'rating') or 
                                             contains(@class, 'stars') or 
                                             contains(@class, 'star-rating') or 
                                             contains(@class, 'ratings')]", $node)->item(0);

        // Image Extraction: Try to extract product image URLs
        $imageNode = $xpath->query(".//img[contains(@class, 'product-image') or 
                                            contains(@class, 'item-image') or 
                                            contains(@class, 'product-card-image')]/@src", $node)->item(0);

        // Pagination Handling (to support multi-page scraping)
        $nextPageNode = $xpath->query("//a[contains(@class, 'next') or contains(text(), 'Next')]/@href")->item(0);
        $nextPageUrl = $nextPageNode ? trim($nextPageNode->nodeValue) : null;

        // Extract text values or default to 'N/A'
        $title = $titleNode ? trim($titleNode->nodeValue) : 'N/A';
        $price = $priceNode ? trim($priceNode->nodeValue) : 'N/A';
        $category = $categoryNode ? trim($categoryNode->nodeValue) : 'Unknown';
        $rating = $ratingNode ? trim($ratingNode->nodeValue) : 'N/A';
        $image = $imageNode ? trim($imageNode->nodeValue) : 'N/A';

        // Add extracted data to the items array
        $items[] = [
            'title' => $title,
            'price' => $price,
            'discount_price' => $discountPrice,
            'category' => $category,
            'rating' => $rating,
            'image' => $image,
            'next_page' => $nextPageUrl
        ];

        // Optional: If you want to crawl the next page, trigger another request here
        // You would need a recursive mechanism to handle multi-page scraping
    }

    return ['url' => 'generic-site', 'items' => $items];
}

function enhancedEcommerceSite($xpath) {
    $items = [];

    // Try to get even deeper and more comprehensive matches using more complex queries
    $productNodes = $xpath->query(
        "//li[contains(@class, 'product-item') or 
              contains(@class, 'result-item') or 
              contains(@class, 'product-box') or 
              contains(@class, 'product-tile') or 
              contains(@class, 'product-list') or 
              contains(@class, 'grid-item')]"
    );

    foreach ($productNodes as $node) {
        // Try all possible patterns for title and description
        $titleNode = $xpath->query(".//h1 | .//h2 | .//h3 | .//a[contains(@class, 'title') or 
                        contains(@class, 'name') or 
                        contains(@class, 'product-title') or 
                        contains(@class, 'heading') or 
                        contains(@class, 'product-heading')]", $node)->item(0);

        // For prices, try multiple approaches, including any dynamic content placeholders
        $priceNode = $xpath->query(
            ".//span[contains(@class, 'price') or 
                     contains(@class, 'a-price') or 
                     contains(@class, 'amount') or 
                     contains(@class, 'price-current') or 
                     contains(@class, 'current-price') or 
                     contains(@class, 'discount-price')]", 
            $node
        )->item(0);

        // Attempt to capture category from the breadcrumb, sidebar, or nav tags
        $categoryNode = $xpath->query(".//a[contains(@class, 'breadcrumb') or 
                                          contains(@class, 'category') or 
                                          contains(@class, 'nav')]", $node)->item(0);

        // Capture ratings, either in text or as stars
        $ratingNode = $xpath->query(".//span[contains(@class, 'rating') or 
                                             contains(@class, 'stars') or 
                                             contains(@class, 'star-rating') or 
                                             contains(@class, 'ratings')]", $node)->item(0);

        // Extract values and apply fallback where necessary
        $title = $titleNode ? trim($titleNode->nodeValue) : 'N/A';
        $price = $priceNode ? trim($priceNode->nodeValue) : 'N/A';
        $category = $categoryNode ? trim($categoryNode->nodeValue) : 'Unknown';
        $rating = $ratingNode ? trim($ratingNode->nodeValue) : 'N/A';

        $items[] = [
            'title' => $title,
            'price' => $price,
            'category' => $category,
            'rating' => $rating,
        ];
    }

    return ['url' => 'enhanced-site', 'items' => $items];
}
