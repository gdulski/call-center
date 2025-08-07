# API Harmonogramu Call Center

## Przegląd

API harmonogramu call center umożliwia zarządzanie harmonogramami pracy agentów na podstawie predykcji zapotrzebowania. System wykorzystuje algorytmy ILP (Integer Linear Programming) i heurystyki do optymalizacji przydziału agentów do kolejek.

## Endpointy

### 1. Lista harmonogramów

**GET** `/api/schedules`

Zwraca listę wszystkich harmonogramów.

**Odpowiedź:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "queueType": "Sprzedaż",
      "weekStartDate": "2024-01-01",
      "weekEndDate": "2024-01-07",
      "status": "generated",
      "totalAssignedHours": 168.5,
      "assignmentsCount": 45
    }
  ]
}
```

### 2. Szczegóły harmonogramu

**GET** `/api/schedules/{id}`

Zwraca szczegółowe informacje o harmonogramie wraz z przypisaniami.

**Odpowiedź:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "queueType": {
      "id": 1,
      "name": "Sprzedaż"
    },
    "weekStartDate": "2024-01-01",
    "weekEndDate": "2024-01-07",
    "status": "generated",
    "totalAssignedHours": 168.5,
    "assignments": [
      {
        "id": 1,
        "agentId": 5,
        "agentName": "Jan Kowalski",
        "startTime": "2024-01-01 09:00",
        "endTime": "2024-01-01 17:00",
        "duration": 8.0
      }
    ]
  }
}
```

### 3. Utworzenie harmonogramu

**POST** `/api/schedules`

Tworzy nowy harmonogram dla określonego typu kolejki i tygodnia.

**Request Body:**
```json
{
  "queueTypeId": 1,
  "weekStartDate": "2024-01-01"
}
```

**Odpowiedź:**
```json
{
  "success": true,
  "message": "Harmonogram został utworzony",
  "data": {
    "id": 1,
    "queueType": "Sprzedaż",
    "weekStartDate": "2024-01-01",
    "weekEndDate": "2024-01-07",
    "status": "draft"
  }
}
```

### 4. Generowanie harmonogramu

**POST** `/api/schedules/{id}/generate`

Generuje przypisania agentów na podstawie predykcji zapotrzebowania.

**Odpowiedź:**
```json
{
  "success": true,
  "message": "Harmonogram został wygenerowany pomyślnie",
  "data": {
    "scheduleId": 1,
    "queueType": "Sprzedaż",
    "weekStartDate": "2024-01-01",
    "weekEndDate": "2024-01-07",
    "predictionsCount": 168,
    "availableAgentsCount": 15,
    "assignmentsCount": 45,
    "totalAssignedHours": 168.5
  }
}
```

### 5. Optymalizacja harmonogramu

**POST** `/api/schedules/{id}/optimize`

Optymalizuje istniejący harmonogram używając algorytmu heurystycznego.

**Odpowiedź:**
```json
{
  "success": true,
  "message": "Harmonogram został zoptymalizowany",
  "data": {
    "scheduleId": 1,
    "optimizedAssignmentsCount": 42,
    "totalOptimizedHours": 165.0
  }
}
```

### 6. Optymalizacja ILP

**POST** `/api/schedules/{id}/optimize-ilp`

Wykonuje zaawansowaną optymalizację używając algorytmu ILP.

**Odpowiedź:**
```json
{
  "success": true,
  "message": "Harmonogram został zoptymalizowany używając ILP",
  "data": {
    "assignmentsCount": 40,
    "totalHours": 160.0,
    "metrics": {
      "totalHours": 160.0,
      "agentCount": 12,
      "averageHoursPerAgent": 13.33,
      "maxHoursPerAgent": 40.0,
      "minHoursPerAgent": 8.0,
      "hourlyCoverage": {
        "2024-01-01 09:00": 12.0,
        "2024-01-01 10:00": 15.0
      }
    },
    "validation": {
      "isValid": true,
      "violations": [],
      "totalViolations": 0
    }
  }
}
```

### 7. Metryki harmonogramu

**GET** `/api/schedules/{id}/metrics`

Zwraca metryki jakości i walidację harmonogramu.

**Odpowiedź:**
```json
{
  "success": true,
  "data": {
    "metrics": {
      "totalHours": 160.0,
      "agentCount": 12,
      "averageHoursPerAgent": 13.33,
      "maxHoursPerAgent": 40.0,
      "minHoursPerAgent": 8.0,
      "hourlyCoverage": {
        "2024-01-01 09:00": 12.0
      }
    },
    "validation": {
      "isValid": true,
      "violations": [],
      "totalViolations": 0
    }
  }
}
```

### 8. Aktualizacja statusu

**PATCH** `/api/schedules/{id}/status`

Aktualizuje status harmonogramu.

**Request Body:**
```json
{
  "status": "published"
}
```

**Odpowiedź:**
```json
{
  "success": true,
  "message": "Status harmonogramu został zaktualizowany",
  "data": {
    "id": 1,
    "status": "published"
  }
}
```

### 9. Usunięcie harmonogramu

**DELETE** `/api/schedules/{id}`

Usuwa harmonogram i wszystkie jego przypisania.

**Odpowiedź:**
```json
{
  "success": true,
  "message": "Harmonogram został usunięty"
}
```

## Statusy harmonogramu

- `draft` - Szkic
- `generated` - Wygenerowany
- `published` - Opublikowany
- `finalized` - Sfinalizowany

## Algorytm optymalizacji

### 1. Generowanie podstawowe

System generuje harmonogram w następujących krokach:

1. **Pobranie danych:**
   - Predykcje zapotrzebowania dla każdej godziny
   - Lista dostępnych agentów z ich efektywnością
   - Dostępności agentów w danym tygodniu

2. **Grupowanie danych:**
   - Predykcje grupowane według godzin
   - Dostępności grupowane według godzin

3. **Przydział agentów:**
   - Sortowanie agentów według efektywności (najlepsi pierwsi)
   - Obliczenie wymaganych godzin na podstawie predykcji
   - Przydzielenie agentów używając heurystyk

### 2. Optymalizacja ILP

Zaawansowana optymalizacja używająca algorytmu Integer Linear Programming:

1. **Przygotowanie danych ILP:**
   - Definicja zmiennych decyzyjnych
   - Określenie funkcji celu
   - Definicja ograniczeń

2. **Rozwiązanie problemu:**
   - Minimalizacja kosztów
   - Maksymalizacja pokrycia zapotrzebowania
   - Optymalizacja wykorzystania agentów

3. **Walidacja wyników:**
   - Sprawdzenie ograniczeń czasowych
   - Weryfikacja nakładających się przypisań
   - Obliczenie metryk jakości

## Metryki jakości

### Podstawowe metryki:
- **Total Hours** - Łączna liczba przypisanych godzin
- **Agent Count** - Liczba zaangażowanych agentów
- **Average Hours Per Agent** - Średnia liczba godzin na agenta
- **Max/Min Hours Per Agent** - Maksymalna/minimalna liczba godzin na agenta

### Walidacja:
- **Weekly Hours Limit** - Sprawdzenie limitu 40h/tydzień
- **Overlapping Assignments** - Wykrywanie nakładających się przypisań
- **Coverage Validation** - Weryfikacja pokrycia zapotrzebowania

## Przykłady użycia

### Utworzenie i wygenerowanie harmonogramu:

```bash
# 1. Utwórz harmonogram
curl -X POST http://localhost:8000/api/schedules \
  -H "Content-Type: application/json" \
  -d '{"queueTypeId": 1, "weekStartDate": "2024-01-01"}'

# 2. Wygeneruj przypisania
curl -X POST http://localhost:8000/api/schedules/1/generate

# 3. Sprawdź metryki
curl http://localhost:8000/api/schedules/1/metrics

# 4. Zoptymalizuj używając ILP
curl -X POST http://localhost:8000/api/schedules/1/optimize-ilp
```

### Optymalizacja istniejącego harmonogramu:

```bash
# 1. Sprawdź obecny stan
curl http://localhost:8000/api/schedules/1

# 2. Wykonaj optymalizację
curl -X POST http://localhost:8000/api/schedules/1/optimize

# 3. Sprawdź wyniki
curl http://localhost:8000/api/schedules/1/metrics
```

## Obsługa błędów

Wszystkie endpointy zwracają standardowe kody HTTP:

- `200` - Sukces
- `201` - Utworzono
- `400` - Błędne żądanie
- `404` - Nie znaleziono
- `409` - Konflikt
- `500` - Błąd serwera

Przykład błędu:
```json
{
  "success": false,
  "message": "Harmonogram nie został znaleziony"
}
``` 