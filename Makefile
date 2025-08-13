.PHONY: help start start-server stop restart build install test clean logs clean-docker routes

help: ## Pokaż pomoc
	@echo "Dostępne komendy:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

start-server: ## Uruchom serwer Symfony w kontenerze PHP
	@echo "🚀 Uruchamianie serwera Symfony..."
	docker-compose exec php symfony server:start --listen-ip=0.0.0.0 --port=8000 --no-tls --no-interaction --allow-http --daemon

start: ## Uruchom środowisko deweloperskie
	@echo "🚀 Uruchamianie Call Center Management System..."
	@if lsof -Pi :8000 -sTCP:LISTEN -t >/dev/null 2>&1; then \
		echo "⚠️  Port 8000 jest zajęty. Zatrzymuję..."; \
		lsof -ti:8000 | xargs kill -9 2>/dev/null || true; \
		sleep 2; \
	fi
	docker-compose up -d
	@echo "✅ Środowisko uruchomione!"
	@echo "🌐 Frontend: http://localhost:3000"
	@echo "🔧 Backend: http://localhost:8000"
	@echo "⚠️  Pamiętaj: Uruchom 'make start-server' aby uruchomić serwer Symfony"

stop: ## Zatrzymaj środowisko
	@echo "🛑 Zatrzymywanie środowiska..."
	docker-compose down

restart: stop start ## Restart środowiska

build: ## Zbuduj obrazy Docker
	@echo "🔨 Budowanie obrazów Docker..."
	docker-compose build

install: ## Zainstaluj zależności
	@echo "📦 Instalowanie zależności Symfony..."
	docker-compose exec php composer install
	@echo "📦 Instalowanie zależności React..."
	docker-compose exec frontend npm install

test: ## Uruchom testy
	@echo "🧪 Uruchamianie testów..."
	docker-compose exec php php bin/phpunit
	docker-compose exec frontend npm test

logs: ## Pokaż logi kontenerów
	docker-compose logs -f

clean: ## Wyczyść cache i logi
	@echo "🧹 Czyszczenie cache..."
	docker-compose exec php php bin/console cache:clear
	@echo "🧹 Czyszczenie logów..."
	docker-compose exec php rm -rf var/log/*

migrate: ## Uruchom migracje bazy danych
	@echo "🗄️ Uruchamianie migracji..."
	docker-compose exec php php bin/console doctrine:migrations:migrate

shell-php: ## Otwórz shell PHP
	docker-compose exec php bash

shell-frontend: ## Otwórz shell frontend
	docker-compose exec frontend bash

shell-mysql: ## Otwórz shell MySQL
	docker-compose exec mysql mysql -u root -ppassword call_center

status: ## Pokaż status kontenerów
	docker-compose ps 

clean-docker: ## Wyczyść Docker śmieci, orphany i nieużywane zasoby
	@if docker-compose ps | grep -q "Up"; then \
		echo "❌ Kontenery działają! Uruchom 'make stop' przed czyszczeniem."; \
		exit 1; \
	fi
	@echo "🧹 Czyszczenie Docker śmieci i orphanów..."
	@echo "🗑️  Usuwanie zatrzymanych kontenerów..."
	docker container prune -f
	@echo "🗑️  Usuwanie nieużywanych obrazów..."
	docker image prune -f
	@echo "🗑️  Usuwanie nieużywanych wolumenów..."
	docker volume prune -f
	@echo "🗑️  Usuwanie nieużywanych sieci..."
	docker network prune -f
	@echo "🧹 Usuwanie wszystkich nieużywanych zasobów..."
	docker system prune -f
	@echo "✅ Docker wyczyszczony!" 

routes: ## Pokaż wszystkie routy Symfony
	@echo "🗺️  Wyświetlanie routingu Symfony..."
	docker-compose exec php php bin/console debug:router 