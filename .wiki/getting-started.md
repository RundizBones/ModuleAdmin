# Getting started.

## Installation

* Extract files in **Modules/RdbAdmin** inside RundizBones framework installation location.
* Make sure that the database configuration of your framework are configured correctly.
* Open command prompt program.
* Go to RundizBones root folder (where contain **composer.default.json** and **composer.json**).
* Run the command `php rdb system:module install --mname="RdbAdmin"`.
* Wait until module installation finished and then run the command `composer update`.
* Open web browser to your framework URL and follow with **/admin**. Example: http://localhost.localhost/admin.
* If you installed correctly, it should showing up the login page.
* Enter `admin` for username and `pass` for password for the first time.

### Customize

Open each configuration file inside **config/default** folder. Read carefully one by one.

**WARNING!** Do not write anything into files inside this folder because it will be overwrite when updated.<br>
Copy the file that you want to modify to **config/`APP_ENV`** where `APP_ENV` is the environment that you defined in the **index.php** file of the framework. 
Example: your `APP_ENV` is `production` then copy to **config/production**.