<?php
/**
 * Create a module for development via CLI.
 * 
 * This file can be deleted on production site.
 * 
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Console;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Style\SymfonyStyle;


/**
 * RdbAdmin create a module CLI.
 * 
 * @since 0.1
 */
class CreateModule extends \Rdb\System\Core\Console\BaseConsole
{


    /**
     * @var \Rdb\System\Libraries\FileSystem
     */
    private $Fs;


    /**
     * @var string
     */
    private $moduleName;


    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('rdbadmin:create-module')
            ->setDescription('Create a module that is compatible with RdbAdmin module.')
            ->setHelp(
                'Create a module for development with example folders and files such as assets, config, Controllers, languages, ModuleData, Tests, Views, Installer.php, moduleComposer.json.'
            )
            ->addArgument('moduleName', InputArgument::REQUIRED, 'The module system name (folder name) to create. Use "StudlyCaps" as PSR-1 rules.')
            ->addOption('noassets', null, InputOption::VALUE_NONE, 'Add this option to not create assets folder.')
            ->addOption('notests', null, InputOption::VALUE_NONE, 'Add this option to not create Tests folder.')
            ->addOption('nocomposer', null, InputOption::VALUE_NONE, 'Add this option to not create moduleComposer.json file.')
        ;
    }// configure


    /**
     * Copy file.
     * 
     * @param string $fileName File name.
     * @param OutputInterface $Output
     * @param SymfonyStyle $Io
     * @return bool Return `true` on success, `false` for otherwise.
     */
    private function copyFile(string $fileName, OutputInterface $Output, SymfonyStyle $Io): bool
    {
        $sourceTarget = __DIR__ . DIRECTORY_SEPARATOR . 'CreateModuleTemplate' . DIRECTORY_SEPARATOR . $fileName;

        if (!is_file($sourceTarget) && !file_exists($sourceTarget)) {
            $Io->error('Source file ' . $sourceTarget . ' was not found.');
            return false;
        }

        $destinationTarget = MODULE_PATH . DIRECTORY_SEPARATOR . $this->moduleName . DIRECTORY_SEPARATOR . $fileName;
        $copyResult = copy($sourceTarget, $destinationTarget);
        unset($destinationTarget, $sourceTarget);

        if ($copyResult === true) {
            $Output->writeln('Copy ' . $fileName . ' successfully.');
        } else {
            $Io->warning('Unable to copy ' . $fileName . '.');
        }

        return $copyResult;
    }// copyFile


    /**
     * Copy folder recursive.
     * 
     * @param string $folderName Folder name.
     * @param OutputInterface $Output
     * @param SymfonyStyle $Io
     * @return bool Return `true` on success, `false` for otherwise.
     */
    private function copyFolder(string $folderName, OutputInterface $Output, SymfonyStyle $Io): bool
    {
        $sourceTarget = __DIR__ . DIRECTORY_SEPARATOR . 'CreateModuleTemplate' . DIRECTORY_SEPARATOR . $folderName;
        $destinationTarget = $this->moduleName . DIRECTORY_SEPARATOR . $folderName;
        $copyResult = $this->Fs->copyFolderRecursive($sourceTarget, $destinationTarget);
        unset($destinationTarget, $sourceTarget);

        if ($copyResult === true) {
            $Output->writeln('Copy ' . $folderName . ' folder successfully.');
        } else {
            $Io->warning('Unable to copy ' . $folderName . ' folder.');
        }

        return $copyResult;
    }// copyFolder


    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $Input, OutputInterface $Output)
    {
        $Io = new SymfonyStyle($Input, $Output);
        $Io->title('Create a module');

        // validate module name
        $this->moduleName = $Input->getArgument('moduleName');
        $validated = true;
        if (!preg_match('#^([A-Z])(.+)$#', $this->moduleName)) {
            $Io->error('Invalid module name. It must be "StudlyCaps" or start with first character upper case to make PSR-4 auto loading works.');
            $validated = false;
        }
        if (preg_match('#([\s]+)#', $this->moduleName)) {
            $Io->error('Invalid module name. The module name cannot contain space.');
            $validated = false;
        }
        if (!preg_match('#^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$#', $this->moduleName)) {
            // if module name contain disallowed characters.
            // @link https://www.php.net/manual/en/language.oop5.basic.php Regular expression pattern reference.
            $Io->error('Invalid module name. The module name contain disallowed characters.');
            $validated = false;
        }

        if ($validated === true) {
            $this->Fs = new \Rdb\System\Libraries\FileSystem(MODULE_PATH);
            $createModuleFolder = $this->Fs->createFolder($this->moduleName);

            if ($createModuleFolder === true) {
                $Output->writeln('Module folder created');
                $Output->writeln('');
            } else {
                $Io->error('Unable to create module folder. Please check "Modules" folder permission.');
                $validated = false;
            }

            unset($createModuleFolder);
        }

        if ($validated === true) {
            $this->executeCopyRequiredFiles($Input, $Output, $Io);

            if (!$Input->getOption('noassets')) {
                $this->copyFolder('assets', $Output, $Io);
                $Output->writeln('');
            }
            if (!$Input->getOption('notests')) {
                $this->executeCopyTests($Input, $Output, $Io);
                $Output->writeln('');
            }
            if (!$Input->getOption('nocomposer')) {
                $this->copyFile('moduleComposer.json', $Output, $Io);
                $Output->writeln('');
            }

            $Io->success('Finished. Please check that your module is working by access the URL http://<your-domain>/<rundizbones-install-dir>/admin/' . strtolower($this->moduleName) . ' from your browser.');
        }

        unset($Io);
    }// execute


    /**
     * Copy required files (or folders recursive).
     * 
     * @param InputInterface $Input
     * @param OutputInterface $Output
     * @param SymfonyStyle $Io
     */
    private function executeCopyRequiredFiles(InputInterface $Input, OutputInterface $Output, SymfonyStyle $Io)
    {
        // copy folders recursive.
        $foldersToCopy = ['config', 'Controllers', 'languages', 'ModuleData', 'Views', ];

        foreach ($foldersToCopy as $folderToCopy) {
            if ($this->copyFolder($folderToCopy, $Output, $Io) === true) {
                $destinationTarget = $this->moduleName . DIRECTORY_SEPARATOR . $folderToCopy;
                $listFiles = $this->Fs->listFilesSubFolders($destinationTarget);
                if (is_array($listFiles)) {
                    $this->rewritePlaceholder($listFiles, $Output, $Io);
                    $this->renameFiles($listFiles, $Output, $Io);
                }
                $Output->writeln('');
            }
        }// endforeach;

        unset($folderToCopy, $foldersToCopy);

        // copy file(s).
        $filesToCopy = ['Installer.pht'];

        foreach ($filesToCopy as $fileToCopy) {
            if ($this->copyFile($fileToCopy, $Output, $Io) === true) {
                $destinationTarget = $this->moduleName . DIRECTORY_SEPARATOR . $fileToCopy;
                $this->rewritePlaceholder([$destinationTarget], $Output, $Io);
                $this->renameFiles([$destinationTarget], $Output, $Io);
                $Output->writeln('');
            }
        }

        unset($fileToCopy, $filesToCopy);
    }// executeCopyRequiredFiles


    /**
     * Copy Tests folder.
     * 
     * @param InputInterface $Input
     * @param OutputInterface $Output
     * @param SymfonyStyle $Io
     */
    private function executeCopyTests(InputInterface $Input, OutputInterface $Output, SymfonyStyle $Io)
    {
        $copyResult = $this->copyFolder('Tests', $Output, $Io);
        $destinationTarget = $this->moduleName . DIRECTORY_SEPARATOR . 'Tests';

        if ($copyResult === true) {
            $listFiles = $this->Fs->listFilesSubFolders($destinationTarget);
            if (is_array($listFiles)) {
                $this->rewritePlaceholder($listFiles, $Output, $Io);
                $this->renameFiles($listFiles, $Output, $Io);
            }
        }

        $this->copyFile('phpunit.xml', $Output, $Io);
    }// executeCopyTests


    /**
     * Rename PHP template file (.pht) to .php file.
     * 
     * @param array $files The list of files that has got from `\Rdb\System\Libraries\FileSystem->listFilesSubFolders()`.
     * @param OutputInterface $Output
     * @param SymfonyStyle $Io
     */
    private function renameFiles(array $files, OutputInterface $Output, SymfonyStyle $Io)
    {
        foreach ($files as $file) {
            $fullPathFile = MODULE_PATH . DIRECTORY_SEPARATOR . $file;

            if (
                is_file($fullPathFile) && 
                (
                    stripos($file, '.pht') !== false ||
                    stripos($file, '%modulename%') !== false
                )
            ) {
                $newFileName = str_replace(['.pht', '%modulename%'], ['.php', strtolower($this->moduleName)], MODULE_PATH . DIRECTORY_SEPARATOR . $file);
                $renameResult = rename($fullPathFile, $newFileName);

                if ($renameResult === true) {
                    $Output->writeln('  File ' . $fullPathFile . ' was renamed to ' . $newFileName . '.');
                } else {
                    $Io->warning('  File ' . $fullPathFile . ' is unable to rename.');
                }

                unset($newFileName, $renameResult);
            }

            unset($fullPathFile);
        }// endforeach;
        unset($file);
    }// renameFiles


    /**
     * Rewrite the placeholder.
     * 
     * %ModuleName% to EnteredModuleName.<br>
     * %modulename% to enteredmodulename.
     * 
     * @param array $files The list of files that has got from `\Rdb\System\Libraries\FileSystem->listFilesSubFolders()`.
     * @param OutputInterface $Output
     * @param SymfonyStyle $Io
     */
    private function rewritePlaceholder(array $files, OutputInterface $Output, SymfonyStyle $Io)
    {
        foreach ($files as $file) {
            $fullPathFile = MODULE_PATH . DIRECTORY_SEPARATOR . $file;

            if (is_file($fullPathFile)) {
                // if file was found and it is file.
                $fileContents = file_get_contents($fullPathFile);
                $fileContents = str_replace(['%ModuleName%', '%modulename%'], [$this->moduleName, strtolower($this->moduleName)], $fileContents);
                $writeResult = $this->Fs->writeFile($file, $fileContents);

                if ($writeResult !== false) {
                    $Output->writeln('  File ' . $fullPathFile . ' has been successfully replaced the placeholders.');
                } else {
                    $Io->warning('  File ' . $fullPathFile . ' was unable to replace the placeholders.');
                }

                unset($fileContents, $writeResult);
            } else {
                // if file was not found.
                if (!is_dir($fullPathFile)) {
                    // if file is not even a folder, it is really not found.
                    $Io->error('  File ' . $fullPathFile . ' was not found.');
                }
            }

            unset($fullPathFile);
        }// endforeach;
        unset($file);
    }// rewritePlaceholder


}
