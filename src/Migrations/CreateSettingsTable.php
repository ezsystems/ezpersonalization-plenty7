<?php

namespace Yoochoose\Migrations;

use Yoochoose\Models\Settings;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;

class CreateSettingsTable
{

    /**
     * @param Migrate $migrate
     */
    public function run(Migrate $migrate)
    {
        $migrate->createTable(Settings::class, 10, 20);
    }
}