<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Interfaces;


/**
 * Plugins interface that any plugin file must be implemented to work.
 * 
 * @since 0.2.4
 */
interface Plugins
{


    /**
     * Class constructor.
     * 
     * Set the container to protected property for easy access from the other part in the plugin class.<br>
     * Example:<pre>
     * $this->Container = $Container;
     * </pre>
     * 
     * @param \Rdb\System\Container $Container The DI container class.
     */
    public function __construct(\Rdb\System\Container $Container);


    /**
     * Register action and/or filter hooks.
     * 
     * Example:<pre>
     * $Plugins = $this->Container['Plugins'];
     * $YourClass = new \Rdb\Modules\YourModule\Plugins\YourPlugin\YourPluginClass();
     * $Plugins->addAction('hook.name', [$YourClass, 'yourActionHook'], 10);
     * $Plugins->addFilter('hook.name', [$YourClass, 'yourFilterHook'], 10);
     * </pre>
     */
    public function registerHooks();


    /**
     * Your uninstall action on uninstall or delete a plugin.
     */
    public function uninstall();


}
