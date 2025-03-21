<?php

namespace hentai\command;

use hentai\command\commandperm;
use hentai\command\DisableCommand;
use hentai\command\commands\hentaiac;

class commandLoader {
    public static function loadcommand(): void {
        commandperm::createperm();
        hentaiac::command();
    }
}
