<?php

namespace practice\utils;

class yaml
{

    private static $plugin;
    public static array $globalConfig = [];
    public static array $capeConfig = [];
    public static array $hatConfig = [];
    public static array $killphaseConfig = [];
    public static array $skinConfig = [];
    public static array $soundConfig = [];
    public static array $titleConfig = [];

    public static function setup(): void
    {
        self::create();
        self::load();
    }

    public static function create(): void {}

    public static function load(): void
    {
        $currentPath = __DIR__;
        $filePath = str_replace("src\practice\utils", "resources\defalt_config.yaml", $currentPath);
        self::$globalConfig = yaml_parse_file($filePath);
        $filePath = str_replace("src\practice\utils", "resources\cosmetic\cape.yaml", $currentPath);
        self::$capeConfig = yaml_parse_file($filePath);
        $filePath = str_replace("src\practice\utils", "resources\cosmetic\hat.yaml", $currentPath);
        self::$hatConfig = yaml_parse_file($filePath);
        $filePath = str_replace("src\practice\utils", "resources\cosmetic\killphase.yaml", $currentPath);
        self::$killphaseConfig = yaml_parse_file($filePath);
        $filePath = str_replace("src\practice\utils", "resources\cosmetic\skin.yaml", $currentPath);
        self::$skinConfig = yaml_parse_file($filePath);
        $filePath = str_replace("src\practice\utils", "resources\cosmetic\sound.yaml", $currentPath);
        self::$soundConfig = yaml_parse_file($filePath);
        $filePath = str_replace("src\practice\utils", "resources\cosmetic\\title.yaml", $currentPath);
        self::$titleConfig = yaml_parse_file($filePath);
    }

    public static function getconfig(): array
    {
        return self::$globalConfig;
    }

    public static function getcolor(): string
    {
        //$list = ["a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "v", "u", "r", "s", "f", "g", "h", "1", "2", "3", "4", "5", "6", "7", "8"];
        //$randomKey = array_rand($list);
        //$color = "§" . $list[$randomKey];
        $color = self::$globalConfig["color"];
        return $color;
    }

    public static function getcosmeticconfig($type): array
    {
        $configname = $type . "Config";
        return self::$$configname;
    }

    public static function setPluginInstance($plugin): void
    {
        self::$plugin = $plugin;
    }
}
