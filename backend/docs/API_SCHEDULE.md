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
      "queueType": {
        "id": 1,
        "name": "Sprzedaż"
      },
      "weekStartDate": "2024-01-01",
      "weekEndDate": "2024-01-07",
      "weekIdentifier": "2024-W01",
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

### 10. Preview reassignment agenta

**POST** `/api/schedules/{id}/reassignment-preview`

Generuje podgląd zmian przy reassignment agenta bez zapisywania.

**Request Body:**
```json
{
  "agentId": 5,
  "newAvailability": [
    {
      "startDate": "2024-01-01",
      "endDate": "2024-01-03"
    }
  ]
}
```

**Odpowiedź:**
```json
{
  "success": true,
  "data": {
    "conflictingAssignments": [
      {
        "assignmentId": 15,
        "date": "2024-01-02",
        "time": "09:00-17:00",
        "duration": 8.0
      }
    ],
    "potentialReplacements": [
      {
        "agentId": 8,
        "agentName": "Anna Nowak",
        "efficiencyScore": 0.85,
        "availability": "2024-01-02 09:00-17:00"
      }
    ],
    "estimatedImpact": {
      "assignmentsToChange": 3,
      "totalHoursAffected": 24.0
    }
  }
}
```

### 11. Reassignment agenta

**POST** `/api/schedules/{id}/reassign-agent`

Przeprowadza reassignment agenta w harmonogramie.

**Request Body:**
```json
{
  "agentId": 5,
  "newAvailability": [
    {
      "startDate": "2024-01-01",
      "endDate": "2024-01-03"
    }
  ]
}
```

**Odpowiedź:**
```json
{
  "success": true,
  "message": "Pomyślnie zastąpiono 3 przypisań. 0 konfliktów nierozwiązanych.",
  "data": {
    "changes": [
      {
        "assignmentId": 15,
        "oldAgent": {
          "id": 5,
          "name": "Jan Kowalski"
        },
        "newAgent": {
          "id": 8,
          "name": "Anna Nowak"
        },
        "date": "2024-01-02",
        "time": "09:00-17:00",
        "duration": 8.0
      }
    ],
    "unresolvedConflicts": []
  }
}
```

## Zarządzanie dostępnością agentów

### 1. Lista dostępności

**GET** `/api/availability`

Zwraca listę wszystkich dostępności agentów.

**Query Parameters:**
- `agentId` (opcjonalny) - ID konkretnego agenta

**Odpowiedź:**
```json
[
  {
    "id": 1,
    "agent": {
      "id": 5,
      "name": "Jan Kowalski"
    },
    "startDate": "2024-01-01T09:00:00+00:00",
    "endDate": "2024-01-01T17:00:00+00:00"
  }
]
```

### 2. Utworzenie dostępności

**POST** `/api/availability`

Tworzy nową dostępność dla agenta.

**Request Body:**
```json
{
  "agentId": 5,
  "startDate": "2024-01-01T09:00:00Z",
  "endDate": "2024-01-01T17:00:00Z"
}
```

**Odpowiedź:**
```json
{
  "id": 1,
  "agent": {
    "id": 5,
    "name": "Jan Kowalski"
  },
  "startDate": "2024-01-01T09:00:00+00:00",
  "endDate": "2024-01-01T17:00:00+00:00"
}
```

### 3. Szczegóły dostępności

**GET** `/api/availability/{id}`

Zwraca szczegóły konkretnej dostępności.

**Odpowiedź:**
```json
{
  "id": 1,
  "agent": {
    "id": 5,
    "name": "Jan Kowalski"
  },
  "startDate": "2024-01-01T09:00:00+00:00",
  "endDate": "2024-01-01T17:00:00+00:00"
}
```

### 4. Aktualizacja dostępności

**PUT** `/api/availability/{id}`

Aktualizuje istniejącą dostępność.

**Request Body:**
```json
{
  "startDate": "2024-01-01T10:00:00Z",
  "endDate": "2024-01-01T18:00:00Z"
}
```

### 5. Usunięcie dostępności

**DELETE** `/api/availability/{id}`

Usuwa dostępność agenta.

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

### 3. Reassignment agentów

System reassignment umożliwia dynamiczną zmianę dostępności agentów:

1. **Identyfikacja konfliktów:**
   - Sprawdzenie nakładających się przypisań
   - Weryfikacja nowej dostępności

2. **Znalezienie zastępców:**
   - Wyszukiwanie dostępnych agentów
   - Sprawdzenie umiejętności i efektywności
   - Optymalizacja wyboru zastępcy

3. **Wykonanie zmian:**
   - Przeprowadzenie reassignment
   - Aktualizacja harmonogramu
   - Logowanie zmian

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

### Reassignment agenta:

```bash
# 1. Sprawdź preview reassignment
curl -X POST http://localhost:8000/api/schedules/1/reassignment-preview \
  -H "Content-Type: application/json" \
  -d '{"agentId": 5, "newAvailability": [{"startDate": "2024-01-01", "endDate": "2024-01-03"}]}'

# 2. Wykonaj reassignment
curl -X POST http://localhost:8000/api/schedules/1/reassign-agent \
  -H "Content-Type: application/json" \
  -d '{"agentId": 5, "newAvailability": [{"startDate": "2024-01-01", "endDate": "2024-01-03"}]}'
```

### Zarządzanie dostępnością:

```bash
# 1. Dodaj dostępność agenta
curl -X POST http://localhost:8000/api/availability \
  -H "Content-Type: application/json" \
  -d '{"agentId": 5, "startDate": "2024-01-01T09:00:00Z", "endDate": "2024-01-01T17:00:00Z"}'

# 2. Sprawdź dostępności agenta
curl "http://localhost:8000/api/availability?agentId=5"
```

## Obsługa błędów

Wszystkie endpointy zwracają standardowe kody HTTP:

- `200` - Sukces
- `201` - Utworzono
- `400` - Błędne żądanie
- `404` - Nie znaleziono
- `409` - Konflikt (np. nakładające się dostępności)
- `500` - Błąd serwera

Przykład błędu:
```json
{
  "success": false,
  "message": "Harmonogram nie został znaleziony"
}
```

Przykład błędu konfliktu:
```json
{
  "error": "Availability period overlaps with existing period"
}
``` 