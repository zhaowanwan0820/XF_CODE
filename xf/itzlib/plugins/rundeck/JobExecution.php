<?php
/**
 * Job execution event, either currently running or complete
 */
class JobExecution {
	/**
	 * name of job
	 * @var [type]
	 */
	var $job;

	/**
	 * Status - one of complete|running|failed
	 * @var [type]
	 */
	var $status;

	/**
	 * unix time of start
	 * @var [type]
	 */
	var $started;

	/**
	 * unix time of end, if complete or failed
	 * @var [type]
	 */
	var $ended;

	/**
	 * execution log
	 * @var array
	 */
	var $log = array(); // log output
}
