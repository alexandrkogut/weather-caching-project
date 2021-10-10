docker-compose up -d
docker-compose exec php composer install
yes | docker-compose exec -T php bin/console doctrine:migrations:migrate