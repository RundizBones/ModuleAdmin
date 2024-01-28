<?php
/** 
 * RdbAdmin cache configuration.
 * 
 * @license http://opensource.org/licenses/MIT MIT
 */


// Example of memcache driver configuration.
//$Memcache = new \Memcache();
// For memcacheD use `\Memcached()` class.
//$add = $Memcache->addServer('localhost', 11211);
// Maybe use `$Memcache->flush();` once after changed the configuration value.

return [
    // Simple cache configuration.
    // Driver accept: One of these ( 'filesystem', 'apcu', 'memcache', 'memcached' )
    'driver' => 'filesystem',
    // Memcache or Memcached configuration.
    // Use `$Memcache` variable from the example above of this configuration file.
    //'memcacheConfig' => $Memcache,// un-comment this line if you want to use external cache driver with its configuration.

    // Model auto cache expires in days. The models can auto generate cache for quick access, please set the expire date for those cache.
    'modelCacheExpire' => 30,
    // Cache menu items or not (boolean). Set to `true` for cache menu items, set to `false` (default) to not cache it. For production, it is recommended to set this to `true`.
    'cacheMenuItem' => false,
];