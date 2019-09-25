<?php

namespace Horoshop;

use Horoshop\Exceptions\UnavailablePageException;
use Horoshop\App\Controller\Pages;

class ProductAggregator
{
    /**
     * @var string
     */
    private $filename;

    /**
     * ProductAggregator constructor.
     *
     * @param string $filename
     */
    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    /**
     * @param string $currency
     * @param int    $page
     * @param int    $perPage
     *
     * @return string Json
     * @throws UnavailablePageException
     */
    public function find(string $currency, int $page, int $perPage): string 
    {
        return Pages::GetPage($this->filename, $page, $perPage, $currency);
    }
}