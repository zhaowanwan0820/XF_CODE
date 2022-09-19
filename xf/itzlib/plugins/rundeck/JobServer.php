<?php

/**
 * API to schedule, execute and monitor execution of jobs
 */
class JobServer {
	/**
	 * list jobs on server
	 *
	 * @return Job[]
	 */
	function jobs() {}

	/**
	 * log message
	 *
	 * @param  [type] $str [description]
	 * @return [type]      [description]
	 */
	function log($str) {
		// error_log($str);
	}

	/**
	 * retrieve details about job ($job either Job instance or name
	 *
	 * @param  Job|string $job
	 * @return [type]      [description]
	 */
	function job($job) {}

	/**
	 * find a job, throw exception or return null
	 *
	 * @param  [type]  $name [description]
	 * @param  boolean $fail [description] If true will throw exception if no job found, false return null
	 * @return Job
	 */
	function find($name, $fail = true) {}

	/**
	 * register job on server
	 *
	 * @param  [type] $job [description]
	 * @return void
	 */
	function create($job) {}

	/**
	 * run job
	 * @param  [type] $job [description]
	 * @return JobExecution details about start (status == running)
	 */
	function run($job) {}

	/**
	 * last executions of job with log
	 *
	 * @param  Job|string $job Name or job instance
	 * @param  integer $count Maximum number of records
	 * @return JobExecution[]
	 */
	function last($job, $count = 5) {}

	/**
	 * status (running/complete/failed), NULL if not yet run
	 *
	 * @param  Job|string $job Name or job instance
	 * @return string running|complete|failed
	 */
	function status($job) {}

	/**
	 * Delete job
	 */
	function delete($job) {}

	static function server($type = "Rundeck") {
		return new $type();
	}
}

