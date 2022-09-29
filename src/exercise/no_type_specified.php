<?php
namespace NoTypeSpecified;

class SampleClass {

    /** @var string[] */
    private $users = [];

    public function printUsers(): void
    {
        foreach ($this->users as $user) {
            echo "{$user}\n";
        }
    }

    /**
     * @return string[]
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    public function addUser(string $userName): void
    {
        $this->users [] = $userName;
    }
}
