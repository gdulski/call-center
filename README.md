# Call Center Management System

Kompletny system zarządzania call center zbudowany na Symfony API i React frontend.

## Architektura

- **Backend**: Symfony 6.3 API
- **Frontend**: React 18 z React Router
- **Baza danych**: MySQL 8.0
- **Cache**: Redis
- **Konteneryzacja**: Docker & Docker Compose

## Struktura projektu

```
call-center/
├── backend/                 # Symfony API
│   ├── src/
│   │   ├── Controller/     # Kontrolery API
│   │   ├── Entity/         # Encje Doctrine
│   │   ├── Repository/     # Repozytoria
│   │   └── Service/        # Serwisy biznesowe
│   ├── config/             # Konfiguracja Symfony
│   └── public/             # Publiczne pliki
├── frontend/               # React aplikacja
│   ├── src/
│   │   ├── components/     # Komponenty React
│   │   ├── pages/          # Strony aplikacji
│   │   ├── services/       # Serwisy API
│   │   └── utils/          # Narzędzia
│   └── public/             # Publiczne pliki
└── docker-compose.yml      # Konfiguracja Docker
```

## Wymagania

- Docker
- Docker Compose
- Node.js 18+ (dla lokalnego rozwoju frontendu)
- PHP 8.2+ (dla lokalnego rozwoju backendu)

## Szybkie uruchomienie

1. **Sklonuj repozytorium:**
   ```bash
   git clone <repository-url>
   cd call-center
   ```

2. **Uruchom środowisko Docker:**
   ```bash
   docker-compose up -d
   ```

3. **Zainstaluj zależności Symfony (w kontenerze):**
   ```bash
   docker-compose exec php composer install
   ```

4. **Zainstaluj zależności React (w kontenerze):**
   ```bash
   docker-compose exec frontend npm install
   ```

5. **Uruchom migracje bazy danych:**
   ```bash
   docker-compose exec php php bin/console doctrine:migrations:migrate
   ```

## Dostęp do aplikacji

- **Frontend React**: http://localhost:3000
- **Backend API**: http://localhost:8000
- **API Health Check**: http://localhost:8000/api/health
- **MySQL**: localhost:3306
- **Redis**: localhost:6379

## Rozwój lokalny

### Backend (Symfony)

```bash
# Wejdź do kontenera PHP
docker-compose exec php bash

# Utwórz nowy kontroler
php bin/console make:controller Api/NewController

# Utwórz nową encję
php bin/console make:entity NewEntity

# Utwórz migrację
php bin/console make:migration

# Uruchom migracje
php bin/console doctrine:migrations:migrate
```

### Frontend (React)

```bash
# Wejdź do kontenera frontend
docker-compose exec frontend bash

# Utwórz nowy komponent
mkdir src/components/NewComponent
touch src/components/NewComponent/NewComponent.js

# Uruchom testy
npm test
```

## API Endpoints

### Health Check
- `GET /api/health` - Status API

### Przykładowe endpointy do implementacji:
- `GET /api/calls` - Lista połączeń
- `POST /api/calls` - Utwórz nowe połączenie
- `GET /api/agents` - Lista agentów
- `POST /api/agents` - Utwórz nowego agenta

## Zmienne środowiskowe

Utwórz plik `.env` w katalogu `backend/`:

```env
APP_ENV=dev
APP_SECRET=your-secret-key-here
DATABASE_URL="mysql://root:password@mysql:3306/call_center?serverVersion=8.0&charset=utf8mb4"
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
```

## Struktura bazy danych

Główne tabele do implementacji:
- `agents` - Agenci call center
- `calls` - Połączenia telefoniczne
- `customers` - Klienci
- `call_logs` - Logi połączeń

## Kontrybucja

1. Fork projektu
2. Utwórz branch dla nowej funkcjonalności
3. Commit zmiany
4. Push do branch
5. Utwórz Pull Request

## Licencja

MIT License 