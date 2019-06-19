<?php

namespace Lde\ApiHelper\Helpers;

class ObfuscationHelper
{

    public static function obfuscate($string, $visibleChars = 4)
    {
        $len = strlen($string);
        $visible = substr($string, ($visibleChars * -1));
        return str_pad($visible, $len, '*', STR_PAD_LEFT);
    }

    public static function obfuscateEmail($email)
    {
        $em = explode("@", $email);
        $name = implode(array_slice($em, 0, (count($em) - 1)), "@");
        $len = (strlen($name) - 1);
        return substr($name, 0, 1) . str_repeat('*', $len) . "@" . end($em);
    }

}
