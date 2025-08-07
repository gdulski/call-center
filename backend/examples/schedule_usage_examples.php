<?php

/**
 * Przykłady użycia systemu harmonogramu call center
 * 
 * Ten plik zawiera przykłady jak używać API harmonogramu
 * do generowania i optymalizacji harmonogramów pracy agentów.
 */

// Przykład 1: Utworzenie i wygenerowanie harmonogramu
function exampleCreateAndGenerateSchedule()
{
    echo "=== Przykład 1: Utworzenie i wygenerowanie harmonogramu ===\n";
    
    // 1. Utwórz harmonogram
    $createData = [
        'queueTypeId' => 1, // ID typu kolejki (np. "Sprzedaż")
        'weekStartDate' => '2024-01-01' // Początek tygodnia
    ];
    
    $createResponse = makeApiRequest('POST', '/api/schedules', $createData);
    
    if ($createResponse['success']) {
        $scheduleId = $createResponse['data']['id'];
        echo "✓ Harmonogram utworzony z ID: $scheduleId\n";
        
        // 2. Wygeneruj przypisania
        $generateResponse = makeApiRequest('POST', "/api/schedules/$scheduleId/generate");
        
        if ($generateResponse['success']) {
            echo "✓ Harmonogram wygenerowany pomyślnie\n";
            echo "  - Liczba przypisań: " . $generateResponse['data']['assignmentsCount'] . "\n";
            echo "  - Łączne godziny: " . $generateResponse['data']['totalAssignedHours'] . "\n";
        } else {
            echo "✗ Błąd podczas generowania: " . $generateResponse['message'] . "\n";
        }
    } else {
        echo "✗ Błąd podczas tworzenia: " . $createResponse['message'] . "\n";
    }
    
    echo "\n";
}

// Przykład 2: Optymalizacja istniejącego harmonogramu
function exampleOptimizeExistingSchedule()
{
    echo "=== Przykład 2: Optymalizacja istniejącego harmonogramu ===\n";
    
    $scheduleId = 1; // ID istniejącego harmonogramu
    
    // 1. Sprawdź obecny stan
    $currentResponse = makeApiRequest('GET', "/api/schedules/$scheduleId");
    
    if ($currentResponse['success']) {
        $currentData = $currentResponse['data'];
        echo "Obecny stan harmonogramu:\n";
        echo "  - Status: " . $currentData['status'] . "\n";
        echo "  - Łączne godziny: " . $currentData['totalAssignedHours'] . "\n";
        echo "  - Liczba przypisań: " . count($currentData['assignments']) . "\n";
        
        // 2. Wykonaj optymalizację ILP
        $optimizeResponse = makeApiRequest('POST', "/api/schedules/$scheduleId/optimize-ilp");
        
        if ($optimizeResponse['success']) {
            echo "✓ Optymalizacja ILP zakończona pomyślnie\n";
            $optimizedData = $optimizeResponse['data'];
            echo "  - Nowa liczba przypisań: " . $optimizedData['assignmentsCount'] . "\n";
            echo "  - Nowe łączne godziny: " . $optimizedData['totalHours'] . "\n";
            
            // 3. Sprawdź metryki
            $metricsResponse = makeApiRequest('GET', "/api/schedules/$scheduleId/metrics");
            
            if ($metricsResponse['success']) {
                $metrics = $metricsResponse['data']['metrics'];
                $validation = $metricsResponse['data']['validation'];
                
                echo "Metryki po optymalizacji:\n";
                echo "  - Średnie godziny na agenta: " . round($metrics['averageHoursPerAgent'], 2) . "\n";
                echo "  - Maksymalne godziny na agenta: " . $metrics['maxHoursPerAgent'] . "\n";
                echo "  - Minimalne godziny na agenta: " . $metrics['minHoursPerAgent'] . "\n";
                echo "  - Walidacja: " . ($validation['isValid'] ? '✓ Poprawna' : '✗ Błędy') . "\n";
                
                if (!$validation['isValid']) {
                    echo "  - Liczba naruszeń: " . $validation['totalViolations'] . "\n";
                    foreach ($validation['violations'] as $violation) {
                        echo "    * $violation\n";
                    }
                }
            }
        } else {
            echo "✗ Błąd podczas optymalizacji: " . $optimizeResponse['message'] . "\n";
        }
    } else {
        echo "✗ Nie można pobrać harmonogramu: " . $currentResponse['message'] . "\n";
    }
    
    echo "\n";
}

// Przykład 3: Analiza harmonogramu z metrykami
function exampleAnalyzeScheduleMetrics()
{
    echo "=== Przykład 3: Analiza harmonogramu z metrykami ===\n";
    
    $scheduleId = 1;
    
    // Pobierz metryki
    $metricsResponse = makeApiRequest('GET', "/api/schedules/$scheduleId/metrics");
    
    if ($metricsResponse['success']) {
        $metrics = $metricsResponse['data']['metrics'];
        $validation = $metricsResponse['data']['validation'];
        
        echo "Analiza harmonogramu:\n";
        echo "=== Podstawowe metryki ===\n";
        echo "  - Łączne godziny: " . $metrics['totalHours'] . "\n";
        echo "  - Liczba agentów: " . $metrics['agentCount'] . "\n";
        echo "  - Średnie godziny na agenta: " . round($metrics['averageHoursPerAgent'], 2) . "\n";
        echo "  - Maksymalne godziny na agenta: " . $metrics['maxHoursPerAgent'] . "\n";
        echo "  - Minimalne godziny na agenta: " . $metrics['minHoursPerAgent'] . "\n";
        
        echo "\n=== Pokrycie godzinowe ===\n";
        $hourlyCoverage = $metrics['hourlyCoverage'];
        $totalHours = count($hourlyCoverage);
        $coveredHours = count(array_filter($hourlyCoverage, fn($hours) => $hours > 0));
        
        echo "  - Godziny z pokryciem: $coveredHours/$totalHours (" . round(($coveredHours/$totalHours)*100, 1) . "%)\n";
        
        // Znajdź godziny szczytu
        $peakHours = array_filter($hourlyCoverage, fn($hours) => $hours > 15);
        echo "  - Godziny szczytu (>15h): " . count($peakHours) . "\n";
        
        // Znajdź godziny z brakiem pokrycia
        $uncoveredHours = array_filter($hourlyCoverage, fn($hours) => $hours == 0);
        echo "  - Godziny bez pokrycia: " . count($uncoveredHours) . "\n";
        
        echo "\n=== Walidacja ===\n";
        echo "  - Status: " . ($validation['isValid'] ? '✓ Poprawny' : '✗ Błędy') . "\n";
        
        if (!$validation['isValid']) {
            echo "  - Naruszenia:\n";
            foreach ($validation['violations'] as $violation) {
                echo "    * $violation\n";
            }
        } else {
            echo "  - Brak naruszeń ograniczeń\n";
        }
        
        // Analiza rozkładu obciążenia
        echo "\n=== Analiza rozkładu obciążenia ===\n";
        $hoursDistribution = array_count_values($hourlyCoverage);
        ksort($hoursDistribution);
        
        echo "  - Rozkład godzin pracy:\n";
        foreach ($hoursDistribution as $hours => $count) {
            echo "    $hours godzin: $count przypisań\n";
        }
        
    } else {
        echo "✗ Błąd podczas pobierania metryk: " . $metricsResponse['message'] . "\n";
    }
    
    echo "\n";
}

// Przykład 4: Porównanie różnych algorytmów optymalizacji
function exampleCompareOptimizationAlgorithms()
{
    echo "=== Przykład 4: Porównanie algorytmów optymalizacji ===\n";
    
    $scheduleId = 1;
    
    // 1. Pobierz oryginalne metryki
    $originalMetrics = makeApiRequest('GET', "/api/schedules/$scheduleId/metrics");
    
    if (!$originalMetrics['success']) {
        echo "✗ Nie można pobrać oryginalnych metryk\n";
        return;
    }
    
    $originalData = $originalMetrics['data']['metrics'];
    echo "Oryginalne metryki:\n";
    echo "  - Łączne godziny: " . $originalData['totalHours'] . "\n";
    echo "  - Średnie godziny na agenta: " . round($originalData['averageHoursPerAgent'], 2) . "\n";
    echo "  - Liczba agentów: " . $originalData['agentCount'] . "\n";
    
    // 2. Optymalizacja heurystyczna
    echo "\n--- Optymalizacja heurystyczna ---\n";
    $heuristicResponse = makeApiRequest('POST', "/api/schedules/$scheduleId/optimize");
    
    if ($heuristicResponse['success']) {
        $heuristicData = $heuristicResponse['data'];
        echo "✓ Optymalizacja heurystyczna zakończona\n";
        echo "  - Nowe łączne godziny: " . $heuristicData['totalOptimizedHours'] . "\n";
        echo "  - Zmiana: " . round((($heuristicData['totalOptimizedHours'] - $originalData['totalHours']) / $originalData['totalHours']) * 100, 1) . "%\n";
        
        // Pobierz metryki po optymalizacji heurystycznej
        $heuristicMetrics = makeApiRequest('GET', "/api/schedules/$scheduleId/metrics");
        if ($heuristicMetrics['success']) {
            $heuristicMetricsData = $heuristicMetrics['data']['metrics'];
            echo "  - Nowe średnie godziny na agenta: " . round($heuristicMetricsData['averageHoursPerAgent'], 2) . "\n";
        }
    } else {
        echo "✗ Błąd podczas optymalizacji heurystycznej\n";
    }
    
    // 3. Optymalizacja ILP
    echo "\n--- Optymalizacja ILP ---\n";
    $ilpResponse = makeApiRequest('POST', "/api/schedules/$scheduleId/optimize-ilp");
    
    if ($ilpResponse['success']) {
        $ilpData = $ilpResponse['data'];
        echo "✓ Optymalizacja ILP zakończona\n";
        echo "  - Nowe łączne godziny: " . $ilpData['totalHours'] . "\n";
        echo "  - Zmiana: " . round((($ilpData['totalHours'] - $originalData['totalHours']) / $originalData['totalHours']) * 100, 1) . "%\n";
        
        // Pobierz metryki po optymalizacji ILP
        $ilpMetrics = makeApiRequest('GET', "/api/schedules/$scheduleId/metrics");
        if ($ilpMetrics['success']) {
            $ilpMetricsData = $ilpMetrics['data']['metrics'];
            echo "  - Nowe średnie godziny na agenta: " . round($ilpMetricsData['averageHoursPerAgent'], 2) . "\n";
            echo "  - Nowa liczba agentów: " . $ilpMetricsData['agentCount'] . "\n";
            
            // Porównanie z oryginalnymi
            $agentChange = $ilpMetricsData['agentCount'] - $originalData['agentCount'];
            echo "  - Zmiana liczby agentów: " . ($agentChange >= 0 ? '+' : '') . $agentChange . "\n";
        }
    } else {
        echo "✗ Błąd podczas optymalizacji ILP\n";
    }
    
    echo "\n";
}

// Przykład 5: Zarządzanie statusami harmonogramu
function exampleManageScheduleStatuses()
{
    echo "=== Przykład 5: Zarządzanie statusami harmonogramu ===\n";
    
    $scheduleId = 1;
    
    // Lista dostępnych statusów
    $statuses = ['draft', 'generated', 'published', 'finalized'];
    
    foreach ($statuses as $status) {
        echo "Ustawianie statusu: $status\n";
        
        $updateResponse = makeApiRequest('PATCH', "/api/schedules/$scheduleId/status", ['status' => $status]);
        
        if ($updateResponse['success']) {
            echo "  ✓ Status zmieniony na: " . $updateResponse['data']['status'] . "\n";
        } else {
            echo "  ✗ Błąd: " . $updateResponse['message'] . "\n";
        }
    }
    
    echo "\n";
}

// Przykład 6: Lista wszystkich harmonogramów
function exampleListAllSchedules()
{
    echo "=== Przykład 6: Lista wszystkich harmonogramów ===\n";
    
    $listResponse = makeApiRequest('GET', '/api/schedules');
    
    if ($listResponse['success']) {
        $schedules = $listResponse['data'];
        
        echo "Znaleziono " . count($schedules) . " harmonogramów:\n\n";
        
        foreach ($schedules as $schedule) {
            echo "ID: " . $schedule['id'] . "\n";
            echo "  - Typ kolejki: " . $schedule['queueType'] . "\n";
            echo "  - Tydzień: " . $schedule['weekStartDate'] . " - " . $schedule['weekEndDate'] . "\n";
            echo "  - Status: " . $schedule['status'] . "\n";
            echo "  - Łączne godziny: " . $schedule['totalAssignedHours'] . "\n";
            echo "  - Liczba przypisań: " . $schedule['assignmentsCount'] . "\n";
            echo "\n";
        }
    } else {
        echo "✗ Błąd podczas pobierania listy: " . $listResponse['message'] . "\n";
    }
    
    echo "\n";
}

// Funkcja pomocnicza do wykonywania żądań API
function makeApiRequest($method, $endpoint, $data = null)
{
    $baseUrl = 'http://localhost:8000';
    $url = $baseUrl . $endpoint;
    
    $ch = curl_init();
    
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ]
    ];
    
    if ($method === 'POST' || $method === 'PATCH') {
        $options[CURLOPT_CUSTOMREQUEST] = $method;
        if ($data) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }
    }
    
    curl_setopt_array($ch, $options);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response === false) {
        return ['success' => false, 'message' => 'Błąd połączenia z API'];
    }
    
    $decodedResponse = json_decode($response, true);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return $decodedResponse;
    } else {
        return [
            'success' => false,
            'message' => $decodedResponse['message'] ?? 'Błąd HTTP: ' . $httpCode
        ];
    }
}

// Funkcja główna - uruchomienie wszystkich przykładów
function runAllExamples()
{
    echo "=== PRZYKŁADY UŻYCIA SYSTEMU HARMONOGRAMU CALL CENTER ===\n\n";
    
    exampleCreateAndGenerateSchedule();
    exampleOptimizeExistingSchedule();
    exampleAnalyzeScheduleMetrics();
    exampleCompareOptimizationAlgorithms();
    exampleManageScheduleStatuses();
    exampleListAllSchedules();
    
    echo "=== KONIEC PRZYKŁADÓW ===\n";
}

// Uruchomienie przykładów (odkomentuj aby uruchomić)
// runAllExamples();

// Przykład użycia w kodzie PHP
function exampleUsageInCode()
{
    echo "=== Przykład użycia w kodzie PHP ===\n";
    
    // Symulacja danych wejściowych
    $queueTypeId = 1;
    $weekStartDate = '2024-01-01';
    
    // 1. Utworzenie harmonogramu
    $scheduleData = [
        'queueTypeId' => $queueTypeId,
        'weekStartDate' => $weekStartDate
    ];
    
    $schedule = createSchedule($scheduleData);
    
    if ($schedule) {
        echo "✓ Harmonogram utworzony z ID: " . $schedule['id'] . "\n";
        
        // 2. Generowanie przypisań
        $generationResult = generateSchedule($schedule['id']);
        
        if ($generationResult['success']) {
            echo "✓ Harmonogram wygenerowany\n";
            echo "  - Przypisania: " . $generationResult['assignmentsCount'] . "\n";
            echo "  - Godziny: " . $generationResult['totalAssignedHours'] . "\n";
            
            // 3. Optymalizacja ILP
            $optimizationResult = optimizeScheduleILP($schedule['id']);
            
            if ($optimizationResult['success']) {
                echo "✓ Optymalizacja ILP zakończona\n";
                echo "  - Nowe godziny: " . $optimizationResult['totalHours'] . "\n";
                echo "  - Walidacja: " . ($optimizationResult['validation']['isValid'] ? 'OK' : 'BŁĘDY') . "\n";
            }
        }
    }
    
    echo "\n";
}

// Funkcje pomocnicze do użycia w kodzie
function createSchedule($data)
{
    $response = makeApiRequest('POST', '/api/schedules', $data);
    return $response['success'] ? $response['data'] : null;
}

function generateSchedule($scheduleId)
{
    $response = makeApiRequest('POST', "/api/schedules/$scheduleId/generate");
    return $response['success'] ? $response['data'] : ['success' => false];
}

function optimizeScheduleILP($scheduleId)
{
    $response = makeApiRequest('POST', "/api/schedules/$scheduleId/optimize-ilp");
    return $response['success'] ? $response['data'] : ['success' => false];
}

// Przykład użycia w aplikacji
function exampleApplicationUsage()
{
    echo "=== Przykład użycia w aplikacji ===\n";
    
    // Symulacja aplikacji call center
    $callCenter = new CallCenterScheduler();
    
    // Konfiguracja
    $config = [
        'queueTypes' => [
            ['id' => 1, 'name' => 'Sprzedaż', 'priority' => 'high'],
            ['id' => 2, 'name' => 'Wsparcie techniczne', 'priority' => 'medium'],
            ['id' => 3, 'name' => 'Reklamacje', 'priority' => 'low']
        ],
        'weekStartDate' => '2024-01-01',
        'optimizationLevel' => 'high' // 'basic', 'medium', 'high'
    ];
    
    // Generowanie harmonogramów dla wszystkich kolejek
    foreach ($config['queueTypes'] as $queueType) {
        echo "Generowanie harmonogramu dla: " . $queueType['name'] . "\n";
        
        $result = $callCenter->generateScheduleForQueue(
            $queueType['id'],
            $config['weekStartDate'],
            $config['optimizationLevel']
        );
        
        if ($result['success']) {
            echo "  ✓ Harmonogram wygenerowany\n";
            echo "  - Przypisania: " . $result['assignmentsCount'] . "\n";
            echo "  - Godziny: " . $result['totalHours'] . "\n";
            echo "  - Efektywność: " . round($result['efficiency'], 2) . "%\n";
        } else {
            echo "  ✗ Błąd: " . $result['message'] . "\n";
        }
    }
    
    echo "\n";
}

// Klasa symulująca aplikację call center
class CallCenterScheduler
{
    public function generateScheduleForQueue($queueTypeId, $weekStartDate, $optimizationLevel)
    {
        // 1. Utworzenie harmonogramu
        $schedule = createSchedule([
            'queueTypeId' => $queueTypeId,
            'weekStartDate' => $weekStartDate
        ]);
        
        if (!$schedule) {
            return ['success' => false, 'message' => 'Nie można utworzyć harmonogramu'];
        }
        
        // 2. Generowanie podstawowe
        $generationResult = generateSchedule($schedule['id']);
        
        if (!$generationResult['success']) {
            return ['success' => false, 'message' => 'Nie można wygenerować harmonogramu'];
        }
        
        // 3. Optymalizacja w zależności od poziomu
        if ($optimizationLevel === 'high') {
            $optimizationResult = optimizeScheduleILP($schedule['id']);
            
            if ($optimizationResult['success']) {
                return [
                    'success' => true,
                    'assignmentsCount' => $optimizationResult['assignmentsCount'],
                    'totalHours' => $optimizationResult['totalHours'],
                    'efficiency' => $this->calculateEfficiency($optimizationResult['metrics'])
                ];
            }
        }
        
        return [
            'success' => true,
            'assignmentsCount' => $generationResult['assignmentsCount'],
            'totalHours' => $generationResult['totalAssignedHours'],
            'efficiency' => 85.0 // Domyślna efektywność
        ];
    }
    
    private function calculateEfficiency($metrics)
    {
        // Prosty algorytm obliczania efektywności
        $coverage = count(array_filter($metrics['hourlyCoverage'], fn($h) => $h > 0)) / count($metrics['hourlyCoverage']) * 100;
        $balance = (1 - ($metrics['maxHoursPerAgent'] - $metrics['minHoursPerAgent']) / $metrics['maxHoursPerAgent']) * 100;
        
        return ($coverage + $balance) / 2;
    }
}

// Uruchomienie przykładów aplikacji
// exampleApplicationUsage(); 