<?php

namespace App\View\Components\Forms;

use Illuminate\View\Component;

class Input extends Component
{
    public $name;

    public $title;

    public $inputClass;

    public $labelClass;

    /**
     * Create a new component instance.
     */
    public function __construct($name, $inputClass, $labelClass)
    {
        $this->name = $name;
        $this->title = \Str::title(\Str::replace('_', ' ', $name));
        $this->inputClass = $inputClass;
        $this->labelClass = $labelClass;

        switch ($name) {
            case 'dob':
                $this->title = 'DOB';

                break;
            case 'usi_number':
                $this->title = 'USI Number';

                break;
            case 'nominate_usi':
                $this->title = 'NOMINATE USI';

                break;
            default:
                break;
        }
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.forms.input');
    }
}
