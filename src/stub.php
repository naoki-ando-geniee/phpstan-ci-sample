<?php
namespace Demo;

class DB {
    public static function select(string $column): Select {
        echo $column;
        return new Select();
    }
}

class Select {
    public function from(string $table): WhereStatement {
        echo $table;
        return new WhereStatement();
    }
}

class WhereStatement {
    public function where(string $column, string $value): WhereStatement {
        echo $column;
        echo $value;
        return $this;
    }

    public function execute(): Result {
        return new Result();
    }
}

class Result {
    /**
     * @return array<string>|null
     */
    public function current() {
        return [];
    }

    /**
     * @return array<string>[]
     */
    public function as_array(): array {
        return [];
    }
}
