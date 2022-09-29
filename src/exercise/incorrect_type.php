<?php
namespace IncorrectType;

use Demo\DB;

class SampleClass {
    /**
     * @throws \Exception
     */
    public function getUserId(string $userName): int
    {
        /** @var array<string>|null $row */
        $row = DB::select('id')
            ->from('users')
            ->where('name', $userName)
            ->execute()
            ->current();
        if ($row === null) {
            throw new \Exception("User {$userName} not found");
        }
        return (int) $row['id'];
    }
}
