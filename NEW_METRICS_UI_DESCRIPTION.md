# Nowy interfejs metryk pokrycia rozmów

## Przegląd zmian

Zaktualizowaliśmy interfejs metryk, aby był znacznie bardziej czytelny i opisowy. Teraz zamiast niejasnych liczb `243/280`, użytkownik widzi pełne wyjaśnienie, co oznaczają te wartości.

## Struktura nowego interfejsu

### 1. Podsumowanie pokrycia rozmów (na górze)

```
┌─────────────────────────────────────────────────────────────┐
│ Podsumowanie pokrycia rozmów                               │
├─────────────────────────────────────────────────────────────┤
│ Średnie pokrycie:                    [104.2%]              │
│                                                                 │
│ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ │
│ │     2       │ │     1       │ │     1       │ │     1       │ │
│ │Wystarczające│ │Prawie      │ │Niewystarcz. │ │  Krytyczne │ │
│ └─────────────┘ └─────────────┘ └─────────────┘ └─────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### 2. Opis formatu i interpretacji

```
┌─────────────────────────────────────────────────────────────┐
│ Pokrycie rozmów dzienne                                    │
├─────────────────────────────────────────────────────────────┤
│ Format: Oczekiwane rozmowy / Pojemność agentów (Procent)   │
│                                                                 │
│ Interpretacja:                                               │
│ • ≥ 100% - Wystarczające pokrycie                           │
│ • 80-99% - Prawie wystarczające                             │
│ • 60-79% - Niewystarczające                                 │
│ • < 60% - Krytycznie niewystarczające                       │
└─────────────────────────────────────────────────────────────┘
```

### 3. Szczegółowe metryki dzienne

```
┌─────────────────────────────────────────────────────────────┐
│ 2025-09-01                    Oczekiwane: 100 / Pojemność: 80 │
│                               [125.0%]                        │
├─────────────────────────────────────────────────────────────┤
│ 2025-09-02                    Oczekiwane: 120 / Pojemność: 120│
│                               [100.0%]                        │
├─────────────────────────────────────────────────────────────┤
│ 2025-09-03                    Oczekiwane: 90 / Pojemność: 100 │
│                               [90.0%]                         │
└─────────────────────────────────────────────────────────────┘
```

## Kolory i statusy

### 🟢 Wystarczające (≥ 100%)
- **Kolor**: Zielony (#28a745)
- **Tło**: Jasnozielone (#f8fff8)
- **Opis**: Mamy nadmiar agentów - mogą obsłużyć wszystkie oczekiwane rozmowy + zapas

### 🟡 Lekki niedobór (80-99%)
- **Kolor**: Żółty (#ffc107)
- **Tło**: Jasnożółte (#fffbf0)
- **Opis**: Agenci mogą obsłużyć większość rozmów, ale z małym marginesem

### 🟠 Znaczny niedobór (60-79%)
- **Kolor**: Czerwony (#dc3545)
- **Tło**: Jasnoczerwone (#fff5f5)
- **Opis**: Agenci mogą obsłużyć tylko część rozmów, potrzebne dodatkowe wsparcie

### 🔴 Duży problem (< 60%)
- **Kolor**: Ciemnoczerwony (#dc3545)
- **Tło**: Czerwone (#fff0f0)
- **Opis**: Agenci nie mogą obsłużyć większości rozmów, krytyczna sytuacja wymagająca natychmiastowej interwencji

## Co widzi użytkownik

### Przed zmianami:
```
Pokrycie rozmów dzienne
2025-09-01: 243/280 (86.8%)
2025-09-02: 120/120 (100.0%)
```

**Problem**: Użytkownik nie wie, co oznaczają liczby 243/280

### Po zmianach:
```
Pokrycie rozmów dzienne

Format: Oczekiwane rozmowy / Pojemność agentów (Procent pokrycia)
Interpretacja:
• ≥ 100% - Wystarczające pokrycie (nadmiar agentów)
• 80-99% - Lekki niedobór (prawie wystarczające)
• 60-79% - Znaczny niedobór (niewystarczające)
• < 60% - Duży problem (krytycznie niewystarczające)

2025-09-01                    Oczekiwane: 243 / Pojemność: 280
                               [115.2%]
```

**Korzyść**: Użytkownik od razu rozumie:
- 243 = liczba oczekiwanych rozmów (100% zapotrzebowania)
- 280 = maksymalna liczba rozmów, które agenci mogą obsłużyć (115% zapotrzebowania)
- 115.2% = procent pokrycia (wystarczające - mamy nadmiar agentów)

## Podsumowanie korzyści

✅ **Czytelność**: Jasne etykiety zamiast niejasnych liczb  
✅ **Interpretacja**: Kolorowe wskaźniki statusu  
✅ **Podsumowanie**: Ogólny przegląd na górze  
✅ **Szczegóły**: Pełne wyjaśnienie każdej metryki  
✅ **UX**: Intuicyjne kolory i statusy  
✅ **Planowanie**: Łatwiejsze podejmowanie decyzji  

## Przykład użycia

**Menedżer call center:**
1. **Widzi podsumowanie**: "Średnie pokrycie: 104.2%"
2. **Rozumie status**: "2 dni z nadmiarem agentów, 1 dzień z problemem"
3. **Analizuje szczegóły**: "2025-09-01: 243/280 (115.2%) - wystarczające pokrycie"
4. **Podejmuje decyzję**: "Dzień 1 ma nadmiar agentów, możemy rozważyć zmniejszenie liczby"

**Planista:**
1. **Widzi trend**: Większość dni ma nadmiar agentów
2. **Identyfikuje problemy**: Tylko 1 dzień z niedoborem
3. **Planuje działania**: Może rozważyć zmniejszenie liczby agentów dla lepszej efektywności

Teraz metryki są znacznie bardziej użyteczne i zrozumiałe!
