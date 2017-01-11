<?php

namespace Yoochoose\Services;

use Yoochoose\Models\Settings;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;

/**
 * Class SettingsService
 * @package Yoochoose\Services
 */
class SettingsService
{
    const WEB_PROFILE = "yc_test";
    const CUSTOMER = "customer_id";
    const LICENCE = "license_key";
    const PLUGIN = "plugin_id";
    const DESIGN = "design";
    const TYPE = "item_type";
    const OVERWRITE = "script_id";
    const SEARCH = "search_enable";
    const PERFORMANCE = "performance";
    const LOG = "log_severity";
    const TOKEN = "token";


    /**
     * @var array
     */
    private $settingsID = [
        self::WEB_PROFILE => 1,
        self::CUSTOMER => 2,
        self::LICENCE => 3,
        self::PLUGIN => 4,
        self::DESIGN => 5,
        self::TYPE => 6,
        self::OVERWRITE => 7,
        self::SEARCH => 8,
        self::PERFORMANCE => 9,
        self::LOG => 10,
        self::TOKEN => 11,
    ];

    /**
     * @var DataBase
     */
    private $dataBase;

    /**
     * SettingsService constructor.
     * @param DataBase $dataBase
     */
    public function __construct(DataBase $dataBase)
    {
        $this->dataBase = $dataBase;
    }

    /**
     * Set the settings value
     *
     * @param string $name
     * @param $value
     * @throws \Exception
     */
    public function setSettingsValue(string $name, $value)
    {
        if(!array_key_exists($name, $this->settingsID))
        {
            throw new \Exception('The given settings name is not defined!');
        }

        $settings = pluginApp(Settings::class);

        if($settings instanceof Settings)
        {
            $settings->id        = $this->settingsID[$name];
            $settings->name      = $name;
            $settings->value     = (string) $value;
            $settings->createdAt = date('Y-m-d H:i:s');
            $settings->updatedAt = date('Y-m-d H:i:s');

            $this->dataBase->save($settings);
        }
    }

    /**
     * Get the settings value
     *
     * @param string $name
     * @return bool|mixed
     * @throws \Exception
     */
    public function getSettingsValue(string $name)
    {
        if(!array_key_exists($name, $this->settingsID))
        {
            throw new \Exception('The given settings name is not defined!');
        }

        /** @var Settings $settings */
        $settings = $this->dataBase->find(Settings::class, $this->settingsID[$name]);

        if($settings instanceof Settings)
        {
            return $settings->value;
        }

        return false;
    }
}