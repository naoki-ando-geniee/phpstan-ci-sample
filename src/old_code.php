<?php

class OldClass
{
    // private string $prop;

    public function __construct()
    {
        $this->prop = '動的プロパティ';
    }

    public function getProp(): string
    {
        return $this->prop;
    }
}
