php bin/console cache:clear
exit
printenv | grep MONGODB_URL
printenv | grep DATABASE_URL
printenv | grep APP_ENV
exit
printenv | grep MONGODB_URL
printenv | grep DATABASE_URL
exit
php bin/console debug:config framework --env=test
cat config/packages/test/framework.yaml
php bin/console cache:clear --env=test
ls -l /var/www/config/packages/test/
php bin/console cache:clear --env=test
exit
cd /var/www
./vendor/bin/phpunit tests/Controller/HomeControllerTest.php
exit
cd /var/www/html
php bin/console doctrine:database:create --env=test
pwd
ls
cd /var/www
exit
cd /var/www
php bin/console doctrine:database:create --env=test
php bin/console doctrine:database:verify --env=test
php bin/console doctrine:schema:validate --env=test
php bin/console doctrine:mongodb:generate:proxies --env=test
php bin/console doctrine:mongodb:query App\\Document\\Article find --env=test
exit
