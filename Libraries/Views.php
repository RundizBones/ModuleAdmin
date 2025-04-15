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
