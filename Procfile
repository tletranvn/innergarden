web: source bin/heroku-setup.sh && vendor/bin/heroku-php-apache2 public/
release: php bin/console doctrine:migrations:migrate --no-interaction
