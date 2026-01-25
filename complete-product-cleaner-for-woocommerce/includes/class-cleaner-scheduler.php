<?php

/**
 * Cleaner Scheduler Handler
 *
 * @package CompleteProductCleaner
 */

if (! defined('ABSPATH')) {
    exit;
}

class Cleaner_Scheduler
{

    const HOOK_NAME = 'wccc_process_cleanup_batch';
    const GROUP_NAME = 'wccc_cleanup';

    public function __construct()
    {
        add_action(self::HOOK_NAME, array($this, 'process_batch'), 10, 2);
    }

    /**
     * Queue the cleanup process.
     * This technically just starts the loop by scheduling the first batch immediately.
     *
     * @param string $type Module type (products, orders, etc).
     * @param array  $options Additional options.
     */
    public function start_cleanup($type, $options = array())
    {
        if (false === as_next_scheduled_action(self::HOOK_NAME, array('type' => $type), self::GROUP_NAME)) {
            as_schedule_single_action(time(), self::HOOK_NAME, array('type' => $type, 'options' => $options), self::GROUP_NAME);
        }
    }

    /**
     * Stop/Cancel cleanup.
     *
     * @param string $type Module type.
     */
    public function stop_cleanup($type)
    {
        as_unschedule_action(self::HOOK_NAME, array(), self::GROUP_NAME);
    }

    /**
     * Process a batch of items.
     *
     * @param string $type Module type.
     * @param array $options Options.
     */
    public function process_batch($type, $options = array())
    {
        $cleaner = Cleaner_Core::get_module($type);

        if (! $cleaner) {
            return;
        }

        // Get batch (default 20 to be safe for diverse server resources)
        $batch_size = apply_filters('wccc_batch_size', 20);
        $items = $cleaner->get_items_to_delete($batch_size);

        if (empty($items)) {
            // Done!
            $cleaner->cleanup();
            return;
        }

        foreach ($items as $id) {
            $cleaner->delete_item($id, $options);
        }

        // Schedule next batch
        as_schedule_single_action(time() + 2, self::HOOK_NAME, array('type' => $type, 'options' => $options), self::GROUP_NAME);
    }

    /**
     * Get generic status for UI polling.
     */
    public function get_status()
    {
        // Just a simple check if any action is pending
        $pending = as_get_scheduled_actions(array(
            'hook' => self::HOOK_NAME,
            'group' => self::GROUP_NAME,
            'status' => \ActionScheduler_Store::STATUS_PENDING
        ));

        return count($pending) > 0 ? 'running' : 'idle';
    }
}
