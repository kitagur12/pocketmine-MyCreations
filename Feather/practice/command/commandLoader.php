<?php

namespace practice\command;

use practice\command\commandperm;
use practice\command\DisableCommand;
use practice\command\commands\console;
use practice\command\commands\builder;
use practice\command\commands\ban;
use practice\command\commands\unban;
use practice\command\commands\help;
use practice\command\commands\debug;
use practice\command\commands\gamemodecommand1;
use practice\command\commands\tp;
use practice\command\commands\language;
use practice\command\commands\lobby;
use practice\command\commands\setrank;
use practice\command\commands\settings;
use practice\command\commands\spectate;
use practice\command\commands\forfeit;
use practice\command\commands\reboot;
use practice\command\commands\hook;
use practice\command\commands\partyc;

class commandLoader {
    public static function loadcommand(): void {
        commandperm::createperm();
        DisableCommand::DisableCommand();
        console::command_console();
        ban::command_ban();
        unban::command_unban();
        help::command_help();
        debug::command_debug();
        gamemodecommand1::command_gamemode();
        tp::command_tp();
        language::command_language();
        lobby::command_lobby();
        builder::command_builder();
        setrank::command_setrank();
        settings::command_settings();
        spectate::command_spectate();
        forfeit::command_forfeit();
        reboot::command_reboot();
        hook::command_hook();
        partyc::command_party();
    }
}
