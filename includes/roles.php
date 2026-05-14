<?php

function require_role($role) {
    $user = current_user();
    if (!$user || !current_user_id()) {
        abort_page('Access denied', 403);
    }
    // Admins bypass role checks
    if (!empty($user['role']) && $user['role'] === 'admin') return;
    if ($user['role'] !== $role) {
        abort_page('Access denied', 403);
    }
}

function is_admin() {
    return current_user() && current_user()['role'] === 'admin';
}

function is_organizer() {
    return current_user() && in_array(current_user()['role'], ['organizer', 'admin']);
}

function is_judge() {
    return current_user() && in_array(current_user()['role'], ['judge', 'admin']);
}

function is_participant() {
    return current_user() && in_array(current_user()['role'], ['participant', 'admin']);
}
