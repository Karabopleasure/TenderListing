<?php
// Ensure script has no execution time limit
set_time_limit(0);

// File to save tenders
$outputFile = __DIR__ . '/tender.json';

// Load existing tenders from JSON file
function loadExistingTenders($file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        return json_decode($content, true);
    }
    return [];
}

// Save tenders to JSON file
function saveTenders($file, $tenders) {
    $json = json_encode($tenders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents($file, $json);
    echo "Tenders saved to {$file}\n";
}

// Function to scrape tenders
function scrapeTenders($outputFile) {
    $baseUrl = 'https://www.etenders.gov.za/Home/PaginatedTenderOpportunities';
    $headers = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        'Referer: https://www.etenders.gov.za/',
        'X-Requested-With: XMLHttpRequest',
        'Accept: application/json, text/javascript, */*; q=0.01',
    ];

    // Static parameters
    $staticParams = [
        'draw' => 1,
        'columns[0][data]' => '',
        'columns[0][name]' => '',
        'columns[0][searchable]' => 'true',
        'columns[0][orderable]' => 'false',
        'columns[0][search][value]' => '',
        'columns[0][search][regex]' => 'false',

        'columns[1][data]' => 'category',
        'columns[1][name]' => '',
        'columns[1][searchable]' => 'true',
        'columns[1][orderable]' => 'true',
        'columns[1][search][value]' => '',
        'columns[1][search][regex]' => 'false',

        'columns[2][data]' => 'description',
        'columns[2][name]' => '',
        'columns[2][searchable]' => 'true',
        'columns[2][orderable]' => 'false',
        'columns[2][search][value]' => '',
        'columns[2][search][regex]' => 'false',

        'columns[3][data]' => 'eSubmission',
        'columns[3][name]' => '',
        'columns[3][searchable]' => 'true',
        'columns[3][orderable]' => 'true',
        'columns[3][search][value]' => '',
        'columns[3][search][regex]' => 'false',

        'columns[4][data]' => 'date_Published',
        'columns[4][name]' => '',
        'columns[4][searchable]' => 'true',
        'columns[4][orderable]' => 'true',
        'columns[4][search][value]' => '',
        'columns[4][search][regex]' => 'false',

        'columns[5][data]' => 'closing_Date',
        'columns[5][name]' => '',
        'columns[5][searchable]' => 'true',
        'columns[5][orderable]' => 'true',
        'columns[5][search][value]' => '',
        'columns[5][search][regex]' => 'false',

        'columns[6][data]' => 'actions',
        'columns[6][name]' => '',
        'columns[6][searchable]' => 'true',
        'columns[6][orderable]' => 'true',
        'columns[6][search][value]' => '',
        'columns[6][search][regex]' => 'false',

        'order[0][column]' => 2,
        'order[0][dir]' => 'desc',
        'search' => '{"value":"","regex":false}',
        'status' => 1,
    ];

    $start = 0;
    $length = 50;
    $totalRecords = 0;
    $allTenders = [];

    do {
        $params = $staticParams;
        $params['draw'] = ($start / $length) + 1;
        $params['start'] = $start;
        $params['length'] = $length;
        $params['_'] = time();

        $query = http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseUrl . '?' . $query);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        if ($response === false) {
            echo "Error: " . curl_error($ch) . "\n";
            curl_close($ch);
            break;
        }

        $data = json_decode($response, true);
        curl_close($ch);

        if (!$data || !isset($data['data'])) {
            echo "Unexpected response format:\n";
            print_r($data);
            break;
        }

        $totalRecords = $data['recordsTotal'];
        $allTenders = array_merge($allTenders, $data['data']);

        echo "Fetched tenders " . ($start + 1) . " to " . min($start + $length, $totalRecords) . " of {$totalRecords}\n";

        $start += $length;

    } while ($start < $totalRecords);

    echo "Total tenders scraped: " . count($allTenders) . "\n";

    // Load existing tenders
    $existingTenders = loadExistingTenders($outputFile);
    $existingIDs = [];
    foreach ($existingTenders as $t) {
        $existingIDs[] = $t['tender_No'] ?? $t['id'];
    }

    // Filter new tenders
    $newTenders = array_filter($allTenders, function($t) use ($existingIDs) {
        return !in_array($t['tender_No'] ?? $t['id'], $existingIDs);
    });

    if (count($newTenders) > 0) {
        echo "Found " . count($newTenders) . " new tenders.\n";
        $updatedTenders = array_merge($newTenders, $existingTenders);
        saveTenders($outputFile, $updatedTenders);
    } else {
        echo "No new tenders found.\n";
    }

    // Optionally output JSON
    echo "<pre>" . json_encode($allTenders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
}

// Run scraper
scrapeTenders($outputFile);

// Optionally set up auto-refresh in browser (every 5 min)
echo '<script>setTimeout(function(){window.location.reload();}, 300000);</script>';
?>
