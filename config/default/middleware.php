<?php
/** 
 * The middleware configuration.
 * 
 * Contain middleware in array.
 * The middleware array contain 2 key names: `beforeMiddleware`, `afterMiddleware`.
 * The `beforeMiddleware` will be run before application start and `afterMiddleware` will be run after application started.
 * Each key will be run from top to bottom.
 * 
 * The middleware array value is the handle function, class.
 * Example: `\Rdb\Modules\Users\Middleware\RequireAuth:init`
 * This will be call class name `\Rdb\Modules\Users\Middleware\RequireAuth` and `init` method.
 * 
 * The middleware must accept and return the response content in its method.
 * 
 * @license http://opensource.org/licenses/MIT MIT
 * @since 1.2.1
 */


$beforeMiddleware = [];

$beforeMiddleware[11] = '\Rdb\Modules\RdbAdmin\Middleware\ValidDomain:init';
$beforeMiddleware[12] = '\Rdb\Modules\RdbAdmin\Middleware\APIAccess:init';

return [
    'beforeMiddleware' => $beforeMiddleware,
    'afterMiddleware' => [],
];