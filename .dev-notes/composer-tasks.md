# Composer Tasks

## First start.
This is only run for the first time after cloning the repository.

* Make sure that you had ever run `composer install` at the site root where contains **composer.json** file.

## Add, Update packages.
* Write down the package and version in **moduleComposser.json** file.
* Go to site root where contains **composer.json** file.
* Run the command `php rdb system:module update --mname="RdbAdmin"` for update the packages list to main **composer.json**.
* Run the command `composer outdated` for listing outdated packages.
* Run the command `composer update` to update packages.