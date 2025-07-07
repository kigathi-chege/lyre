<?php

namespace Lyre;

use Illuminate\Database\Schema\ColumnDefinition;
use Attribute;

#[Attribute]
class Field
{
    public string $type;
    public array $modifiers;
    public array $options;

    public function __construct(...$args)
    {
        // $columnDefinition = new ColumnDefinition([
        //     'type' => $type,
        //     'nullable' => $nullable,
        //     'default' => $default,
        // ]);

        // if (count($args) > 1) {
        //     dd($args);
        // }


        $this->modifiers = [];
        $this->options = [];

        $foundType = false;

        // Separate positional and named args
        foreach ($args as $key => $value) {

            if ($key === 0) {
                $foundType = true;
                $this->type = $value;
                continue;
            }

            if (!$foundType) {
                echo "Type error" . PHP_EOL;
                echo "Missing field type" . PHP_EOL;
            }

            if (is_string($key)) {
                // Named argument
                $this->options[$key] = $value;
            } else {
                // Positional argument
                $this->modifiers[] = $value;
            }
        }
    }
}
