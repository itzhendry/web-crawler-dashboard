// script.js

document.getElementById('crawl-button').addEventListener('click', () => {
    const urlInput = document.getElementById('search-url').value.trim();
    if (!urlInput) {
        alert('Palun sisesta e-poe URL.');
        return;
    }

    // Näita kaapimise olekut
    document.getElementById('status').innerText = 'Kaapimine käib...';

    // API päring frontendist otse backendisse
    // Selleks vajame backendile uue endpointi, mis võtab URL-i ja lisab selle `urls.txt` faili
    // Selle lihtsustamiseks, eeldame, et kasutame juba olemasolevat `api.php` endpointi

    fetch(`http://localhost/web-crawler-dashboard/backend/api.php`, {
        method: 'GET',
        headers: {
            'Authorization': 'Bearer teie_tugev_api_võti' // Asenda oma API võtmega
        }
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('status').innerText = 'Kaapimine lõpetatud.';
        visualizeData(data);
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('status').innerText = 'Viga kaapimisel.';
    });
});

function visualizeData(data) {
    // Andmete töötlemine ja graafikute joonistamine
    const categoryCounts = {};
    data.forEach(site => {
        site.categories.forEach(category => {
            if (categoryCounts[category]) {
                categoryCounts[category]++;
            } else {
                categoryCounts[category] = 1;
            }
        });
    });

    // Sortime kategooriad populaarsuse järgi
    const sortedCategories = Object.entries(categoryCounts).sort((a, b) => b[1] - a[1]);

    // Kuvame tabeli
    const categoriesBody = document.getElementById('categories-body');
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

    // Toodete kategooriate graafik (ringgraafik)
    const ctxCategory = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctxCategory, {
        type: 'pie',
        data: {
            labels: Object.keys(categoryCounts),
            datasets: [{
                data: Object.values(categoryCounts),
                backgroundColor: generateColors(Object.keys(categoryCounts).length)
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

    // Hinnaklassi jaotuse graafik (tulpdiagramm)
    const priceRanges = {
        '0-50': 0,
        '51-100': 0,
        '101-200': 0,
        '201-500': 0,
        '500+': 0
    };

    data.forEach(site => {
        site.products.forEach(product => {
            const price = parseFloat(product.price.replace(/[^0-9.]/g, ''));
            if (price <= 50) priceRanges['0-50']++;
            else if (price <= 100) priceRanges['51-100']++;
            else if (price <= 200) priceRanges['101-200']++;
            else if (price <= 500) priceRanges['201-500']++;
            else priceRanges['500+']++;
        });
    });

    const ctxPrice = document.getElementById('priceChart').getContext('2d');
    new Chart(ctxPrice, {
        type: 'bar',
        data: {
            labels: Object.keys(priceRanges),
            datasets: [{
                label: 'Toodete arv',
                data: Object.values(priceRanges),
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
            }
        }
    });

    // Lisa teisi graafikuid siia (allahindlused, populaarsus jne)
}

function generateColors(num) {
    const colors = [];
    for(let i = 0; i < num; i++) {
        colors.push(`hsl(${i * (360 / num)}, 70%, 50%)`);
    }
    return colors;
}
