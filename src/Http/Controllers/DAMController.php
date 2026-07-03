<?php

namespace Webkul\DAM\Http\Controllers;

use Illuminate\View\View;

class DAMController
{
    /**
     * Display the DAM asset index page.
     *
     * @return View
     */
    public function index()
    {
        return view('dam::asset.index');
    }
}
