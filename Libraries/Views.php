<?php
/**
 * Extends framework Views class.
 * @since 1.2.12
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Libraries;


/**
 * The Views class that extends framework `Views` class.
 * 
 * @since 1.2.12
 */
class Views extends \Rdb\System\Views
{


    /**
     * {@inheritDoc}
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        parent::__construct($Container);
    }// __construct


    /**
     * {@inheritDoc}
     */
    public function locateViews(string $viewsFile, ?string $currentModule = null): string
    {
        if (empty($currentModule)) {
            $debugBacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
            if (
                isset($debugBacktrace[3]['class']) && 
                is_scalar($debugBacktrace[3]['class']) &&
                stripos($debugBacktrace[3]['class'], 'Rdb\\Modules\\') !== false
            ) {
                // if found modules in trace.
                $expClass = explode('\\', $debugBacktrace[3]['class']);
                if (isset($expClass[2])) {
                    // if found the module name.
                    $currentModule = $expClass[2];
                }
            }// endif;
            unset($debugBacktrace, $expClass);
        }

        return parent::locateViews($viewsFile, $currentModule);
    }// locateViews


    /**
     * {@inheritDoc}
     * 
     * Also set required data if not exists.
     */
    public function render(string $viewsFile, array $data = [], array $options = []): string
    {
        if (!isset($data['Modules'])) {
            $data['Modules'] = $this->Modules;
        }
        if (!isset($data['Url'])) {
            $data['Url'] = new \Rdb\System\Libraries\Url($this->Container);
        }
        if (!isset($data['Views'])) {
            $data['Views'] = $this;
        }

        return parent::render($viewsFile, $data, $options);
    }// render


}
