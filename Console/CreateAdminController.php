<?php
/**
 * Create an admin controller for development via CLI.
 * 
 * This file can be deleted on production site.
 * 
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Modules\RdbAdmin\Console;


use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Style\SymfonyStyle;


/**
 * RdbAdmin create an admin controller CLI.
 * 
 * @since 0.1
 */
class CreateAdminController extends \System\Core\Console\BaseConsole
{


    /**
     * @var \System\Libraries\FileSystem
     */
    private $Fs;


    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('rdbadmin:create-adminct')
            ->setDescription('Create an admin controller that is compatible with RdbAdmin module.')
            ->setHelp(
                'Create an admin controller for development.' . "\n" .
                'The admin controller must be in an existing module and the module has to compatible with the RdbAdmin module.' . "\n" .
                'The controller that will be creating will extend the "\Modules\RdbAdmin\Controllers\Admin\AdminBaseController" controller.' . "\n" .
                'The controller name is start related to "<your module>/Controllers" folder.' . "\n" .
                'If controller name you specified is already exists, it will show the error and not be created.' . "\n\n" .
                'Example:' . "\n" .
                '  php rdb rdbadmin:create-adminct "ModuleName" "Admin\Contact\Page"' . "\n" .
                '    This will create an admin controller file in "Modules/ModuleName/Controllers/Admin/Contact/PageController.php".'
            )
            ->addArgument('moduleName', InputArgument::REQUIRED, 'The module system name (folder name) that you want to work with. This is case sensitive.')
            ->addArgument('controllerName', InputArgument::REQUIRED, 'The controller name without "Controller" suffix. Use "StudlyCaps" as PSR-1 rules.')
        ;
    }// configure


    /**
     * Copy file.
     * 
     * @param string $sourceFile Full path to source file.
     * @param string $controllerFullPath Full path to new controller name.
     * @param OutputInterface $Output
     * @param SymfonyStyle $Io
     * @return bool Return `true` on success, `false` for otherwise.
     */
    private function copyFile(string $sourceFile, string $controllerFullPath, OutputInterface $Output, SymfonyStyle $Io): bool
    {
        $copyResult = copy($sourceFile, $controllerFullPath);

        if ($copyResult === true) {
            $Output->writeln('Controller was created successfully. (' . $controllerFullPath . '.)');
            $Output->writeln('');
        } else {
            $Io->warning('Unable to create controller. (' . $controllerFullPath . '.)');
        }

        return $copyResult;
    }// copyFile


    /**
     * Create controller parent folder if not exists.
     * 
     * @param string $controllerRelatePath Controller related path that has got from `prepareFileName()` method.
     * @param OutputInterface $Output
     * @param SymfonyStyle $Io
     * @return bool Return `true` if created successfully or folder is already exists, return `false` if failed to create.
     */
    private function createControllerParentFolderIfNotExists(string $controllerRelatePath, OutputInterface $Output, SymfonyStyle $Io): bool
    {
        $controllerFullPath = MODULE_PATH . DIRECTORY_SEPARATOR . $controllerRelatePath;
        $controllerParentFolder = dirname($controllerFullPath);
        unset($controllerFullPath);

        if (!is_dir($controllerParentFolder)) {
            $controllerParentFolderRelated = dirname($controllerRelatePath);
            $Fs = new \System\Libraries\FileSystem(MODULE_PATH);
            $createFolderResult = $Fs->createFolder($controllerParentFolderRelated);
            unset($controllerParentFolderRelated, $Fs);

            if ($createFolderResult === false) {
                unset($createFolderResult);
                $Io->error('Unable to create controller folder for "' . $controllerParentFolder . '".');
                return false;
            }
        }

        unset($controllerParentFolder);

        return true;
    }// createControllerParentFolderIfNotExists


    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $Input, OutputInterface $Output)
    {
        $Io = new SymfonyStyle($Input, $Output);
        $Io->title('Create an admin controller');

        $moduleName = $Input->getArgument('moduleName');
        $controllerName = $Input->getArgument('controllerName');

        // validate. -------------------------------------------------------------------------------------
        $validated = true;
        if (empty(trim($moduleName))) {
            $Io->error('Please enter module name.');
            $validated = false;
        }
        if (!is_dir(MODULE_PATH . DIRECTORY_SEPARATOR . $moduleName)) {
            $Io->error('The module ' . $moduleName . ' is not exists.');
            $validated = false;
        }

        if (empty(trim($controllerName))) {
            $Io->error('Please enter controller name.');
            $validated = false;
        }
        if (!preg_match('#^([A-Z])(.+)$#', $controllerName)) {
            $Io->error('Invalid controller name. It must be "StudlyCaps" or start with first character upper case to make PSR-4 auto loading works.');
            $validated = false;
        }
        if (!preg_match('#^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*(\\\\[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)*$#', $controllerName)) {
            // if controller name contain disallowed characters.
            // @link https://www.php.net/manual/en/language.oop5.basic.php Regular expression pattern reference.
            $Io->error('Invalid controller name. The controller name contain disallowed characters.');
            $validated = false;
        }

        if ($validated === true) {
            $controllerRelatePath = $this->prepareFileName($moduleName, $controllerName);
            $controllerFullPath = MODULE_PATH . DIRECTORY_SEPARATOR . $controllerRelatePath;
            if (is_file($controllerFullPath)) {
                $Io->error(
                    'The specified controller file is already exists.' .
                    '(' . $controllerFullPath . ').' .
                    'Please enter new controller name.'
                );
                $validated = false;
            }
        }
        // end validate. --------------------------------------------------------------------------------

        if ($validated === true) {
            $questionMsg = 'Your controller will be create on this location: "' . $controllerFullPath . '".' . "\n" .
                'Are you sure?' . "\n" .
                '(y, n) - default is n.';
            $answer = $Io->ask($questionMsg, 'n');
            unset($questionMsg);

            if (!preg_match('#^y#i', $answer)) {
                return ;
            } else {
                $sourceFile = realpath(__DIR__ . '/CreateModuleTemplate/Controllers/Admin/IndexController.pht');
                if (!is_file($sourceFile) || !is_string($sourceFile) || empty($sourceFile)) {
                    $Io->error('Unable to find source controller file.');
                    return ;
                }

                // create controller parent folder if not exists.
                if ($this->createControllerParentFolderIfNotExists($controllerRelatePath, $Output, $Io) === false) {
                    return ;
                }

                // copy controller template to destination.
                if ($this->copyFile($sourceFile, $controllerFullPath, $Output, $Io) === false) {
                    return ;
                }

                if ($this->rewriteContents($moduleName, $controllerRelatePath, $Output, $Io) === true) {
                    $Io->success('Finished. You can start write your controller at ' . $controllerFullPath . '.');
                }
            }

            unset($answer);
        }

        unset($controllerFullPath, $controllerName, $controllerRelatePath, $moduleName, $validated);

        unset($Io);
    }// execute


    /**
     * Prepare file name related from "Modules" folder.
     * 
     * @param string $moduleName The module name.
     * @param string $controllerName The controller name without suffix and extension.
     * @return string Return related path to controller from Modules folder.
     */
    private function prepareFileName(string $moduleName, string $controllerName): string
    {
        $controllerName = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $controllerName);
        $controllerName .= 'Controller.php';

        return $moduleName . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . $controllerName;
    }// prepareFileName


    /**
     * Rewrite file contents.
     * 
     * @param string $moduleName The module folder name.
     * @param string $controllerRelatePath Controller relate path that has got from `prepareFileName()` method.
     * @param OutputInterface $Output
     * @param SymfonyStyle $Io
     * @return bool Return `true` on success, `false` on failure.
     */
    private function rewriteContents(string $moduleName, string $controllerRelatePath, OutputInterface $Output, SymfonyStyle $Io): bool
    {
        $controllerFullPath = MODULE_PATH . DIRECTORY_SEPARATOR . $controllerRelatePath;

        if (is_file($controllerFullPath)) {
            $controllerFullClass = 'Modules\\' . str_replace('.php', '', $controllerRelatePath);
            $controllerNamespace = dirname($controllerFullClass);
            $controllerClass = str_replace($controllerNamespace . '\\', '', $controllerFullClass);

            // get original contents.
            $fileContents = file_get_contents($controllerFullPath);
            // replace contens. -----------------------------------------------------------------------------------------
            // replace placeholders.
            $fileContents = str_replace(['%ModuleName%', '%modulename%'], [$moduleName, strtolower($moduleName)], $fileContents);
            // replace namespace.
            $fileContents = preg_replace('#^namespace[\s]+(.+);#m', 'namespace ' . $controllerNamespace . ';', $fileContents);
            // replace class.
            $fileContents = preg_replace('#^class[\s]+([a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)#m', 'class ' . $controllerClass, $fileContents);

            unset($controllerClass, $controllerFullClass, $controllerNamespace);
            // end replace contens. ------------------------------------------------------------------------------------

            $Fs = new \System\Libraries\FileSystem(MODULE_PATH);
            $writeResult = $Fs->writeFile($controllerRelatePath, $fileContents);
            unset($Fs);

            if ($writeResult !== false) {
                $Output->writeln('The controller file has been created successfully. (' . $controllerFullPath . '.)');
                $return = true;
            } else {
                $Io->warning('The controller file was unable to modify template contents. (' . $controllerFullPath . '.)');
                $return = false;
            }

            unset($fileContents, $writeResult);
        } else {
            // if file was not found.
            if (!is_dir($controllerFullPath)) {
                // if file is not even a folder, it is really not found.
                $Io->error('Controller was not found. (' . $controllerFullPath . '.)');
                return false;
            }
        }

        unset($controllerFullPath);

        return ($return ?? false);
    }// rewriteContents


}
