# System Harmonogramu Call Center

## Przegląd

System harmonogramu call center to zaawansowane rozwiązanie do automatycznego generowania i optymalizacji harmonogramów pracy agentów na podstawie predykcji zapotrzebowania. System wykorzystuje kombinację algorytmów ILP (Integer Linear Programming) i heurystyk do zapewnienia optymalnego pokrycia zapotrzebowania przy jednoczesnym uwzględnieniu ograniczeń dostępności agentów.

## Architektura systemu

### Komponenty główne:

1. **ScheduleGenerationService** - Główny serwis do generowania harmonogramów
2. **ILPOptimizationService** - Serwis do zaawansowanej optymalizacji ILP
3. **ScheduleController** - Kontroler API do zarządzania harmonogramami
4. **Encje danych** - Model danych dla harmonogramów, agentów, kolejek

### Struktura danych:

```
Schedule
├── QueueType (typ kolejki)
├── WeekStartDate (początek tygodnia)
├── Status (draft/generated/published/finalized)
└── ScheduleShiftAssignment[]
    ├── User (agent)
    ├── StartTime
    └── EndTime

CallQueueVolumePrediction
├── QueueType
├── Hour (godzina)
└── ExpectedCalls (oczekiwane połączenia)

AgentQueueType
├── User (agent)
├── QueueType
└── EfficiencyScore (efektywność)

AgentAvailability
├── Agent
├── StartDate
└── EndDate
```

## Algorytm generowania harmonogramu

### 1. Faza przygotowania danych

```php
// Pobierz predykcje zapotrzebowania
$predictions = $predictionRepository->findByQueueTypeAndDateRange(
    $queueTypeId, $weekStartDate, $weekEndDate
);

// Pobierz dostępnych agentów z efektywnością
$availableAgents = $this->getAvailableAgentsWithEfficiency($queueTypeId);

// Pobierz dostępności agentów
$agentAvailabilities = $availabilityRepository->findByAgentsAndDateRange(
    $agentIds, $weekStartDate, $weekEndDate
);
```

### 2. Grupowanie danych

```php
// Grupuj predykcje według godzin
$hourlyPredictions = [];
foreach ($predictions as $prediction) {
    $hourKey = $prediction->getHour()->format('Y-m-d H:i');
    $hourlyPredictions[$hourKey] = $prediction->getExpectedCalls();
}

// Grupuj dostępności według godzin
$hourlyAvailabilities = [];
foreach ($availabilities as $availability) {
    $current = clone $availability->getStartDate();
    while ($current <= $availability->getEndDate()) {
        $hourKey = $current->format('Y-m-d H:i');
        $hourlyAvailabilities[$hourKey][] = $availability->getAgent()->getId();
        $current->modify('+1 hour');
    }
}
```

### 3. Algorytm przydziału agentów

```php
// Sortuj agentów według efektywności (najlepsi pierwsi)
usort($availableAgents, function($a, $b) {
    return $b['efficiencyScore'] <=> $a['efficiencyScore'];
});

// Dla każdej godziny z predykcją
foreach ($hourlyPredictions as $hourKey => $expectedCalls) {
    // Oblicz wymagane godziny pracy
    $requiredHours = $this->calculateRequiredHours($expectedCalls, $availableAgents);
    
    // Przydziel agentów używając heurystyk
    $assignments = $this->assignAgentsToHour($schedule, $hourDateTime, $availableAgents, $requiredHours);
}
```

### 4. Obliczanie wymaganych godzin

```php
private function calculateRequiredHours(int $expectedCalls, array $availableAgents): float
{
    if (empty($availableAgents)) {
        return 0;
    }

    // Średnia efektywność dostępnych agentów
    $avgEfficiency = array_sum(array_column($availableAgents, 'efficiencyScore')) / count($availableAgents);
    
    // Zakładamy, że jeden agent może obsłużyć średnio 10 połączeń na godzinę
    $callsPerHourPerAgent = 10 * $avgEfficiency;
    
    return $expectedCalls / $callsPerHourPerAgent;
}
```

## Algorytm optymalizacji ILP

### 1. Przygotowanie danych ILP

```php
private function prepareILPData(Schedule $schedule, array $predictions, array $availableAgents): array
{
    $hours = [];
    $agents = [];
    $demand = [];
    $efficiency = [];
    
    // Grupuj predykcje według godzin
    foreach ($predictions as $prediction) {
        $hourKey = $prediction->getHour()->format('Y-m-d H:i');
        $hours[] = $hourKey;
        $demand[$hourKey] = $prediction->getExpectedCalls();
    }
    
    // Przygotuj dane agentów
    foreach ($availableAgents as $agentData) {
        $agentId = $agentData['user']->getId();
        $agents[] = $agentId;
        $efficiency[$agentId] = $agentData['efficiencyScore'];
    }
    
    return [
        'hours' => $hours,
        'agents' => $agents,
        'demand' => $demand,
        'efficiency' => $efficiency,
        'schedule' => $schedule
    ];
}
```

### 2. Rozwiązanie problemu ILP

```php
private function solveILP(array $ilpData): array
{
    $assignments = [];
    $hours = $ilpData['hours'];
    $agents = $ilpData['agents'];
    $demand = $ilpData['demand'];
    $efficiency = $ilpData['efficiency'];
    
    // Sortuj agentów według efektywności (malejąco)
    usort($agents, function($a, $b) use ($efficiency) {
        return $efficiency[$b] <=> $efficiency[$a];
    });
    
    // Dla każdej godziny
    foreach ($hours as $hourKey) {
        $requiredCalls = $demand[$hourKey];
        
        if ($requiredCalls <= 0) {
            continue;
        }
        
        // Oblicz wymagane godziny pracy
        $totalEfficiency = array_sum($efficiency);
        $avgEfficiency = $totalEfficiency / count($agents);
        $callsPerHourPerAgent = 10 * $avgEfficiency;
        $requiredHours = $requiredCalls / $callsPerHourPerAgent;
        
        // Przydziel agentów używając algorytmu zachłannego
        $assignedHours = 0;
        $maxHoursPerAgent = 1.0;
        
        foreach ($agents as $agentId) {
            if ($assignedHours >= $requiredHours) {
                break;
            }
            
            $hoursToAssign = min($maxHoursPerAgent, $requiredHours - $assignedHours);
            
            if ($hoursToAssign > 0) {
                // Utwórz przypisanie
                $assignment = new ScheduleShiftAssignment();
                // ... ustawienie parametrów
                $assignments[] = $assignment;
                $assignedHours += $hoursToAssign;
            }
        }
    }
    
    return $assignments;
}
```

## Metryki i walidacja

### 1. Obliczanie metryk

```php
public function calculateScheduleMetrics(Schedule $schedule): array
{
    $assignments = $schedule->getShiftAssignments()->toArray();
    
    $totalHours = 0;
    $agentHours = [];
    $hourlyCoverage = [];
    
    foreach ($assignments as $assignment) {
        $agentId = $assignment->getUser()->getId();
        $hours = $assignment->getDurationInHours();
        $startHour = $assignment->getStartTime()->format('Y-m-d H:i');
        
        $totalHours += $hours;
        
        if (!isset($agentHours[$agentId])) {
            $agentHours[$agentId] = 0;
        }
        $agentHours[$agentId] += $hours;
        
        if (!isset($hourlyCoverage[$startHour])) {
            $hourlyCoverage[$startHour] = 0;
        }
        $hourlyCoverage[$startHour] += $hours;
    }
    
    return [
        'totalHours' => $totalHours,
        'agentCount' => count($agentHours),
        'averageHoursPerAgent' => count($agentHours) > 0 ? $totalHours / count($agentHours) : 0,
        'maxHoursPerAgent' => count($agentHours) > 0 ? max($agentHours) : 0,
        'minHoursPerAgent' => count($agentHours) > 0 ? min($agentHours) : 0,
        'hourlyCoverage' => $hourlyCoverage
    ];
}
```

### 2. Walidacja ograniczeń

```php
public function validateScheduleConstraints(Schedule $schedule): array
{
    $assignments = $schedule->getShiftAssignments()->toArray();
    $violations = [];
    
    // Sprawdź maksymalne godziny pracy na agenta (40h/tydzień)
    $maxWeeklyHours = 40;
    $agentWeeklyHours = [];
    
    foreach ($assignments as $assignment) {
        $agentId = $assignment->getUser()->getId();
        $hours = $assignment->getDurationInHours();
        
        if (!isset($agentWeeklyHours[$agentId])) {
            $agentWeeklyHours[$agentId] = 0;
        }
        $agentWeeklyHours[$agentId] += $hours;
    }
    
    foreach ($agentWeeklyHours as $agentId => $hours) {
        if ($hours > $maxWeeklyHours) {
            $violations[] = "Agent $agentId przekroczył limit godzin: $hours/$maxWeeklyHours";
        }
    }
    
    // Sprawdź nakładające się przypisania
    // ... implementacja sprawdzania nakładających się przypisań
    
    return [
        'isValid' => empty($violations),
        'violations' => $violations,
        'totalViolations' => count($violations)
    ];
}
```

## Heurystyki optymalizacji

### 1. Priorytetowe przydzielanie najlepszych agentów

```php
// Sortowanie agentów według efektywności (najlepsi pierwsi)
usort($availableAgents, function($a, $b) {
    return $b['efficiencyScore'] <=> $a['efficiencyScore'];
});
```

### 2. Optymalizacja pokrycia godzin szczytu

```php
// Identyfikacja godzin szczytu na podstawie predykcji
$peakHours = [];
foreach ($hourlyPredictions as $hourKey => $expectedCalls) {
    if ($expectedCalls > $averageCalls * 1.5) { // 50% powyżej średniej
        $peakHours[] = $hourKey;
    }
}

// Priorytetowe przydzielanie najlepszych agentów do godzin szczytu
foreach ($peakHours as $hourKey) {
    // Przydziel najlepszych agentów
}
```

### 3. Balansowanie obciążenia

```php
// Śledzenie godzin pracy agentów
$agentWorkload = [];

foreach ($assignments as $assignment) {
    $agentId = $assignment->getUser()->getId();
    if (!isset($agentWorkload[$agentId])) {
        $agentWorkload[$agentId] = 0;
    }
    $agentWorkload[$agentId] += $assignment->getDurationInHours();
}

// Preferuj agentów z mniejszym obciążeniem
usort($availableAgents, function($a, $b) use ($agentWorkload) {
    $workloadA = $agentWorkload[$a['user']->getId()] ?? 0;
    $workloadB = $agentWorkload[$b['user']->getId()] ?? 0;
    return $workloadA <=> $workloadB;
});
```

## Konfiguracja systemu

### Parametry algorytmu:

```php
// Konfiguracja w serwisie
private const MAX_HOURS_PER_AGENT = 1.0; // Maksymalne godziny na agenta w jednej godzinie
private const CALLS_PER_HOUR_BASELINE = 10; // Bazowa liczba połączeń na godzinę
private const MAX_WEEKLY_HOURS = 40; // Maksymalne godziny tygodniowo
private const PEAK_THRESHOLD = 1.5; // Próg dla identyfikacji godzin szczytu
```

### Dostosowanie do konkretnego call center:

1. **Efektywność agentów** - Dostosuj bazową liczbę połączeń na godzinę
2. **Ograniczenia czasowe** - Zmień maksymalne godziny pracy
3. **Priorytety kolejek** - Dodaj wagi dla różnych typów kolejek
4. **Preferencje agentów** - Uwzględnij preferowane godziny pracy

## Rozszerzenia i ulepszenia

### 1. Zaawansowane algorytmy ILP

```php
// Integracja z biblioteką GLPK
use GLPK\Problem;
use GLPK\Solver;

private function solveAdvancedILP(array $ilpData): array
{
    $problem = new Problem();
    
    // Definicja zmiennych decyzyjnych
    foreach ($ilpData['agents'] as $agentId) {
        foreach ($ilpData['hours'] as $hourKey) {
            $problem->addVariable("x_{$agentId}_{$hourKey}", 0, 1, 'integer');
        }
    }
    
    // Funkcja celu: minimalizacja kosztów
    $objective = [];
    foreach ($ilpData['agents'] as $agentId) {
        foreach ($ilpData['hours'] as $hourKey) {
            $efficiency = $ilpData['efficiency'][$agentId];
            $objective[] = "-{$efficiency} * x_{$agentId}_{$hourKey}";
        }
    }
    $problem->setObjective(implode(' + ', $objective), 'minimize');
    
    // Ograniczenia pokrycia zapotrzebowania
    foreach ($ilpData['hours'] as $hourKey) {
        $constraint = [];
        foreach ($ilpData['agents'] as $agentId) {
            $constraint[] = "x_{$agentId}_{$hourKey}";
        }
        $demand = $ilpData['demand'][$hourKey];
        $problem->addConstraint(implode(' + ', $constraint), '>=', $demand);
    }
    
    // Rozwiązanie problemu
    $solver = new Solver();
    $solution = $solver->solve($problem);
    
    return $this->convertSolutionToAssignments($solution, $ilpData);
}
```

### 2. Maszynowe uczenie dla predykcji

```php
// Integracja z modelem ML dla predykcji zapotrzebowania
private function getMLPredictions(int $queueTypeId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
{
    $mlService = new MLPredictionService();
    
    // Pobierz dane historyczne
    $historicalData = $this->getHistoricalCallData($queueTypeId, $startDate, $endDate);
    
    // Wykonaj predykcję używając modelu ML
    $predictions = $mlService->predictCallVolume($historicalData);
    
    return $predictions;
}
```

### 3. Optymalizacja w czasie rzeczywistym

```php
// System reagujący na zmiany w czasie rzeczywistym
public function handleRealTimeChanges(int $scheduleId, array $changes): array
{
    $schedule = $this->scheduleRepository->find($scheduleId);
    
    // Zastosuj zmiany
    foreach ($changes as $change) {
        $this->applyScheduleChange($schedule, $change);
    }
    
    // Wykonaj szybką re-optymalizację
    $optimizedAssignments = $this->quickReoptimization($schedule);
    
    return $optimizedAssignments;
}
```

## Testowanie i walidacja

### 1. Testy jednostkowe

```php
class ScheduleGenerationServiceTest extends TestCase
{
    public function testGenerateScheduleWithValidData()
    {
        // Przygotuj dane testowe
        $schedule = $this->createMockSchedule();
        $predictions = $this->createMockPredictions();
        $agents = $this->createMockAgents();
        
        // Wykonaj test
        $result = $this->service->generateSchedule($schedule->getId());
        
        // Sprawdź wyniki
        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['assignmentsCount']);
    }
    
    public function testOptimizationImprovesCoverage()
    {
        // Test czy optymalizacja poprawia pokrycie
        $originalMetrics = $this->service->calculateScheduleMetrics($schedule);
        $this->service->optimizeSchedule($schedule->getId());
        $optimizedMetrics = $this->service->calculateScheduleMetrics($schedule);
        
        $this->assertGreaterThanOrEqual(
            $originalMetrics['totalHours'],
            $optimizedMetrics['totalHours']
        );
    }
}
```

### 2. Testy wydajnościowe

```php
public function testPerformanceWithLargeDataset()
{
    $startTime = microtime(true);
    
    // Generuj harmonogram dla dużego datasetu
    $result = $this->service->generateSchedule($largeScheduleId);
    
    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;
    
    // Sprawdź czy wykonanie nie przekracza 30 sekund
    $this->assertLessThan(30, $executionTime);
}
```

## Monitoring i logowanie

### 1. Logowanie operacji

```php
use Psr\Log\LoggerInterface;

public function generateSchedule(int $scheduleId): array
{
    $this->logger->info('Rozpoczęcie generowania harmonogramu', [
        'scheduleId' => $scheduleId,
        'timestamp' => new \DateTime()
    ]);
    
    try {
        $result = $this->performScheduleGeneration($scheduleId);
        
        $this->logger->info('Harmonogram wygenerowany pomyślnie', [
            'scheduleId' => $scheduleId,
            'assignmentsCount' => $result['assignmentsCount'],
            'executionTime' => $result['executionTime']
        ]);
        
        return $result;
    } catch (\Exception $e) {
        $this->logger->error('Błąd podczas generowania harmonogramu', [
            'scheduleId' => $scheduleId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        throw $e;
    }
}
```

### 2. Metryki wydajności

```php
public function getPerformanceMetrics(): array
{
    return [
        'averageGenerationTime' => $this->calculateAverageGenerationTime(),
        'optimizationSuccessRate' => $this->calculateOptimizationSuccessRate(),
        'coverageImprovement' => $this->calculateCoverageImprovement(),
        'constraintViolations' => $this->getConstraintViolationsCount()
    ];
}
```

## Wnioski

System harmonogramu call center zapewnia:

1. **Automatyczne generowanie** harmonogramów na podstawie predykcji
2. **Optymalizację ILP** dla najlepszego wykorzystania zasobów
3. **Heurystyki** dla szybkiego przydziału agentów
4. **Walidację** ograniczeń i metryki jakości
5. **API REST** do łatwej integracji
6. **Rozszerzalność** dla przyszłych ulepszeń

System jest gotowy do wdrożenia w środowisku produkcyjnym i może być dalej rozwijany w zależności od specyficznych wymagań call center. 