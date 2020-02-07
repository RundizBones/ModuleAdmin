<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Console;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Style\SymfonyStyle;


/**
 * Cron CLI.
 * 
 * @since 0.1
 */
class Cron extends \Rdb\System\Core\Console\BaseConsole
{


    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('rdbadmin:cron')
            ->setDescription('Run cron jobs via command line')
            ->setHelp(
                'Run the same cron jobs as URL /admin/cron but run it via command line interface. 
                The cron jobs will be automatic load and run from any modules that is compatible with RdbAdmin module.'
            )
        ;
    }// configure


    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $Input, OutputInterface $Output)
    {
        $Cron = new \Rdb\Modules\RdbAdmin\Libraries\Cron($this->Container);
        $Io = new SymfonyStyle($Input, $Output);

        $dataOutput = [];
        $dataOutput['runnedJobs'] = $Cron->runJobsOnAllModules();

        $Io->title('Running cron job');
        $Output->writeln('Run result:');
        $Output->writeln(var_export($dataOutput, true));
        $Io->success('Cron job end.');

        unset($Cron, $dataOutput, $Io);
    }// execute


}
