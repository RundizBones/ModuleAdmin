<?php
/** 
 * RdbAdmin cache configuration.
 * 
 * @license http://opensource.org/licenses/MIT MIT
 */


// Example of memcache driver configuration.
//$Memcache = new \Memcache();
//$add = $Memcache->addServer('localhost');

return [
    // Simple cache configuration.
    // driver: accept 'filesystem', 'apcu', 'memcache', 'memcached'
    'driver' => 'filesystem',
    // memcache or memcached configuration.
    // use `$Memcache = new \Memcache();` to define new variable object 
    // and then `$Memcache->addServer('localhost');` to add your memcache or memcached configuration.
    // after that, put `$Memcache` into this config value. Example: `'memcacheConfig' => $Memcache,`.
    //'memcacheConfig' => $Memcache,// un-comment this line if you want to use external cache driver with its configuration.

    // Model auto cache expire date. The models can auto generate cache for quick access, please set the expire date for those cache.
    'modelCacheExpire' => 30,
    // Cache menu items or not (boolean). Set to `true` for cache menu items, set to `false` (default) to not cache it. For production, it is recommended to set this to `true`.
    'cacheMenuItem' => false,
];