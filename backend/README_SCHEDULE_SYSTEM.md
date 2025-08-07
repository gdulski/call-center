# System Harmonogramu Call Center

## 🎯 Przegląd

System harmonogramu call center to zaawansowane rozwiązanie do automatycznego generowania i optymalizacji harmonogramów pracy agentów na podstawie predykcji zapotrzebowania. System wykorzystuje kombinację algorytmów **ILP (Integer Linear Programming)** i **heurystyk** do zapewnienia optymalnego pokrycia zapotrzebowania przy jednoczesnym uwzględnieniu ograniczeń dostępności agentów.

## 🏗️ Architektura

### Komponenty główne:

- **`ScheduleGenerationService`** - Główny serwis do generowania harmonogramów
- **`ILPOptimizationService`** - Serwis do zaawansowanej optymalizacji ILP
- **`ScheduleController`** - Kontroler API do zarządzania harmonogramami
- **Encje danych** - Model danych dla harmonogramów, agentów, kolejek

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

## 🚀 Funkcjonalności

### 1. Generowanie harmonogramów
- Automatyczne tworzenie harmonogramów na podstawie predykcji zapotrzebowania
- Uwzględnianie dostępności agentów
- Priorytetowe przydzielanie najlepszych agentów do najważniejszych kolejek

### 2. Optymalizacja ILP
- Zaawansowana optymalizacja używająca algorytmu Integer Linear Programming
- Minimalizacja kosztów przy maksymalizacji pokrycia zapotrzebowania
- Optymalizacja wykorzystania agentów

### 3. Heurystyki optymalizacji
- Priorytetowe przydzielanie najlepszych agentów
- Optymalizacja pokrycia godzin szczytu
- Balansowanie obciążenia między agentami

### 4. Walidacja i metryki
- Sprawdzanie ograniczeń czasowych (maks. 40h/tydzień)
- Wykrywanie nakładających się przypisań
- Obliczanie metryk jakości harmonogramu

## 📊 API Endpointy

### Podstawowe operacje:

| Metoda | Endpoint | Opis |
|--------|----------|------|
| `GET` | `/api/schedules` | Lista wszystkich harmonogramów |
| `GET` | `/api/schedules/{id}` | Szczegóły harmonogramu |
| `POST` | `/api/schedules` | Utworzenie nowego harmonogramu |
| `POST` | `/api/schedules/{id}/generate` | Generowanie przypisań |
| `POST` | `/api/schedules/{id}/optimize` | Optymalizacja heurystyczna |
| `POST` | `/api/schedules/{id}/optimize-ilp` | Optymalizacja ILP |
| `GET` | `/api/schedules/{id}/metrics` | Metryki harmonogramu |
| `PATCH` | `/api/schedules/{id}/status` | Aktualizacja statusu |
| `DELETE` | `/api/schedules/{id}` | Usunięcie harmonogramu |

## 🔧 Szybki start

### 1. Utworzenie harmonogramu

```bash
# Utwórz harmonogram
curl -X POST http://localhost:8000/api/schedules \
  -H "Content-Type: application/json" \
  -d '{"queueTypeId": 1, "weekStartDate": "2024-01-01"}'
```

### 2. Generowanie przypisań

```bash
# Wygeneruj przypisania
curl -X POST http://localhost:8000/api/schedules/1/generate
```

### 3. Optymalizacja ILP

```bash
# Wykonaj optymalizację ILP
curl -X POST http://localhost:8000/api/schedules/1/optimize-ilp
```

### 4. Sprawdzenie metryk

```bash
# Pobierz metryki
curl http://localhost:8000/api/schedules/1/metrics
```

## 📈 Metryki i walidacja

### Podstawowe metryki:
- **Total Hours** - Łączna liczba przypisanych godzin
- **Agent Count** - Liczba zaangażowanych agentów
- **Average Hours Per Agent** - Średnia liczba godzin na agenta
- **Max/Min Hours Per Agent** - Maksymalna/minimalna liczba godzin na agenta

### Walidacja:
- **Weekly Hours Limit** - Sprawdzenie limitu 40h/tydzień
- **Overlapping Assignments** - Wykrywanie nakładających się przypisań
- **Coverage Validation** - Weryfikacja pokrycia zapotrzebowania

## 🧮 Algorytm optymalizacji

### 1. Generowanie podstawowe

```php
// Pobierz predykcje zapotrzebowania
$predictions = $predictionRepository->findByQueueTypeAndDateRange(
    $queueTypeId, $weekStartDate, $weekEndDate
);

// Pobierz dostępnych agentów z efektywnością
$availableAgents = $this->getAvailableAgentsWithEfficiency($queueTypeId);

// Grupuj dane według godzin
$hourlyPredictions = $this->groupPredictionsByHour($predictions);
$hourlyAvailabilities = $this->groupAvailabilitiesByHour($agentAvailabilities);

// Przydziel agentów używając heurystyk
$assignments = $this->generateOptimalAssignments(
    $schedule, $hourlyPredictions, $availableAgents, $hourlyAvailabilities
);
```

### 2. Optymalizacja ILP

```php
// Przygotuj dane dla algorytmu ILP
$ilpData = $this->prepareILPData($schedule, $predictions, $availableAgents);

// Wykonaj optymalizację
$optimizedAssignments = $this->solveILP($ilpData);

// Waliduj wyniki
$metrics = $this->calculateScheduleMetrics($schedule);
$validation = $this->validateScheduleConstraints($schedule);
```

## 🎛️ Konfiguracja

### Parametry algorytmu:

```php
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

## 📋 Statusy harmonogramu

- `draft` - Szkic
- `generated` - Wygenerowany
- `published` - Opublikowany
- `finalized` - Sfinalizowany

## 🔍 Przykłady użycia

### Utworzenie i wygenerowanie harmonogramu:

```php
// 1. Utwórz harmonogram
$schedule = createSchedule([
    'queueTypeId' => 1,
    'weekStartDate' => '2024-01-01'
]);

// 2. Wygeneruj przypisania
$result = generateSchedule($schedule['id']);

// 3. Sprawdź metryki
$metrics = getScheduleMetrics($schedule['id']);

// 4. Zoptymalizuj używając ILP
$optimized = optimizeScheduleILP($schedule['id']);
```

### Analiza harmonogramu:

```php
$metrics = getScheduleMetrics($scheduleId);

echo "Łączne godziny: " . $metrics['totalHours'] . "\n";
echo "Liczba agentów: " . $metrics['agentCount'] . "\n";
echo "Średnie godziny na agenta: " . $metrics['averageHoursPerAgent'] . "\n";
echo "Walidacja: " . ($metrics['validation']['isValid'] ? 'OK' : 'BŁĘDY') . "\n";
```

## 🧪 Testowanie

### Testy jednostkowe:

```bash
# Uruchom testy
php bin/phpunit tests/Service/ScheduleGenerationServiceTest.php
```

### Testy wydajnościowe:

```bash
# Test wydajności z dużym datasetem
php bin/console app:test:schedule-performance
```

## 📚 Dokumentacja

- [API Documentation](docs/API_SCHEDULE.md) - Szczegółowa dokumentacja API
- [System Architecture](docs/SCHEDULE_SYSTEM.md) - Architektura systemu
- [Usage Examples](examples/schedule_usage_examples.php) - Przykłady użycia

## 🔧 Rozszerzenia

### Możliwe rozszerzenia:

1. **Zaawansowane algorytmy ILP** - Integracja z biblioteką GLPK
2. **Maszynowe uczenie** - ML dla predykcji zapotrzebowania
3. **Optymalizacja w czasie rzeczywistym** - Reagowanie na zmiany
4. **Interfejs webowy** - Panel administracyjny
5. **Integracja z systemami zewnętrznymi** - CRM, systemy kadrowe

## 🚀 Wdrożenie

### Wymagania:

- PHP 8.2+
- Symfony 6.3+
- MySQL 8.0+
- Redis (opcjonalnie)

### Instalacja:

```bash
# Zainstaluj zależności
composer install

# Uruchom migracje
php bin/console doctrine:migrations:migrate

# Załaduj dane testowe
php bin/console doctrine:fixtures:load
```

### Konfiguracja:

```yaml
# config/services.yaml
services:
    App\Service\ScheduleGenerationService:
        arguments:
            $maxHoursPerAgent: 1.0
            $callsPerHourBaseline: 10
            $maxWeeklyHours: 40
```

## 📊 Monitoring

### Logowanie:

```php
// Logowanie operacji
$this->logger->info('Harmonogram wygenerowany', [
    'scheduleId' => $scheduleId,
    'assignmentsCount' => $assignmentsCount,
    'executionTime' => $executionTime
]);
```

### Metryki wydajności:

```php
// Metryki wydajności
$metrics = [
    'averageGenerationTime' => $this->calculateAverageGenerationTime(),
    'optimizationSuccessRate' => $this->calculateOptimizationSuccessRate(),
    'coverageImprovement' => $this->calculateCoverageImprovement(),
    'constraintViolations' => $this->getConstraintViolationsCount()
];
```

## 🤝 Wsparcie

### Problemy i rozwiązania:

1. **Wolne generowanie harmonogramów** - Użyj optymalizacji ILP
2. **Brak pokrycia zapotrzebowania** - Sprawdź dostępności agentów
3. **Naruszenia ograniczeń** - Sprawdź metryki walidacji

### Kontakt:

- **Dokumentacja**: [docs/](docs/)
- **Przykłady**: [examples/](examples/)
- **Testy**: [tests/](tests/)

## 📄 Licencja

MIT License - zobacz plik [LICENSE](LICENSE) dla szczegółów.

---

**System harmonogramu call center** - Zaawansowane rozwiązanie do optymalizacji harmonogramów pracy agentów w call center. 