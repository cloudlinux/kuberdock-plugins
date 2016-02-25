<?php

/**
 * Class KuberDock_Paginator
 *
 * https://github.com/maeharin/pagee
 */
class KuberDock_Paginator
{
    protected $base_url;
    protected $total_count;
    protected $per_page;
    protected $last_page;
    protected $current_page;
    protected $window;
    protected $records;
    protected $params;
    protected $anchor;
    public static function create($setting)
    {
        return new static($setting);
    }
    protected function __construct($setting)
    {
        $setting = array_merge($this->default_setting(), $setting);
        extract($setting);
        $this->base_url = $base_url;
        $this->total_count = intval($total_count);
        $this->per_page = intval($per_page);
        $this->last_page = (int)ceil($this->total_count / $this->per_page);
        $this->set_current_page($requested_page);
        $this->window = $window;
    }
    /**
     * default setting
     */
    protected function default_setting()
    {
        return array(
            'base_url'       => '',
            'per_page'       => 20,
            'requested_page' => 1,
            'window'         => 3
        );
    }
    /**
     * set current page
     * - if requested_page is greater than last_page, current_page is set to last_page
     * - if requrested_page is invalid, current_page is set to 1
     */
    protected function set_current_page($requested_page)
    {
        if (is_numeric($requested_page) && $requested_page > $this->last_page){
            $this->current_page = ($this->last_page > 0) ? $this->last_page : 1;
        } else {
            $this->current_page = $this->is_valid_requested_page($requested_page) ? intval($requested_page) : 1;
        }
    }
    /**
     * is requested_page valid?
     */
    protected function is_valid_requested_page($requested_page)
    {
        if ($requested_page >= 1 && filter_var($requested_page, FILTER_VALIDATE_INT) !== false) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * return limit value for sql
     */
    public function limit()
    {
        return $this->per_page;
    }
    /**
     * return offset value for sql
     */
    public function offset()
    {
        return ($this->current_page - 1) * $this->per_page;
    }
    /**
     * set results of sql
     */
    public function set_records($records)
    {
        $this->records = $records;
    }
    /**
     * return records
     */
    public function records()
    {
        return $this->records;
    }
    /**
     * total count
     */
    public function total_count()
    {
        return $this->total_count;
    }
    /**
     * current page number
     */
    public function current()
    {
        return $this->current_page;
    }
    /**
     * last page number
     */
    public function last()
    {
        return $this->last_page;
    }
    /**
     * prev page number
     */
    public function prev()
    {
        return $this->current_page - 1;
    }
    /**
     * next page number
     */
    public function next()
    {
        return $this->current_page + 1;
    }
    /**
     * before page numbers
     */
    public function befores()
    {
        $before_candidates = array();
        for($i = 1; $i <= $this->window; $i++) {
            $before_candidates[] = $this->current_page - $i;
        }
        return array_filter($before_candidates, function ($before) {
            if($before > 1) { return $before; }
        });
    }
    /**
     * after page numbers
     */
    public function afters()
    {
        $after_candidates = array();
        for($i = 1; $i <= $this->window; $i++) {
            $after_candidates[] = $this->current_page + $i;
        }
        $that = $this;
        return array_filter($after_candidates, function ($after) use ($that) {
            if($after < $that->last()) { return $after; }
        });
    }
    /**
     * current_page is first_page?
     */
    public function is_first()
    {
        return $this->current_page === 1 ? true : false;
    }
    /**
     * current_page is last_page?
     */
    public function is_last()
    {
        return $this->current_page === $this->last_page ? true : false;
    }
    /**
     * has before dot?
     */
    public function has_before_dot()
    {
        if($this->current_page >= (1 + $this->window + 2)) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * has after dot?
     */
    public function has_after_dot()
    {
        if($this->current_page <= ($this->last() - $this->window - 2)) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * append params
     */
    public function append_params($params)
    {
        $this->params = $params;
        return $this;
    }

    public function append_anchor($anchor)
    {
        $this->anchor = $anchor;
        return $this;
    }

    /**
     * generate html link
     */
    protected function html_link($page, $content)
    {
        $html = '<a href="';
        $html .= $this->base_url;
        $html .= "?page=" . $page;
        if($this->params) {
            $html .= "&".http_build_query($this->params);
        }
        if ($this->anchor) {
            $html .= '#' . $this->anchor;
        }
        $html .= '">';
        $html .= $content;
        $html .= '</a>';
        return $html;
    }
    /**
     * prev link html
     */
    public function prev_link()
    {
        if ($this->is_first()) {
            return '';
        } else {
            $html = '<li>';
            $html .= $this->html_link($this->prev(), 'prev');
            $html .= '</li>';
            return $html;
        }
    }
    /**
     * first link html
     */
    public function first_link()
    {
        if ($this->is_first()) {
            return '';
        } else {
            $html = '<li>';
            $html .= $this->html_link(1, '1');
            $html .= '</li>';
            return $html;
        }
    }
    /**
     * before dot
     */
    public function before_dot()
    {
        if(! $this->has_before_dot()) {
            return '';
        }
        $html = '<span>...</span>';
        return $html;
    }
    /**
     * before links
     */
    public function before_links()
    {
        $befores = $this->befores();
        if(empty($befores)) {
            return '';
        }
        sort($befores);
        $before_links = array();
        foreach($befores as $before) {
            $html = '<li>';
            $html .= $this->html_link($before, $before);
            $html .= '</li>';
            $before_links[] = $html;
        }
        return implode("\n", $before_links);
    }
    /**
     * current element
     */
    public function current_element()
    {
        $html = '<li class="active"><span>';
        $html .= $this->current_page;
        $html .= '</span></li>';
        return $html;
    }
    /**
     * after links
     */
    public function after_links()
    {
        $afters = $this->afters();
        if(empty($afters)) {
            return '';
        }
        $after_links = array();
        foreach($afters as $after) {
            $html = '<li>';
            $html .= $this->html_link($after, $after);
            $html .= '</li>';
            $after_links[] = $html;
        }
        return implode("\n", $after_links);
    }
    /**
     * after dot
     */
    public function after_dot()
    {
        if(! $this->has_after_dot()) {
            return '';
        }
        $html = '<span>...</span>';
        return $html;
    }
    /**
     * last link html
     */
    public function last_link()
    {
        if ($this->is_last()) {
            return '';
        } else {
            $html = '<li>';
            $html .= $this->html_link($this->last_page, $this->last_page);
            $html .= '</li>';
            return $html;
        }
    }
    /**
     * next link html
     */
    public function next_link()
    {
        if ($this->is_last()) {
            return '';
        } else {
            $html = '<li>';
            $html .= $this->html_link($this->next(), 'next');
            $html .= '</li>';
            return $html;
        }
    }
    /**
     * build pagination links
     */
    public function links()
    {
        if ($this->last_page <= 1) {
            return '';
        }

        $html = '';
        $html .= '<div class="row offset-top text-center">' . PHP_EOL;
        $html .= '<div class="pagination">' . PHP_EOL;
        $html .= '<ul>' . PHP_EOL;
        $html .= $this->prev_link() . PHP_EOL;
        $html .= $this->first_link() . PHP_EOL;
        $html .= $this->before_dot() . PHP_EOL;
        $html .= $this->before_links() . PHP_EOL;
        $html .= $this->current_element() . PHP_EOL;
        $html .= $this->after_links() . PHP_EOL;
        $html .= $this->after_dot() . PHP_EOL;
        $html .= $this->last_link() . PHP_EOL;
        $html .= $this->next_link() . PHP_EOL;
        $html .= '</ul>' . PHP_EOL;
        $html .= '</div>' . PHP_EOL;
        $html .= '</div>' . PHP_EOL;

        return $html;
    }
}