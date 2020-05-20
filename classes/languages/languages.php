<?php

namespace SCT {
    abstract class Language
    {
        abstract public function getName(): string;
        abstract public function getFileEnding(): string;
        abstract public function getKey(): string;
        abstract public function getMIME(): string;
        abstract public function getCodeMirrorFile(): string;
    };
    class Languages
    {
        var $languages = array();
        function __construct()
        {
            $this->loadLanguages();
        }
        public function getMIME(): array
        {
            return array_reduce(
                $this->languages,
                function ($carry, $data) {
                    $carry[$data->getKey()] = $data->getMIME();
                    return $carry;
                },
                array()
            );
        }
        public function getName(): array {
            return array_reduce(
                $this->languages,
                function ($carry, $data) {
                    $carry[$data->getKey()] = $data->getName();
                    return $carry;
                },
                array()
            );
        }
        /***
         * Returns all loaded languages
         *
         * @result array All loaded languages as key=>SCT\Language pairs
         * @access public
         */
        public function get(): array
        {
            return $this->languages;
        }

        /***
         * Load all language files and instanciate.
         */
        private function loadLanguages()
        {
            foreach (scandir(__DIR__) as $file) {
                $pos = strrpos($file, '.');
                if (substr($file, $pos) != ".php") continue;
                if ($file == "languages.php") continue;
                $path = __DIR__ . "/" . $file;
                include_once $path;
            };
            foreach (get_declared_classes() as $cls) {
                if (!is_subclass_of($cls, Language::class)) continue;
                $obj = new $cls;
                $this->languages[$obj->getKey()] = $obj;
            };
        }
    };
    //print_r(new Languages());
};
