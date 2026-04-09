<?php

use Webkul\DAM\Models\ActionRequest;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('can create an action request', function () {
    $request = ActionRequest::create([
        'event_type' => 'delete_directory',
        'status'     => 'pending',
        'admin_id'   => auth()->id(),
    ]);

    expect($request)->toBeInstanceOf(ActionRequest::class);
    expect($request->event_type)->toBe('delete_directory');
    expect($request->status)->toBe('pending');
});

it('has correct table name', function () {
    $request = new ActionRequest;
    expect($request->getTable())->toBe('dam_action_request');
});

it('has correct fillable attributes', function () {
    $request = new ActionRequest;
    expect($request->getFillable())->toBe(['event_type', 'status', 'error_message', 'admin_id']);
});

it('can find one where with conditions', function () {
    ActionRequest::create([
        'event_type' => 'rename_directory',
        'status'     => 'completed',
        'admin_id'   => auth()->id(),
    ]);

    $found = ActionRequest::findOneWhere([
        'event_type' => 'rename_directory',
        'admin_id'   => auth()->id(),
    ]);

    expect($found)->not->toBeNull();
    expect($found->event_type)->toBe('rename_directory');
    expect($found->status)->toBe('completed');
});

it('returns null when findOneWhere has no match', function () {
    $found = ActionRequest::findOneWhere([
        'event_type' => 'nonexistent_event',
        'admin_id'   => 99999,
    ]);

    expect($found)->toBeNull();
});

it('can store error messages for failed actions', function () {
    $request = ActionRequest::create([
        'event_type'    => 'copy_directory_structure',
        'status'        => 'failed',
        'error_message' => 'Disk is full',
        'admin_id'      => auth()->id(),
    ]);

    expect($request->status)->toBe('failed');
    expect($request->error_message)->toBe('Disk is full');
});

it('can update status from pending to completed', function () {
    $request = ActionRequest::create([
        'event_type' => 'move_directory_structure',
        'status'     => 'pending',
        'admin_id'   => auth()->id(),
    ]);

    $request->update(['status' => 'completed']);

    expect($request->refresh()->status)->toBe('completed');
});

it('can update status from pending to failed with error', function () {
    $request = ActionRequest::create([
        'event_type' => 'delete_directory',
        'status'     => 'pending',
        'admin_id'   => auth()->id(),
    ]);

    $request->update([
        'status'        => 'failed',
        'error_message' => 'Permission denied',
    ]);

    $request->refresh();
    expect($request->status)->toBe('failed');
    expect($request->error_message)->toBe('Permission denied');
});
