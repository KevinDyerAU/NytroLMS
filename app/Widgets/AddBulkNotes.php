<?php

namespace App\Widgets;

use Arrilot\Widgets\AbstractWidget;

class AddBulkNotes extends AbstractWidget
{
    /**
     * The configuration array.
     *s.
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

        return view('widgets.add_bulk_note', [
            'config' => $this->config,
        ]);
    }
}
