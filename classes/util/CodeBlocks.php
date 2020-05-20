<?php

namespace SCT {
    abstract class CodeBlockType
    {
        const Text = 0;
        const StaticCode = 1;
        const SolutionCode = 2;
        const HiddenCode = 3;
        const TestCode = 4;
    }
    class CodeBlock
    {
        var $type = null;
        var $send = null;
        var $lines = null;
        var $content = null;
        function __construct(int $type = CodeBlockType::StaticCode, bool $send = true, int $lines = 15, string $content = "")
        {
            $this->type = $type;
            $this->send = $send;
            $this->lines = $lines;
            $this->content = $content;
        }
        function fromArray($array)
        {
            if (!is_array($array)) return;
            $this->type = $array["type"];
            $this->send = $array["send"];
            $this->lines = $array["lines"];
            $this->content = $array["content"];
        }
        function toArray(): array
        {
            return array(
                "type" => $this->type,
                "send" => $this->send,
                "lines" => $this->lines,
                "content" => $this->content,
            );
        }

        function setType(int $type)
        {
            $this->type = $type;
        }
        function getType(): int
        {
            return $this->type;
        }

        function setSend(bool $send)
        {
            $this->send = $send;
        }
        function getSend(): bool
        {
            return $this->send;
        }

        function setLines(int $lines)
        {
            $this->lines = $lines;
        }
        function getLines(): int
        {
            return $this->lines;
        }

        function setContent(string $content)
        {
            $this->content = $content;
        }
        function getContent(): string
        {
            return $this->content;
        }
    }
}
