<?php

namespace Kuberdock\classes\extensions\paginator;

use Kuberdock\classes\KuberDock_View;

class Pagination {
    /**
     * @var int
     */
    public $itemsPerPage;

    /**
     * @var
     */
    private $page;
    /**
     * @var int
     */
    private $count;
    /**
     * @var string
     */
    private $pageParam = 'page';
    /**
     * @var string
     */
    private $template = 'default';

    /**
     * @param $page
     * @param int $count
     * @param int $itemsPerPage
     */
    public function __construct($page, $count = 0, $itemsPerPage = 10)
    {
        $this->page = $page;
        $this->count = $count;
        $this->itemsPerPage = $itemsPerPage;
    }

    /**
     * @param $template
     * @return $this
     */
    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @param $param
     */
    public function setPageParam($param)
    {
        $this->pageParam = $param;
    }

    /**
     * @param bool $output
     * @return string
     */
    public function render($output = true)
    {
        $view = new KuberDock_View();
        $view->setViewDirectory(dirname(__FILE__) . DS . 'views');

        $pageCount = ($this->itemsPerPage) ? $this->count % $this->itemsPerPage : 0;

        $data = $view->renderPartial($this->template, array(
            'page' => $this->page,
            'pageCount' => $pageCount,
            'pageParam' => $this->pageParam,
            '_this' => $this,
        ), $output);

        return $data;
    }

    /**
     * @param $page
     * @return string
     */
    public function getUrl($page)
    {
        $url = parse_url($_SERVER['REQUEST_URI']);
        parse_str($url['query'], $query);

        $query[$this->pageParam] = $page;
        $query = http_build_query($query);

        return $url['path'] . $query ? '?' . $query : '';
    }
} 