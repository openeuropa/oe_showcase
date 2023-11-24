git stash
git checkout 1.x
docker-compose exec web ./vendor/bin/run drupal:site-install
git checkout OEL-2541
git stash apply
docker-compose exec web drush updb -y
docker-compose exec web drush cr
docker-compose exec web ./vendor/bin/phpunit --testdox  modules/oe_showcase_search/
