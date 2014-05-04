<?php

namespace aotd\Captcha;

class Region {
    public $x;
    public $y;
    public $columns;
    public $rows;

    public function __construct($x, $y, $columns, $rows)
    {
        $this->x = $x;
        $this->y = $y;
        $this->columns = $columns;
        $this->rows = $rows;
    }

    public function __toString()
    {
        return "[$this->x; $this->y; $this->columns; $this->rows]";
    }
} 