<?php

namespace App\Widgets;

use Arrilot\Widgets\AbstractWidget;

class StaticWidget extends AbstractWidget
{
    /**
     * The configuration array.
     *
     * @var array
     */
    protected $config = [
        'content' => '',
    ];

    /**
     * Treat this method as a controller action.
     * Return view() or other content to display.
     */
    public function run()
    {
        return view('widgets.static_widget', [
            'config' => $this->config,
        ]);
    }
}
