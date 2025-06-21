<?php
declare(strict_types=1);

namespace Studio3Marketing\AppAccess\Api;

interface AppLoginManagementInterface
{
    /**
     * Get app login information or perform app login logic.
     *
     * @return mixed
     */
    public function getAppLogin();
}

