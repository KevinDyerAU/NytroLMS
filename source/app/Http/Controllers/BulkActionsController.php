<?php

namespace App\Http\Controllers;

class BulkActionsController extends Controller
{
    /**
     * Display the bulk notes creation page.
     */
    public function bulkNotes()
    {
        $pageConfigs = ['layoutWidth' => 'full'];

        return view('content.bulk-actions.bulk-notes', [
            'title' => 'Bulk Notes',
            'pageConfigs' => $pageConfigs,
        ]);
    }
}
