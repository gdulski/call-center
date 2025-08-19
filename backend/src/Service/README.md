# Service (Serwisy)

Ten katalog zawiera serwisy biznesowe zorganizowane według domeny biznesowej.

## Struktura katalogów

### Schedule/
Serwisy związane z harmonogramami:
- `ScheduleService.php` - główny serwis zarządzający harmonogramami
- `ScheduleGenerationService.php` - serwis generowania harmonogramów
- `ScheduleValidationService.php` - serwis walidacji harmonogramów
- `ILPOptimizationService.php` - serwis optymalizacji ILP (Integer Linear Programming)

### Agent/
Serwisy związane z agentami:
- `AgentReassignmentService.php` - serwis ponownego przypisania agentów
- `AgentAvailabilityService.php` - serwis zarządzania dostępnością agentów
- `AgentAvailabilityValidationService.php` - serwis walidacji dostępności agentów

### User/
Serwisy związane z użytkownikami:
- `UserService.php` - główny serwis zarządzania użytkownikami
- `UserValidationService.php` - serwis walidacji użytkowników

### QueueType/
Serwisy związane z typami kolejek:
- `QueueTypeService.php` - główny serwis zarządzania typami kolejek
- `QueueTypeValidationService.php` - serwis walidacji typów kolejek

## Zasady organizacji

- Każda domena biznesowa ma swój katalog
- Serwisy są pogrupowane według funkcjonalności
- Nazwy plików są opisowe i wskazują na ich przeznaczenie
- Wszystkie klasy są final zgodnie z zasadami Symfony
- Serwisy implementują logikę biznesową i są wstrzykiwane do kontrolerów

## Wzorce projektowe

- **Service Pattern** - logika biznesowa jest wydzielona do serwisów
- **Repository Pattern** - dostęp do danych przez repozytoria
- **Dependency Injection** - serwisy są wstrzykiwane przez konstruktor
- **Single Responsibility Principle** - każdy serwis ma jedną odpowiedzialność
