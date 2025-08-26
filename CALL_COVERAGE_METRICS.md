# Nowe metryki pokrycia rozmów (Call Coverage Metrics)

## Przegląd zmian

Zastąpiliśmy metrykę `hourlyCoverage` nowymi metrykami `callCoverage`, które są znacznie bardziej użyteczne dla zarządzania call center.

## Co było wcześniej (hourlyCoverage)

```json
"hourlyCoverage": {
    "2025-09-01 09:00": 12,
    "2025-09-02 09:00": 12,
    "2025-09-03 09:00": 12,
    "2025-09-04 09:00": 12,
    "2025-09-05 09:00": 12
}
```

**Problem**: Pokazywało tylko łączną liczbę godzin pracy agentów w określonych godzinach, ale nie informowało o tym, czy agenci są w stanie obsłużyć oczekiwaną liczbę rozmów.

## Co jest teraz (callCoverage)

```json
"callCoverage": {
    "2025-09-01": {
        "expectedCalls": 100,
        "agentCapacity": 80,
        "coverage": 125.0,
        "formatted": "100/80 (125.0%)"
    },
    "2025-09-02": {
        "expectedCalls": 120,
        "agentCapacity": 120,
        "coverage": 100.0,
        "formatted": "120/120 (100.0%)"
    }
}
```

## Opis nowych metryk

### `expectedCalls`
- **Typ**: `int`
- **Opis**: Przewidywana liczba rozmów na dany dzień (z predykcji)
- **Źródło**: Encja `CallQueueVolumePrediction`

### `agentCapacity`
- **Typ**: `int`
- **Opis**: Maksymalna liczba rozmów, które agenci mogą obsłużyć w danym dniu
- **Obliczanie**: `suma_godzin_agentów * 10` (zakładając średnio 10 rozmów na godzinę)

### `coverage`
- **Typ**: `float`
- **Opis**: Procent pokrycia rozmów przez agentów
- **Obliczanie**: `(expectedCalls / agentCapacity) * 100`
- **Interpretacja**:
  - `≥ 100%`: Wystarczające pokrycie
  - `80-99%`: Prawie wystarczające
  - `60-79%`: Niewystarczające
  - `< 60%`: Krytycznie niewystarczające

**Uwaga**: Pole `formatted` zostało usunięte. Frontend powinien samodzielnie formatować wyświetlanie metryk.

## Jak to działa

### 1. Pobieranie predykcji rozmów
```php
$predictions = $this->predictionRepository->findByQueueTypeAndDateRange(
    $schedule->getQueueType()->getId(),
    $schedule->getWeekStartDate(),
    $schedule->getWeekEndDate()
);
```

### 2. Grupowanie predykcji według dni
```php
foreach ($predictions as $prediction) {
    $dayKey = $prediction->getHour()->format('Y-m-d');
    $dailyPredictions[$dayKey] += $prediction->getExpectedCalls();
}
```

### 3. Obliczanie pojemności agentów
```php
foreach ($assignments as $assignment) {
    $dayKey = $assignment->getStartTime()->format('Y-m-d');
    $hours = $assignment->getDurationInHours();
    $dailyAgentCapacity[$dayKey] += (int)($hours * 10);
}
```

### 4. Obliczanie pokrycia
```php
foreach ($dailyPredictions as $day => $expectedCalls) {
    $agentCapacity = $dailyAgentCapacity[$day] ?? 0;
    $coveragePercentage = round(($expectedCalls / $agentCapacity) * 100, 1);
    
                $callCoverage[$day] = [
                'expectedCalls' => $expectedCalls,
                'agentCapacity' => $agentCapacity,
                'coverage' => $coveragePercentage
            ];
}
```

## Korzyści z nowych metryk

### Dla menedżerów call center:
- **Widoczność**: Wiedzą, czy mają wystarczająco agentów
- **Planowanie**: Mogą planować zatrudnienie na podstawie pokrycia
- **Optymalizacja**: Widzą, które dni wymagają uwagi

### Dla planistów:
- **Balansowanie**: Mogą balansować obciążenie między dniami
- **Efektywność**: Widzą, czy nie ma nadmiernego pokrycia
- **Koszty**: Mogą optymalizować koszty zatrudnienia

### Dla agentów:
- **Przewidywalność**: Wiedzą, jak intensywna będzie praca
- **Planowanie**: Mogą planować swoje zadania

## Przykłady użycia

### Sprawdzenie dni z problemami
```php
// Dni z niewystarczającym pokryciem
$insufficientDays = array_filter($callCoverage, fn($c) => $c['coverage'] < 100);

// Dni z nadmiernym obciążeniem
$overloadedDays = array_filter($callCoverage, fn($c) => $c['coverage'] > 120);
```

### Analiza trendów
```php
$avgCoverage = array_sum(array_column($callCoverage, 'coverage')) / count($callCoverage);
$trend = $avgCoverage >= 100 ? 'Wystarczające' : 'Niewystarczające';
```

### Raportowanie
```php
foreach ($callCoverage as $day => $coverage) {
    echo "{$day}: {$coverage['formatted']}\n";
}
```

## Konfiguracja

### Współczynnik wydajności agenta
Obecnie zakładamy, że agent może obsłużyć średnio **10 rozmów na godzinę**. Można to skonfigurować:

```php
// W ILPOptimizationService.php
private const AGENT_EFFICIENCY_PER_HOUR = 10;

// Użycie:
$dailyAgentCapacity[$dayKey] += (int)($hours * self::AGENT_EFFICIENCY_PER_HOUR);
```

### Dostosowanie do różnych typów kolejek
Różne typy kolejek mogą mieć różne współczynniki wydajności:

```php
$efficiencyScore = $agentQueueType->getEfficiencyScore();
$dailyAgentCapacity[$dayKey] += (int)($hours * $efficiencyScore);
```

## Migracja z hourlyCoverage

Jeśli masz kod używający `hourlyCoverage`, musisz go zaktualizować:

### Przed:
```php
$hourlyCoverage = $metrics['hourlyCoverage'];
$totalHours = array_sum($hourlyCoverage);
```

### Po:
```php
$callCoverage = $metrics['callCoverage'];
$totalCapacity = array_sum(array_column($callCoverage, 'agentCapacity'));
$totalExpected = array_sum(array_column($callCoverage, 'expectedCalls'));

// Formatowanie na frontendzie
$formatted = "{$totalExpected}/{$totalCapacity} (" . round(($totalExpected/$totalCapacity)*100, 1) . "%)";
```

## Testowanie

Uruchom test nowych metryk:

```bash
cd backend
php test_call_coverage.php
```

## Podsumowanie

Nowe metryki `callCoverage` zapewniają znacznie lepszy wgląd w efektywność harmonogramu call center:

✅ **Przewidywane rozmowy** vs **pojemność agentów**  
✅ **Procent pokrycia** w czytelnym formacie  
✅ **Analiza dzienna** zamiast godzinowej  
✅ **Praktyczne rekomendacje** dla menedżerów  
✅ **Łatwiejsze planowanie** zasobów  

Te metryki pozwalają na lepsze zarządzanie call center i optymalizację harmonogramów pracy.
