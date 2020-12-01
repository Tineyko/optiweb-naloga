<?php

namespace PimMagBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;

class PimMagBundle extends AbstractPimcoreBundle
{
    public function getJsPaths()
    {
        return [
            '/bundles/pimmag/js/pimcore/startup.js'
        ];
    }
}
