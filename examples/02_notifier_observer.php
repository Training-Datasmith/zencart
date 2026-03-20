<?php

declare(strict_types=1);

/**
 * Example: Using the Zen Cart notifier/observer system.
 *
 * All major Zen Cart classes extend `base`, which provides the attach() and
 * notify() mechanism. Observers register interest in named events and receive
 * a reference to data they can modify.
 *
 * This is Zen Cart's primary extension mechanism — use it instead of editing
 * core files.
 *
 * In production, observers are discovered automatically from
 * includes/classes/observers/*.php
 */

// --- Define an observer ---

class MyBreadcrumbObserver extends base
{
    public function __construct()
    {
        // Register interest in the breadcrumb-reset event
        $this->attach($this, ['NOTIFY_BREADCRUMB_RESET', 'NOTIFY_BREADCRUMB_ADD_LAST_ENTRY']);
    }

    /**
     * Called when a notified event fires.
     *
     * @param  object       $callingClass  The object that triggered the notification.
     * @param  string       $eventID       The event name constant.
     * @param  mixed        $param1        First mutable parameter (passed by reference from caller).
     * @param  mixed        $param2        Second mutable parameter.
     */
    public function update(&$callingClass, string $eventID, mixed &$param1 = null, mixed &$param2 = null): void
    {
        if ($eventID === 'NOTIFY_BREADCRUMB_ADD_LAST_ENTRY') {
            // $param1 is the entry being added — we could modify it here
            error_log("Breadcrumb entry added: " . ($param1['title'] ?? '(unknown)'));
        }
    }
}

// --- Instantiate the observer (normally done in init_observers.php) ---
// $observer = new MyBreadcrumbObserver();

// --- The breadcrumb class fires these events automatically on add()/reset() ---
// $breadcrumb->add('Product Name', 'https://...');
// → triggers NOTIFY_BREADCRUMB_ADD_LAST_ENTRY, calling $observer->update(...)
