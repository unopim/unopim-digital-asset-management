<?php

use Webkul\DAM\Models\ActionRequest;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('should return action request status for a given event type', function () {
    ActionRequest::create([
        'event_type' => 'delete_directory',
        'status'     => 'completed',
        'admin_id'   => auth()->id(),
    ]);

    $response = $this->getJson(route('admin.dam.action_request.status', 'delete_directory'));

    $response->assertOk()
        ->assertJson([
            'status' => 'completed',
        ]);
});

it('should return null status when no action request exists', function () {
    $response = $this->getJson(route('admin.dam.action_request.status', 'nonexistent_event'));

    $response->assertOk()
        ->assertJson([
            'status'  => null,
            'message' => null,
        ]);
});

it('should return pending status for in-progress action', function () {
    ActionRequest::create([
        'event_type' => 'copy_directory_structure',
        'status'     => 'pending',
        'admin_id'   => auth()->id(),
    ]);

    $response = $this->getJson(route('admin.dam.action_request.status', 'copy_directory_structure'));

    $response->assertOk()
        ->assertJson([
            'status' => 'pending',
        ]);
});

it('should return failed status with error message', function () {
    ActionRequest::create([
        'event_type'    => 'rename_directory',
        'status'        => 'failed',
        'error_message' => 'Directory not writable',
        'admin_id'      => auth()->id(),
    ]);

    $response = $this->getJson(route('admin.dam.action_request.status', 'rename_directory'));

    $response->assertOk()
        ->assertJson([
            'status'  => 'failed',
            'message' => 'Directory not writable',
        ]);
});
