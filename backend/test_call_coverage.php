<?php

require_once 'vendor/autoload.php';

use App\DTO\Schedule\ScheduleMetricsData;

// Symulacja danych metryk z nowym formatem callCoverage (bez pola formatted)
$callCoverage = [
    '2025-09-01' => [
        'expectedCalls' => 100,
        'agentCapacity' => 80,
        'coverage' => 125.0
    ],
    '2025-09-02' => [
        'expectedCalls' => 120,
        'agentCapacity' => 120,
        'coverage' => 100.0
    ],
    '2025-09-03' => [
        'expectedCalls' => 90,
        'agentCapacity' => 100,
        'coverage' => 90.0
    ],
    '2025-09-04' => [
        'expectedCalls' => 150,
        'agentCapacity' => 120,
        'coverage' => 125.0
    ],
    '2025-09-05' => [
        'expectedCalls' => 80,
        'agentCapacity' => 100,
        'coverage' => 80.0
    ]
];

$metrics = new ScheduleMetricsData(
    totalHours: 60,
    agentCount: 2,
    averageHoursPerAgent: 30,
    maxHoursPerAgent: 40,
    minHoursPerAgent: 20,
    callCoverage: $callCoverage
);

echo "=== Nowe metryki pokrycia rozmów ===\n\n";

echo "Podstawowe informacje:\n";
echo "- Łączne godziny: {$metrics->totalHours}\n";
echo "- Liczba agentów: {$metrics->agentCount}\n";
echo "- Średnie godziny na agenta: {$metrics->averageHoursPerAgent}\n";
echo "- Maksymalne godziny na agenta: {$metrics->maxHoursPerAgent}\n";
echo "- Minimalne godziny na agenta: {$metrics->minHoursPerAgent}\n\n";

echo "Pokrycie rozmów dzienne:\n";
foreach ($metrics->callCoverage as $day => $coverage) {
    $status = match(true) {
        $coverage['coverage'] >= 100 => '✓ Wystarczające',
        $coverage['coverage'] >= 80 => '⚠️  Prawie wystarczające',
        $coverage['coverage'] >= 60 => '⚠️  Niewystarczające',
        default => '✗ Krytycznie niewystarczające'
    };
    
    // Frontend formatuje to samo
    $formatted = "{$coverage['expectedCalls']}/{$coverage['agentCapacity']} ({$coverage['coverage']}%)";
    
    echo "- $day: $formatted - $status\n";
}

echo "\nAnaliza:\n";
$totalDays = count($metrics->callCoverage);
$sufficientDays = count(array_filter($metrics->callCoverage, fn($c) => $c['coverage'] >= 100));
$insufficientDays = count(array_filter($metrics->callCoverage, fn($c) => $c['coverage'] < 100));

echo "- Dni z wystarczającym pokryciem: $sufficientDays/$totalDays (" . round(($sufficientDays/$totalDays)*100, 1) . "%)\n";
echo "- Dni z niewystarczającym pokryciem: $insufficientDays/$totalDays (" . round(($insufficientDays/$totalDays)*100, 1) . "%)\n";

$avgCoverage = array_sum(array_column($metrics->callCoverage, 'coverage')) / $totalDays;
echo "- Średnie pokrycie: " . round($avgCoverage, 1) . "%\n";

echo "\nRekomendacje:\n";
if ($avgCoverage < 80) {
    echo "- Rozważ zwiększenie liczby agentów lub godzin pracy\n";
} elseif ($avgCoverage > 120) {
    echo "- Rozważ zmniejszenie liczby agentów (nadmierne pokrycie)\n";
} else {
    echo "- Harmonogram jest dobrze zbalansowany\n";
}
