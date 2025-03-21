<?php

namespace practice\form;

use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use practice\player\PracticePlayer;

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

    public static function setTitle($player, $title, $tlanslate = true)
    {
        if ($tlanslate) {
            $title = PracticePlayer::getLangText($player, $title);
        }
        $xuid = $player->getXuid();
        self::$formdata[$xuid]["title"] = $title;
    }

    public static function setContent($player, $content, $tlanslate = true)
    {
        if ($content !== "") {
            if ($tlanslate) {
                $content = PracticePlayer::getLangText($player, $content);
            }
        }
        $xuid = $player->getXuid();
        if (self::$formdata[$xuid]["type"] == "modal" || self::$formdata[$xuid]["type"] == "form") {
            self::$formdata[$xuid]["content"] = $content;
        }
    }

    public static function addButton($player, $text, $image = false, $tlanslate = true, $prefix = "")
    {
        if ($tlanslate) {
            $text = PracticePlayer::getLangText($player, $text);
        }
        $text = $text . $prefix;
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

    public static function setModal($player, $button1, $button2, $tlanslate = true)
    {
        if ($tlanslate) {
            $button1 = PracticePlayer::getLangText($player, $button1);
        }
        if ($tlanslate) {
            $button2 = PracticePlayer::getLangText($player, $button2);
        }
        $xuid = $player->getXuid();
        if (self::$formdata[$xuid]["type"] == "modal") {
            self::$formdata[$xuid]["button1"] = $button1;
            self::$formdata[$xuid]["button2"] = $button2;
        }
    }

    public static function addLabel($player, $text, $tlanslate = true)
    {
        if ($tlanslate) {
            $text = PracticePlayer::getLangText($player, $text);
        }
        $xuid = $player->getXuid();
        if (self::$formdata[$xuid]["type"] == "custom_form") {
            self::$formdata[$xuid]["content"][] = ["type" => "label", "text" => $text];
        }
    }

    public static function addInput($player, $text, $placeholder = "", $default = "", $tlanslate = true)
    {
        if ($tlanslate) {
            $text = PracticePlayer::getLangText($player, $text);
        }
        $xuid = $player->getXuid();
        if (self::$formdata[$xuid]["type"] == "custom_form") {
            self::$formdata[$xuid]["content"][] = ["type" => "input", "text" => $text, "placeholder" => $placeholder, "default" => $default];
        }
    }

    public static function addToggle($player, $text, $prefix, bool $default = false, $tlanslate = true)
    {
        if ($tlanslate) {
            $text = PracticePlayer::getLangText($player, $text);
        }
        $xuid = $player->getXuid();
        if (self::$formdata[$xuid]["type"] == "custom_form") {
            self::$formdata[$xuid]["content"][] = ["type" => "toggle", "text" => $text . $prefix, "default" => $default];
        }
    }

    public static function addSlider($player, $text, $min, $max, $step, $default = 0, $tlanslate = true)
    {
        if ($tlanslate) {
            $text = PracticePlayer::getLangText($player, $text);
        }
        $xuid = $player->getXuid();
        if (self::$formdata[$xuid]["type"] == "custom_form") {
            self::$formdata[$xuid]["content"][] = ["type" => "slider", "text" => $text, "min" => $min, "max" => $max, "step" => $step, "default" => $default];
        }
    }

    public static function addStepSlider($player, $text, $steps, $default = 0, $tlanslate = true)
    {
        if ($tlanslate) {
            $text = PracticePlayer::getLangText($player, $text);
        }
        $xuid = $player->getXuid();
        if (self::$formdata[$xuid]["type"] == "custom_form") {
            if (is_string($steps)) {
                $steps = [$steps];
            }
            self::$formdata[$xuid]["content"][] = ["type" => "step_slider", "text" => $text, "steps" => $steps, "default" => $default];
        }
    }

    public static function addDropDown($player, $text, $options, $default = 0, $tlanslate = true)
    {
        if ($tlanslate) {
            $text = PracticePlayer::getLangText($player, $text);
        }
        $xuid = $player->getXuid();
        if (self::$formdata[$xuid]["type"] == "custom_form") {
            if (is_string($options)) {
                $options = [$options];
            }
            self::$formdata[$xuid]["content"][] = ["type" => "dropdown", "text" => $text, "options" => $options, "default" => $default];
        }
    }
}
