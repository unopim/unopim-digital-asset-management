<?php

namespace Webkul\DAM\Http\Controllers;

use Illuminate\View\View;

class DAMController
{

    public function index()
    {
        return view('dam::asset.index');
    }
}
