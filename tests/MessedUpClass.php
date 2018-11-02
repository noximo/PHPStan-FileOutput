<?php
/**
 * Created by PhpStorm.
 * User: TP
 * Date: 01.11.2018
 * Time: 23:07
 */
namespace noximo;

class MessedUpClass
{
    const SOMETHING = 1 + 1;

    public function __construct()
    {
    }

    /**
     * @param string $problem
     *
     * @return int
     */
    function doSOmething(int $problem): string
    {
        return $problem;
    }
}
