#!/bin/bash

echo "🚀 Uruchamianie Call Center Management System..."

# Sprawdź czy Docker jest uruchomiony
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker nie jest uruchomiony. Uruchom Docker i spróbuj ponownie."
    exit 1
fi

# Zatrzymaj istniejące kontenery
echo "🛑 Zatrzymywanie istniejących kontenerów..."
docker-compose down

# Uruchom kontenery
echo "🐳 Uruchamianie kontenerów Docker..."
docker-compose up -d

# Czekaj na uruchomienie bazy danych
echo "⏳ Oczekiwanie na uruchomienie bazy danych..."
sleep 10

# Sprawdź czy kontenery są uruchomione
if docker-compose ps | grep -q "Up"; then
    echo "✅ Kontenery uruchomione pomyślnie!"
    
    echo ""
    echo "📋 Dostępne usługi:"
    echo "   🌐 Frontend React: http://localhost:3000"
    echo "   🔧 Backend API: http://localhost:8000"
    echo "   💚 Health Check: http://localhost:8000/api/health"
    echo "   🗄️  MySQL: localhost:3306"
    echo "   🔴 Redis: localhost:6379"
    echo ""
    echo "🔧 Następne kroki:"
    echo "   1. Zainstaluj zależności Symfony: docker-compose exec php composer install"
    echo "   2. Zainstaluj zależności React: docker-compose exec frontend npm install"
    echo "   3. Uruchom migracje: docker-compose exec php php bin/console doctrine:migrations:migrate"
    echo ""
    echo "📖 Więcej informacji w pliku README.md"
else
    echo "❌ Błąd podczas uruchamiania kontenerów"
    docker-compose logs
    exit 1
fi 