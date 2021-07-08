<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbAdmin\Libraries\SPLIterators;


/**
 * SPL filter iterator.
 * 
 * @since 1.1.8
 */
class FilterOnlyDir extends \FilterIterator
{


    /**
     * Accept only directory.
     * 
     * @return bool Return `true` if it is directory, return `false` for otherwise.
     */
    public function accept(): bool
    {
        $File = $this->getInnerIterator()->current();
        if ($File->isDir()) {
            return true;
        }
        return false;
    }// accept


}
