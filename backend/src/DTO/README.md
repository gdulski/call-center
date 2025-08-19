# DTO (Data Transfer Objects)

Ten katalog zawiera obiekty transferu danych zorganizowane według domeny biznesowej.

## Struktura katalogów

### Schedule/
DTO związane z harmonogramami:
- `ScheduleCreationResponse.php` - odpowiedź po utworzeniu harmonogramu
- `ScheduleDetailsResponse.php` - szczegóły harmonogramu
- `ScheduleGenerationResponse.php` - odpowiedź po wygenerowaniu harmonogramu
- `ScheduleListItemResponse.php` - element listy harmonogramów
- `ScheduleMetricsData.php` - dane metryk harmonogramu
- `ScheduleMetricsResponse.php` - odpowiedź z metrykami harmonogramu
- `ScheduleOptimizationResponse.php` - odpowiedź po optymalizacji
- `ScheduleStatusUpdateResponse.php` - odpowiedź po aktualizacji statusu
- `ScheduleValidationData.php` - dane walidacji harmonogramu
- `ScheduleHeuristicOptimizationResponse.php` - odpowiedź po optymalizacji heurystycznej
- `CreateScheduleRequest.php` - żądanie utworzenia harmonogramu
- `UpdateScheduleStatusRequest.php` - żądanie aktualizacji statusu

### Agent/
DTO związane z agentami:
- `AgentReassignmentPreviewResponse.php` - podgląd ponownego przypisania agenta
- `AgentReassignmentResponse.php` - odpowiedź po ponownym przypisaniu agenta
- `AgentReassignmentRequest.php` - żądanie ponownego przypisania agenta
- `CreateAgentAvailabilityRequest.php` - żądanie utworzenia dostępności agenta
- `UpdateAgentAvailabilityRequest.php` - żądanie aktualizacji dostępności agenta

### User/
DTO związane z użytkownikami:
- `UserRoleResponse.php` - odpowiedź z rolami użytkownika
- `CreateUserRequest.php` - żądanie utworzenia użytkownika
- `UpdateUserRequest.php` - żądanie aktualizacji użytkownika

### QueueType/
DTO związane z typami kolejek:
- `CreateQueueTypeRequest.php` - żądanie utworzenia typu kolejki
- `UpdateQueueTypeRequest.php` - żądanie aktualizacji typu kolejki

## Zasady organizacji

- Każda domena biznesowa ma swój katalog
- DTO są pogrupowane według funkcjonalności
- Nazwy plików są opisowe i wskazują na ich przeznaczenie
- Wszystkie klasy są final i readonly zgodnie z zasadami Symfony
