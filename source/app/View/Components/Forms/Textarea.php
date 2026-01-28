<?php

namespace App\View\Components\Forms;

use Illuminate\View\Component;

class Textarea extends Component
{
    public $name;

    public $title;

    public $inputClass;

    public $labelClass;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($name, $inputClass, $labelClass)
    {
        $this->name = $name;
        $this->title = \Str::title(\Str::replace('_', ' ', $name));
        $this->inputClass = $inputClass;
        $this->labelClass = $labelClass;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.forms.textarea');
    }
}
