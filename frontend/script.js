// frontend/script.js

// API endpoint URL
const API_URL = 'http://localhost/web-crawler-dashboard/backend/api.php';

// Elements
const crawlButton = document.getElementById('crawl-button');
const searchUrlInput = document.getElementById('search-url');
const statusDiv = document.getElementById('status');
const categoriesBody = document.getElementById('categories-body');

// Chart instances
let categoryChartInstance = null;
let priceChartInstance = null;

// Event listener for the crawl button
crawlButton.addEventListener('click', () => {
    const urlInput = searchUrlInput.value.trim();
    if (!urlInput) {
        alert('Palun sisesta e-poe URL.');
        return;
    }

    // Add the new URL to the backend
    addNewUrl(urlInput);
});

/**
 * Add a new URL to the backend via POST request.
 *
 * @param {string} url The URL to add.
 */
function addNewUrl(url) {
    // Show status
    statusDiv.innerText = 'Lisatakse uus URL...';

    fetch(API_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer S3cure8008Stere' // Replace with your API key
        },
        body: JSON.stringify({ url: url })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            // Kasutasime tagurpidi komasid (``) template literal'ide jaoks
            statusDiv.innerText = `Viga: ${data.error}`;
        } else {
            statusDiv.innerText = 'Uus URL lisatud. Alustan kaapimist...';
            // Clear input field
            searchUrlInput.value = '';
            // Start crawling
            startCrawling();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        statusDiv.innerText = 'Viga URL lisamisel.';
    });
}

/**
 * Start crawling by sending a GET request to the backend.
 */
function startCrawling() {
    // Show crawling status
    statusDiv.innerText = 'Kaapimine käib...';

    fetch(API_URL, {
        method: 'GET',
        headers: {
            'Authorization': 'Bearer S3cure8008Stere' // Replace with your API key
        }
    })
    .then(response => response.json())
    .then(data => {
        if (Array.isArray(data)) {
            // Kontrollige andmetes esinevaid vigu
            const errors = data.filter(site => site.error).map(site => site.error);
            if (errors.length > 0) {
                // Kasutasime tagurpidi komasid (``) template literal'ide jaoks
                statusDiv.innerText = `Viga kaapimisel: ${errors.join('; ')}`;
            } else {
                statusDiv.innerText = 'Kaapimine lõpetatud.';
                visualizeData(data);
            }
        } else if (data.error) {
            // Kui kogu vastusel on viga
            // Kasutasime tagurpidi komasid (``) template literal'ide jaoks
            statusDiv.innerText = `Viga kaapimisel: ${data.error}`;
        } else {
            statusDiv.innerText = 'Viga kaapimisel.';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        statusDiv.innerText = 'Viga kaapimisel.';
    });
}

/**
 * Visualize the crawled data by updating charts and tables.
 *
 * @param {Array} data The data returned from the backend.
 */
function visualizeData(data) {
    // Initialize counts
    const categoryCounts = {};
    const priceRanges = {
        '0-50': 0,
        '51-100': 0,
        '101-200': 0,
        '201-500': 0,
        '500+': 0
    };

    // Process data
    data.forEach(site => {
        if (site.items && Array.isArray(site.items)) {
            site.items.forEach(product => {
                // Count categories
                const category = product.category || 'Unknown';
                if (categoryCounts[category]) {
                    categoryCounts[category]++;
                } else {
                    categoryCounts[category] = 1;
                }

                // Count price ranges
                const price = parseFloat(product.price.replace(/[^0-9.]/g, ''));
                if (!isNaN(price)) {
                    if (price <= 50) priceRanges['0-50']++;
                    else if (price <= 100) priceRanges['51-100']++;
                    else if (price <= 200) priceRanges['101-200']++;
                    else if (price <= 500) priceRanges['201-500']++;
                    else priceRanges['500+']++;
                }
            });
        }
    });

    // Sort categories by popularity
    const sortedCategories = Object.entries(categoryCounts).sort((a, b) => b[1] - a[1]);

    // Populate the categories table
    categoriesBody.innerHTML = '';
    sortedCategories.forEach(([category, count]) => {
        const row = document.createElement('tr');
        const catCell = document.createElement('td');
        catCell.innerText = category;
        const countCell = document.createElement('td');
        countCell.innerText = count;
        row.appendChild(catCell);
        row.appendChild(countCell);
        categoriesBody.appendChild(row);
    });

    // Generate or update Category Distribution Chart (Pie Chart)
    const categoryLabels = sortedCategories.map(item => item[0]);
    const categoryData = sortedCategories.map(item => item[1]);

    if (categoryChartInstance) {
        categoryChartInstance.destroy();
    }

    const ctxCategory = document.getElementById('categoryChart').getContext('2d');
    categoryChartInstance = new Chart(ctxCategory, {
        type: 'pie',
        data: {
            labels: categoryLabels,
            datasets: [{
                data: categoryData,
                backgroundColor: generateColors(categoryLabels.length)
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                title: {
                    display: true,
                    text: 'Toodete Kategooriate Jaotus'
                }
            }
        }
    });

    // Generate or update Price Range Distribution Chart (Bar Chart)
    const priceLabels = Object.keys(priceRanges);
    const priceData = Object.values(priceRanges);

    if (priceChartInstance) {
        priceChartInstance.destroy();
    }

    const ctxPrice = document.getElementById('priceChart').getContext('2d');
    priceChartInstance = new Chart(ctxPrice, {
        type: 'bar',
        data: {
            labels: priceLabels,
            datasets: [{
                label: 'Toodete arv',
                data: priceData,
                backgroundColor: 'rgba(75, 192, 192, 0.6)'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                title: {
                    display: true,
                    text: 'Hinnaklassi Jaotus'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    precision: 0
                }
            }
        }
    });

    // Additional charts can be added here
}

/**
 * Generate an array of HSL color strings.
 *
 * @param {number} num The number of colors to generate.
 * @return {Array} An array of HSL color strings.
 */
function generateColors(num) {
    const colors = [];
    for(let i = 0; i < num; i++) {
        // Kasutasime tagurpidi komasid (``) template literal'ide jaoks
        colors.push(`hsl(${i * (360 / num)}, 70%, 50%)`);
    }
    return colors;
}
