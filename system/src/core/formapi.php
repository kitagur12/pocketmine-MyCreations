<?php

namespace core;

use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;

class formapi
{
    private static array $formdata = [];

    public static function sendForm($player, $formId)
    {
        $xuid = $player->getXuid();
        $packet = new ModalFormRequestPacket();
        $packet->formId = $formId;
        if (!isset(self::$formdata[$xuid])) {
            self::reset($player);
        }
        $packet->formData = json_encode(self::$formdata[$xuid]);
        $player->getNetworkSession()->sendDataPacket($packet);
        //var_dump(self::$formdata);
        self::reset($player);
    }

    public static function reset($player)
    {
        $xuid = $player->getXuid();
        self::$formdata[$xuid] = null;
        self::$formdata[$xuid]["type"] = null;
    }

    public static function setType($player, $type)
    {
        $xuid = $player->getXuid();
        self::$formdata[$xuid]["type"] = $type;
    }

    public static function setTitle($player, $title)
    {
        $xuid = $player->getXuid();
        self::$formdata[$xuid]["title"] = $title;
    }

    public static function setContent($player, $content)
    {
        $xuid = $player->getXuid();
        if (self::$formdata[$xuid]["type"] == "modal" || self::$formdata[$xuid]["type"] == "form") {
            self::$formdata[$xuid]["content"] = $content;
        }
    }

    public static function addButton($player, $text, $image = false)
    {
        $xuid = $player->getXuid();
        if (self::$formdata[$xuid]["type"] == "form") {
            $button = ["text" => $text];
            if ($image !== false) {
                if (strpos($image, "://") !== false) {
                    $button["image"] = ["type" => "url", "data" => $image];
                } else {
                    $button["image"] = ["type" => "path", "data" => $image];
                }
            }
            self::$formdata[$xuid]["buttons"][] = $button;
        }
    }

    public static function setModal($player, $button1, $button2)
    {
        $xuid = $player->getXuid();
        if (self::$formdata[$xuid]["type"] == "modal") {
            self::$formdata[$xuid]["button1"] = $button1;
            self::$formdata[$xuid]["button2"] = $button2;
        }
    }

    public static function addLabel($player, $text)
    {
        $xuid = $player->getXuid();
        if (self::$formdata[$xuid]["type"] == "custom_form") {
            self::$formdata[$xuid]["content"][] = ["type" => "label", "text" => $text];
        }
    }

    public static function addInput($player, $text, $placeholder = "", $default = "")
    {
        $xuid = $player->getXuid();
        if (self::$formdata[$xuid]["type"] == "custom_form") {
            self::$formdata[$xuid]["content"][] = ["type" => "input", "text" => $text, "placeholder" => $placeholder, "default" => $default];
        }
    }

    public static function addToggle($player, $text, bool $default = false)
    {
        $xuid = $player->getXuid();
        if (self::$formdata[$xuid]["type"] == "custom_form") {
            self::$formdata[$xuid]["content"][] = ["type" => "toggle", "text" => $text, "default" => $default];
        }
    }

    public static function addSlider($player, $text, $min, $max, $step, $default = 0)
    {
        $xuid = $player->getXuid();
        if (self::$formdata[$xuid]["type"] == "custom_form") {
            self::$formdata[$xuid]["content"][] = ["type" => "slider", "text" => $text, "min" => $min, "max" => $max, "step" => $step, "default" => $default];
        }
    }

    public static function addStepSlider($player, $text, $steps, $default = 0)
    {
        $xuid = $player->getXuid();
        if (self::$formdata[$xuid]["type"] == "custom_form") {
            if (is_string($steps)) {
                $steps = [$steps];
            }
            self::$formdata[$xuid]["content"][] = ["type" => "step_slider", "text" => $text, "steps" => $steps, "default" => $default];
        }
    }

    public static function addDropDown($player, $text, $options, $default = 0)
    {
        $xuid = $player->getXuid();
        if (self::$formdata[$xuid]["type"] == "custom_form") {
            if (is_string($options)) {
                $options = [$options];
            }
            self::$formdata[$xuid]["content"][] = ["type" => "dropdown", "text" => $text, "options" => $options, "default" => $default];
        }
    }
}
