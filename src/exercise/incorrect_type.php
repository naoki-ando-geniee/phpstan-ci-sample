<?php
namespace IncorrectType;

use Demo\DB;

class SampleClass {
    public function getUserId(string $userName): int
    {
        /** @var array<string>|null $row */
        $row = DB::select('id')
            ->from('users')
            ->where('name', $userName)
            ->execute()
            ->current();
        return $row['id'];
    }
}
