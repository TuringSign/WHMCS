<?php

namespace ModulesGarden\TuringSign\Helpers;

use WHMCS\Database\Capsule;

class Lang
{
    protected string $language = "english";
    protected string $languagePath = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "lang" . DIRECTORY_SEPARATOR;
    protected array $langs = [];

    public function __construct()
    {
        $sessionLanguage = $_SESSION['Language'];
        $clientLanguage = Capsule::table('tblclients')->where('id', '=', $_SESSION['uid'])->first(['language']);
        $defaultLanguage = Capsule::table('tblconfiguration')->where('setting', '=', 'language')->first(['value']);

        if($sessionLanguage && file_exists($this->languagePath . $sessionLanguage . ".php"))
        {
            $this->language = $sessionLanguage;
        }
        else if($clientLanguage && file_exists($this->languagePath . $clientLanguage->language . ".php"))
        {
            $this->language = $clientLanguage->language;
        }
        else if($defaultLanguage && file_exists($this->languagePath . $defaultLanguage->value . ".php"))
        {
            $this->language = $defaultLanguage->value;
        }

        $this->langs = include($this->languagePath . $this->language . ".php");

        if(!$this->langs)
        {
            $this->langs = [];
        }
    }

    public function get(string $lang, array $params = []): string
    {
        $langValue = $this->langs[$lang] ?? $lang;

        foreach($params as $key => $param)
        {
            $langValue = str_replace(':' . $key, $param, $langValue);
        }

        return $langValue;
    }

}
