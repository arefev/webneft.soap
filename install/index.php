<?php

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Configuration as BitrixConfig;
use Webneft\Soap\Module;

class webneft_soap extends CModule
{
    const SETTINGS_NAME = 'webneft_soap';
    var $MODULE_ID = 'webneft.soap';

    function __construct()
    {
        $arModuleVersion = array();

        include(__DIR__ . '/version.php');

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_NAME = 'webneft | soap — Модуль подключения к 1C ';
        $this->MODULE_DESCRIPTION = 'webneft soap';
        $this->PARTNER_NAME = 'webneft';
        $this->PARTNER_URI = '';
    }

    public function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        Loader::includeModule($this->MODULE_ID);
        $this->settingsInstall();
    }

    public function DoUninstall()
    {
        Loader::includeModule($this->MODULE_ID);
        $this->settingsUninstall();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function settingsInstall()
    {
        $conf = BitrixConfig::getInstance();
        $params = [
            'url' => 'https://127.0.0.1:6080/ts_test/ws/TransportServer?wsdl',
            'login' => 'test',
            'password' => '123',
        ];
        $conf->add(Module::SETTINGS_NAME, $params);
        $conf->saveConfiguration();
    }

    public function settingsUninstall()
    {
        $conf = BitrixConfig::getInstance();
        $conf->delete(Module::SETTINGS_NAME);
        $conf->saveConfiguration();
    }
}