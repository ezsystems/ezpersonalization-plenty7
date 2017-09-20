<?php

namespace Yoochoose\Services;

use Yoochoose\Models\Settings;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;

class SettingsService
{
    const CUSTOMER = "customer_id";
    const LICENCE = "license_key";
    const PLUGIN = "plugin_id";
    const DESIGN = "design";
    const TYPE = "item_type";
    const OVERWRITE = "script_id";
    const SEARCH = "search_enable";
    const PERFORMANCE = "performance";
    const LOG = "log_severity";
    const TOKEN = "auth_token";
    const ENDPOINT = "endpoint";
    const ENABLE = "enable_flag";
    const PASSWORD = "yc_password";

    /**
     * @var array
     */
    private $settingsID = [
        self::CUSTOMER => 1,
        self::LICENCE => 2,
        self::PLUGIN => 3,
        self::DESIGN => 4,
        self::TYPE => 5,
        self::OVERWRITE => 6,
        self::SEARCH => 7,
        self::PERFORMANCE => 8,
        self::LOG => 9,
        self::TOKEN => 10,
        self::ENDPOINT => 11,
        self::ENABLE => 12,
        self::PASSWORD => 13,
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
        if (!array_key_exists($name, $this->settingsID)) {
            throw new \Exception('The given settings name is not defined!');
        }

        $settings = pluginApp(Settings::class);

        if ($settings instanceof Settings) {
            $settings->id = $this->settingsID[$name];
            $settings->name = $name;
            $settings->value = !empty($value) ? (string)$value : '';
            $settings->createdAt = date('Y-m-d H:i:s');
            $settings->updatedAt = date('Y-m-d H:i:s');

            $this->dataBase->save($settings);
        }
    }

    /**
     * Get the settings value
     *
     * @param string $name
     * @param null $default
     * @return bool|mixed
     * @throws \Exception
     */
    public function getSettingsValue(string $name, $default = null)
    {
        if (!array_key_exists($name, $this->settingsID)) {
            throw new \Exception('The given settings name is not defined!');
        }

        /** @var Settings $settings */
        $settings = $this->dataBase->find(Settings::class, $this->settingsID[$name]);

        if ($settings instanceof Settings) {
            return $settings->value;
        }

        return $default;
    }
}