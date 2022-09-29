<?php
namespace NoTypeSpecified;

class SampleClass {

    private $users = [];

    public function printUsers()
    {
        foreach ($this->users as $user) {
            echo "{$user}\n";
        }
    }

    public function getUsers(): array
    {
        return $this->users;
    }

    public function addUser($userName)
    {
        $this->users [] = $userName;
    }
}
