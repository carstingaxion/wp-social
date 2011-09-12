<?php
/**
 * Social's Aggregation Queue
 *
 * @package Social
 * @subpackage aggregation
 */
final class Social_Aggregation_Queue {

	/**
	 * Initializes the queue.
	 *
	 * @static
	 * @return Social_Aggregation_Queue
	 */
	public static function factory() {
		return new Social_Aggregation_Queue;
	}

	/**
	 * @var  array  queue of posts
	 */
	private $_queue = array();

	/**
	 * Populates Social_Aggregation_Queue with the queue from the database.
	 */
	public function __construct() {
		$queue = Social::instance()->option('aggregation_queue');
		if (!empty($queue)) {
			$this->_queue = $queue;
		}
	}

	/**
	 * Returns an array of queue items that can be run.
	 *
	 * @return array
	 */
	public function runnable() {
		$queue = array();
		$current_timestamp = current_time('timestamp', 1);
		foreach ($this->_queue as $timestamp => $posts) {
			if ($timestamp <= $current_timestamp) {
				$queue[$timestamp] = $posts;
			}
		}

		return $queue;
	}

	/**
	 * Adds a post to the queue based on interval, if it's not already set.
	 *
	 * @param  int     $post_id   post id
	 * @param  string  $interval  schedule key
	 * @return Social_Aggregation_Queue
	 */
	public function add($post_id, $interval = null) {
		$this->remove($post_id);

		// Find the next interval to schedule
		$next_run = 0;
		if ($interval === null) {
			foreach ($this->schedule() as $interval => $next_run) {
				break;
			}
		}
		else {
			$schedule = $this->schedule();
			$found = false;
			foreach ($schedule as $key => $timestamp) {
				if (!$found) {
					if ($key == $interval) {
						$found = true;
						continue;
					}
				}
				else {
					$next_run = $timestamp;
					$interval = $key;
				}
			}

			if (!$found) {
				return $this;
			}
		}

		if ($next_run) {
			if (!isset($this->_queue[$next_run])) {
				$this->_queue[$next_run] = array();
			}

			if (!isset($this->_queue[$next_run][$post_id])) {
				$this->_queue[$next_run][$post_id] = $interval;
				update_post_meta($post_id, '_social_aggregation_next_run', $next_run);
			}

			Social::log('Post #:post_id added to the aggregation queue. (Interval: :interval, Next run: :next_run)', array(
				'post_id' => $post_id,
				'interval' => $interval,
				'next_run' => date('F j, Y, g:i a', ($next_run + (get_option('gmt_offset') * 3600))),
			));
		}

		return $this;
	}

	/**
	 * Removes a post from the queue completely, or by timestamp.
	 *
	 * @param  int  $post_id    post id
	 * @param  int  $timestamp  (optional) timestamp to remove by
	 * @return Social_Aggregation_Queue
	 */
	public function remove($post_id, $timestamp = null) {
		if ($timestamp === null) {
			$queue = array();
			foreach ($this->_queue as $timestamp => $posts) {
				foreach ($posts as $id => $interval) {
					if ($id !== $post_id) {
						if (!isset($queue[$timestamp])) {
							$queue[$timestamp] = array();
						}

						$queue[$timestamp][$id] = $interval;
					}
				}
			}
			$this->_queue = $queue;
		}
		else {
			if (isset($this->_queue[$timestamp]) and isset($this->_queue[$timestamp][$post_id])) {
				unset($this->_queue[$timestamp][$post_id]);

				if (empty($this->_queue[$timestamp])) {
					unset($this->_queue[$timestamp]);
				}
			}
		}

		delete_post_meta($post_id, '_social_aggregation_next_run');

		Social::log('Post #:post_id removed from the aggregation queue.', array(
			'post_id' => $post_id
		));

		return $this;
	}

	/**
	 * Attempts to find the post in the queue.
	 *
	 * @param  int  $post_id
	 * @return bool|object
	 */
	public function find($post_id) {
		foreach ($this->_queue as $timestamp => $posts) {
			foreach ($posts as $id => $interval) {
				if ($id === $post_id) {
					return (object) array(
						'post_id' => $id,
						'interval' => $interval,
						'next_run' => $timestamp
					);
				}
			}
		}
		return false;
	}

	/**
	 * Saves the queue.
	 *
	 * @return void
	 */
	public function save() {
		Social::instance()->option('aggregation_queue', $this->_queue, true);
	}

	/**
	 * Returns a filterable list of schedules and their timestamp.
	 *
	 * @return mixed|void
	 */
	protected function schedule() {
		$current_time = current_time('timestamp', 1);
		return apply_filters('social_aggregation_schedule', array(
			'15min' => $current_time + 900,
			'30min' => $current_time + 1800,
			'45min' => $current_time + 2700,
			'60min' => $current_time + 3600,
			'2hr' => $current_time + 7200,
			'4hr' => $current_time + 14400,
			'8hr' => $current_time + 28800,
			'12hr' => $current_time + 43200,
			'24hr' => $current_time + 86400,
			'48hr' => $current_time + 172800,
		));
	}

} // End Social_Aggregation_Queue