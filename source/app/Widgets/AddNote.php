<?php

namespace App\Widgets;

use Arrilot\Widgets\AbstractWidget;

class AddNote extends AbstractWidget
{
    /**
     * The configuration array.
     *
     * @var array
     */
    protected $config = [
        'subject_type' => 'student',
        'subject_id' => 0,
        'input_id' => 'note_body',
    ];

    /**
     * Treat this method as a controller action.
     * Return view() or other content to display.
     */
    public function run()
    {
        //

        return view('widgets.add_note', [
            'config' => $this->config,
        ]);
    }
}
