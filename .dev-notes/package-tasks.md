# Package Tasks

Describe what to do in each task, step by step.

## First start.
This is only run for the first time after cloning the repository.

* Make sure that you had ever run `npm install` by check at folder **node_modules** must be exists.

### Before start development.
This should be run every time before start modify the files.

* Manually delete **.backup** folder.

## Update packages.
* Run the command `npm outdated` for listing outdated packages.
* Run the command `npm update` to update packages or `npm install [packagename]@latest` to install latest for major version.
* Run the command `npm run build` to do following tasks.
 * Delete **assets** folders.<br>
 * Copy packages from **node_modules** to **assets** folders.<br>
 * Bundle package files.<br>
 * Copy everything from **assets** folder to **public/Modules/RdbAdmin/assets** folder.

### Update version number of packages.
#### Automatic update.
* Run the command `npm run writeVersions` to write the packages version into **ModuleData/ModuleAssets.php** file.
* Open files in **ModuleData/ModuleAssets.php**, **.backup/ModuleData/ModuleAssets.backupxxx.php** where xxx is the date/time of running command.<br>
    Then compare these 2 files to make sure that only version number just changed, otherwise incorrect PHP syntax may cause the website error.

#### Manual update.
Update the version number on these files.

* Update RDTA version at **ModuleData/ModuleAssets.php** inside `getModuleAssets()`method.
* Update DataTables version at **ModuleData/ModuleAssets.php** inside `getModuleAssets()`method.
* Update Handlebars version at **ModuleData/ModuleAssets.php** inside `getModuleAssets()`method.

## Editing files.
To edit files in **assets-src** folder such as CSS, JS, please run the following command to make it watch and copy automatically.

* Run the command `npm run watch` to automatic copy asset files from **assets-src** to **assets/** and **public/** folder.

## Before publish or commit.
* Update version number in **Installer.php** file.
* Run command `phpdoc2` or using phpDocumentor 2. (API doc is no need to generate every commit, just when there are changes on release.)
* Run update version number again from the command in section **Update version number of packages**.
* Run external pack command `rdbdev pack --module framework --packDev --packProd` to pack files and folders into a zip file.