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
     * When plugin is enabled, this method will be called.
     * Do not echo out in this method or it will not functional.
     */
    public function enable();


    /**
     * When plugin is disabled, this method will be called.
     * Do not echo out in this method or it will not functional.
     */
    public function disable();


    /**
     * Register action and/or filter hooks.
     * 
     * Example:<pre>
     * $Plugins = $this->Container['Plugins'];
     * $YourClass = new \Rdb\Modules\YourModule\Plugins\YourPlugin\YourPluginClass();
     * $Plugins->addHook('hook.name', [$YourClass, 'yourActionHook'], 10);
     * </pre>
     */
    public function registerHooks();


    /**
     * Your uninstall action on uninstall or delete a plugin.
     * Do not echo out in this method or it will not functional.
     */
    public function uninstall();


}
