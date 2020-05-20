<?php

namespace SCT {
    include_once(__DIR__ . '/languages.php');
    class C extends \SCT\Language
    {
        public function getName(): string
        {
            return "C";
        }
        public function getFileEnding(): string
        {
            return "c";
        }
        public function getKey(): string
        {
            return "c";
        }
        public function getMIME(): string
        {
            return "text/x-csrc";
        }
        public function getCodeMirrorFile(): string
        {
            return "clike/clike.js";
        }
    }
    class CPP extends \SCT\Language
    {
        public function getName(): string
        {
            return "C++";
        }
        public function getFileEnding(): string
        {
            return "cpp";
        }
        public function getKey(): string
        {
            return "cpp";
        }
        public function getMIME(): string
        {
            return "text/x-c++src";
        }
        public function getCodeMirrorFile(): string
        {
            return "clike/clike.js";
        }
    }
}
