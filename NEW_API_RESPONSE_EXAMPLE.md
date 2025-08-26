# Przykład nowej odpowiedzi API z metrykami callCoverage

## Endpoint
```
GET http://localhost:8000/api/schedules/87/metrics
```

## Nowa odpowiedź (z callCoverage)

```json
{
    "success": true,
    "data": {
        "metrics": {
            "totalHours": 60,
            "agentCount": 2,
            "averageHoursPerAgent": 30,
            "maxHoursPerAgent": 40,
            "minHoursPerAgent": 20,
            "callCoverage": {
                "2025-09-01": {
                    "expectedCalls": 100,
                    "agentCapacity": 80,
                    "coverage": 125.0
                },
                "2025-09-02": {
                    "expectedCalls": 120,
                    "agentCapacity": 120,
                    "coverage": 100.0
                },
                "2025-09-03": {
                    "expectedCalls": 90,
                    "agentCapacity": 100,
                    "coverage": 90.0
                },
                "2025-09-04": {
                    "expectedCalls": 150,
                    "agentCapacity": 120,
                    "coverage": 125.0
                },
                "2025-09-05": {
                    "expectedCalls": 80,
                    "agentCapacity": 100,
                    "coverage": 80.0
                }
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

**Uwaga**: Frontend samodzielnie formatuje wyświetlanie metryk w formacie "100/80 (125.0%)" na podstawie danych `expectedCalls`, `agentCapacity` i `coverage`.

## Stara odpowiedź (z hourlyCoverage) - do porównania

```json
{
    "success": true,
    "data": {
        "metrics": {
            "totalHours": 60,
            "agentCount": 2,
            "averageHoursPerAgent": 30,
            "maxHoursPerAgent": 40,
            "minHoursPerAgent": 20,
            "hourlyCoverage": {
                "2025-09-01 09:00": 12,
                "2025-09-02 09:00": 12,
                "2025-09-03 09:00": 12,
                "2025-09-04 09:00": 12,
                "2025-09-05 09:00": 12
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

## Analiza nowych metryk

### Dzień 1 (2025-09-01)
- **Oczekiwane rozmowy**: 100
- **Pojemność agentów**: 80 rozmów
- **Pokrycie**: 125% (nadmierne pokrycie)
- **Interpretacja**: Mamy więcej agentów niż potrzeba, można rozważyć zmniejszenie liczby

### Dzień 2 (2025-09-02)
- **Oczekiwane rozmowy**: 120
- **Pojemność agentów**: 120 rozmów
- **Pokrycie**: 100% (idealne pokrycie)
- **Interpretacja**: Liczba agentów jest idealnie dopasowana do oczekiwań

### Dzień 3 (2025-09-03)
- **Oczekiwane rozmowy**: 90
- **Pojemność agentów**: 100 rozmów
- **Pokrycie**: 90% (prawie wystarczające)
- **Interpretacja**: Lekko nadmierne pokrycie, ale akceptowalne

### Dzień 4 (2025-09-04)
- **Oczekiwane rozmowy**: 150
- **Pojemność agentów**: 120 rozmów
- **Pokrycie**: 125% (nadmierne pokrycie)
- **Interpretacja**: Mamy więcej agentów niż potrzeba

### Dzień 5 (2025-09-05)
- **Oczekiwane rozmowy**: 80
- **Pojemność agentów**: 100 rozmów
- **Pokrycie**: 80% (prawie wystarczające)
- **Interpretacja**: Lekko nadmierne pokrycie

## Podsumowanie analizy

### Statystyki ogólne:
- **Średnie pokrycie**: 104% (lekko nadmierne)
- **Dni z nadmiernym pokryciem**: 3/5 (60%)
- **Dni z idealnym pokryciem**: 1/5 (20%)
- **Dni z niewystarczającym pokryciem**: 0/5 (0%)

### Rekomendacje:
1. **Dzień 1**: Rozważ zmniejszenie liczby agentów
2. **Dzień 4**: Rozważ zmniejszenie liczby agentów
3. **Dzień 2**: Zachowaj obecną liczbę agentów
4. **Dni 3 i 5**: Można rozważyć lekkie zmniejszenie

### Korzyści z nowych metryk:
- ✅ **Widoczność**: Wiesz dokładnie, ile rozmów możesz obsłużyć
- ✅ **Planowanie**: Możesz optymalizować liczbę agentów
- ✅ **Koszty**: Unikasz nadmiernego zatrudnienia
- ✅ **Jakość**: Zapewniasz odpowiednie pokrycie rozmów
