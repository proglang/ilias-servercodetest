<?php

namespace SCT {
    include_once(__DIR__ . '/languages.php');
    class Python extends \SCT\Language
    {
        public function getName(): string
        {
            return "Python";
        }
        public function getFileEnding(): string
        {
            return "py";
        }
        public function getKey(): string
        {
            return "py";
        }
        public function getMIME(): string
        {
            return "text/x-python";
        }
        public function getCodeMirrorFile(): string
        {
            return "python/python.js";
        }
    }
}
